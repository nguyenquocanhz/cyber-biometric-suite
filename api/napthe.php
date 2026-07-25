<?php
cmsnt_header('Content-Type: application/json; charset=utf-8');

// Tải cấu hình hệ thống
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/function.php');

if (!isset($CMSNT)) {
    $CMSNT = new CMSNT();
}

// 1. Xác thực API Key để đảm bảo an toàn bảo mật
$client_api_key = $_REQUEST['api_key'] ?? '';
$system_api_key = $CMSNT->site('site_api_key') ?: 'shopkcff_secret_api_key_123';

if (empty($client_api_key) || $client_api_key !== $system_api_key) {
    cmsnt_response_code(401);
    echo json_encode([
        'status' => 'error',
        'msg' => 'API Key không hợp lệ hoặc chưa được cấu hình!'
    ], JSON_UNESCAPED_UNICODE);
    cmsnt_exit();
}

$action = $_REQUEST['action'] ?? 'submit';

// ==========================================
// HÀNH ĐỘNG 1: SUBMIT - GỬI THẺ NẠP
// ==========================================
if ($action === 'submit') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        cmsnt_response_code(405);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Yêu cầu gửi thẻ phải sử dụng phương thức POST!'
        ], JSON_UNESCAPED_UNICODE);
        cmsnt_exit();
    }

    $id_game = check_string($_POST['id_game'] ?? '');
    $loaithe = check_string($_POST['telco'] ?? '');
    $menhgia = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
    $seri = check_string($_POST['serial'] ?? '');
    $pin = check_string($_POST['pin'] ?? '');

    if (empty($id_game) || empty($loaithe) || $menhgia <= 0 || empty($seri) || empty($pin)) {
        cmsnt_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Vui lòng cung cấp đầy đủ thông tin: id_game, telco, amount, serial, pin!'
        ], JSON_UNESCAPED_UNICODE);
        cmsnt_exit();
    }

    $card_approval_mode = $CMSNT->site('card_approval_mode') ?: 'manual';
    $request_id = random('qwertyuiopasdfghjklzxcvbnm1234567890QWERTYUIOPASDFGHJKLZXCVBNM', 12);

    // Chèn thẻ vào cơ sở dữ liệu hệ thống
    $isInsert = $CMSNT->insert("napthe", [
        'request_id' => $request_id,
        'id_game' => $id_game,
        'telco' => $loaithe,
        'amount' => $menhgia,
        'thucnhan' => 0,
        'serial' => $seri,
        'code' => $pin,
        'status' => 0, // Mặc định chờ duyệt
        'ip' => myip(),
        'fingerprint' => 'REST_API'
    ]);

    if (!$isInsert) {
        cmsnt_response_code(500);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Không lưu được giao dịch vào hệ thống. Vui lòng thử lại!'
        ], JSON_UNESCAPED_UNICODE);
        cmsnt_exit();
    }

    // Xử lý nạp tự động đẩy lên đối tác
    if ($card_approval_mode === 'auto') {
        $active_gateway = $CMSNT->site('active_card_gateway') ?: 'doithe1s';
        require_once(__DIR__ . "/../config/gateways/GatewayFactory.php");

        try {
            $gateway = GatewayFactory::create($active_gateway);
            $res = $gateway->processCard([
                'telco' => $loaithe,
                'pin' => $pin,
                'serial' => $seri,
                'amount' => $menhgia,
                'request_id' => $request_id
            ]);

            if ($res['success']) {
                $CMSNT->update("napthe", ['status' => 99], "`request_id` = '$request_id'");
                echo json_encode([
                    'status' => 'success',
                    'request_id' => $request_id,
                    'approval_mode' => 'auto',
                    'card_status' => 99,
                    'msg' => 'Gửi thẻ tự động lên đối tác thành công! Đang chờ duyệt.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $CMSNT->update("napthe", ['status' => 2], "`request_id` = '$request_id'");
                echo json_encode([
                    'status' => 'error',
                    'request_id' => $request_id,
                    'approval_mode' => 'auto',
                    'card_status' => 2,
                    'msg' => 'Đối tác từ chối thẻ: ' . $res['message']
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            $CMSNT->update("napthe", ['status' => 2], "`request_id` = '$request_id'");
            echo json_encode([
                'status' => 'error',
                'request_id' => $request_id,
                'approval_mode' => 'auto',
                'card_status' => 2,
                'msg' => 'Lỗi kết nối cổng tự động: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
        cmsnt_exit();
    } else {
        // Xử lý nạp chậm (Lưu hệ thống + Gửi tin nhắn Telegram)
        $telegram_message = "🔔 <b>[API] YÊU CẦU NẠP CHẬM MỚI!</b>\n";
        $telegram_message .= "------------------------------------\n";
        $telegram_message .= "📱 <b>Loại thẻ:</b> " . strtoupper($loaithe) . "\n";
        $telegram_message .= "💰 <b>Mệnh giá:</b> " . number_format($menhgia) . " VND\n";
        $telegram_message .= "🔑 <b>Mã thẻ (PIN):</b> <code>" . $pin . "</code>\n";
        $telegram_message .= "📜 <b>Số seri:</b> <code>" . $seri . "</code>\n";
        $telegram_message .= "🎯 <b>ID Game:</b> " . $id_game . "\n";
        $telegram_message .= "🆔 <b>Mã giao dịch:</b> <code>" . $request_id . "</code>\n";
        $telegram_message .= "🌐 <b>Địa chỉ IP:</b> " . myip() . "\n";
        $telegram_message .= "⏰ <b>Thời gian:</b> " . date('Y-m-d H:i:s') . "\n";
        $telegram_message .= "------------------------------------\n";
        $telegram_message .= "👉 Truy cập trang admin để duyệt thẻ.";

        send_telegram_notification($telegram_message);

        echo json_encode([
            'status' => 'success',
            'request_id' => $request_id,
            'approval_mode' => 'manual',
            'card_status' => 0,
            'msg' => 'Gửi thẻ thành công! Hệ thống đang chờ kiểm tra.'
        ], JSON_UNESCAPED_UNICODE);
        cmsnt_exit();
    }
}

// ==========================================
// HÀNH ĐỘNG 2: CHECK - KIỂM TRA TRẠNG THÁI THẺ
// ==========================================
if ($action === 'check') {
    $request_id = check_string($_REQUEST['request_id'] ?? '');

    if (empty($request_id)) {
        cmsnt_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Thiếu tham số request_id!'
        ], JSON_UNESCAPED_UNICODE);
        cmsnt_exit();
    }

    $card = $CMSNT->get_row("SELECT * FROM `napthe` WHERE `request_id` = '$request_id'");

    if (!$card) {
        cmsnt_response_code(404);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Không tìm thấy giao dịch với request_id này!'
        ], JSON_UNESCAPED_UNICODE);
        cmsnt_exit();
    }

    $card_approval_mode = $CMSNT->site('card_approval_mode') ?: 'manual';

    echo json_encode([
        'status' => 'success',
        'request_id' => $card['request_id'],
        'id_game' => $card['id_game'],
        'telco' => $card['telco'],
        'amount' => (int)$card['amount'],
        'thucnhan' => (int)$card['thucnhan'],
        'serial' => $card['serial'],
        'card_status' => (int)$card['status'],
        'approval_mode' => $card_approval_mode,
        'created_at' => $card['thoigian'],
        'updated_at' => $card['thoigian_dt']
    ], JSON_UNESCAPED_UNICODE);
    cmsnt_exit();
}

// Hành động không hỗ trợ
cmsnt_response_code(400);
echo json_encode([
    'status' => 'error',
    'msg' => 'Hành động (action) không được hỗ trợ!'
], JSON_UNESCAPED_UNICODE);
cmsnt_exit();
?>
