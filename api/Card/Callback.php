<?php
/**
 * API Card Callback Handler
 * Handles callbacks from doithe1s.vn and similar card charging gateways
 */

require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/function.php');

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Get raw input data (supports JSON, POST, and GET)
    $raw_input = file_get_contents('php://input');
    $json_data = json_decode($raw_input, true) ?: [];

    $data = array_merge($_GET, $_POST, $json_data);

    // 2. Parse callback parameters
    $status         = isset($data['status']) ? (int) $data['status'] : null;
    $request_id     = isset($data['request_id']) ? check_string($data['request_id']) : '';
    $declared_value = isset($data['declared_value']) ? (int) $data['declared_value'] : 0;
    $real_value     = isset($data['value']) ? (int) $data['value'] : 0; // Real card value
    $amount         = isset($data['amount']) ? (int) $data['amount'] : 0; // Net received money
    $callback_sign  = isset($data['callback_sign']) ? check_string($data['callback_sign']) : '';

    if ($status === null || empty($request_id)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Missing parameters!']);
        exit;
    }

    // 3. Find transaction in database
    $card = $CMSNT->get_row("SELECT * FROM `napthe` WHERE `request_id` = '$request_id'");
    if (!$card) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'msg' => 'Transaction not found!']);
        exit;
    }

    // 4. Validate signature if partner_key is set
    $partner_key = $CMSNT->site('partner_key');
    if (!empty($partner_key) && !empty($callback_sign)) {
        // Check various common signature formulas to prevent gateway mismatch
        $sig1 = md5($partner_key . $status . $request_id);
        $sig2 = md5($partner_key . $request_id . $status);
        
        if (strcmp($callback_sign, $sig1) !== 0 && strcmp($callback_sign, $sig2) !== 0) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'msg' => 'Invalid signature!']);
            exit;
        }
    }

    // 5. Update transaction status
    $status_db = 0;
    $status_text = 'Chờ duyệt';

    if ($status == 1) {
        // Success - Correct amount
        $status_db = 1;
        $status_text = 'Thành công';
        $thucnhan = $real_value ?: $declared_value;
    } elseif ($status == 2) {
        // Success - Wrong amount
        $status_db = 1; // Mark as success so user receives reward, but register the wrong amount penalty if applicable
        $status_text = 'Sai mệnh giá';
        $thucnhan = $real_value;
    } else {
        // Failed / Rejected (status 3 or others)
        $status_db = 2; // Rejected
        $status_text = 'Thất bại';
        $thucnhan = 0;
    }

    // Update database
    $update_data = [
        'status' => $status_db,
        'thucnhan' => $thucnhan
    ];
    $CMSNT->update("napthe", $update_data, "`request_id` = '$request_id'");

    // Log system activity
    admin_log("Callback card #{$card['id']} (Seri: {$card['serial']}) -> Status: {$status_text}, Amount: " . number_format($thucnhan) . "đ");

    // Send Telegram notification
    $telegram_message = "🔔 <b>CALLBACK THẺ NẠP TỰ ĐỘNG!</b>\n";
    $telegram_message .= "------------------------------------\n";
    $telegram_message .= "🆔 <b>Giao dịch:</b> #{$card['id']}\n";
    $telegram_message .= "📱 <b>Loại thẻ:</b> " . strtoupper($card['telco']) . "\n";
    $telegram_message .= "📜 <b>Seri:</b> <code>{$card['serial']}</code>\n";
    $telegram_message .= "🎯 <b>ID Game:</b> {$card['id_game']}\n";
    $telegram_message .= "💰 <b>Khai báo:</b> " . number_format($card['amount']) . " VND\n";
    $telegram_message .= "💵 <b>Thực tế:</b> " . number_format($thucnhan) . " VND\n";
    $telegram_message .= "📈 <b>Trạng thái:</b> " . strtoupper($status_text) . "\n";
    $telegram_message .= "⏰ <b>Thời gian:</b> " . date('Y-m-d H:i:s') . "\n";
    $telegram_message .= "------------------------------------";
    send_telegram_notification($telegram_message);

    echo json_encode([
        'status' => 'success',
        'msg' => 'Callback processed successfully!',
        'card_status' => $status_db,
        'thucnhan' => $thucnhan
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Lỗi xử lý Callback trên máy chủ!',
        'debug' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
