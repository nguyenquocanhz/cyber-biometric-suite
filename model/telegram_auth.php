<?php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/function.php');

$bot_token = $CMSNT->site('telegram_bot_token');
$is_ajax = false;

// Xác định phương thức gọi: GET (redirect) hay POST (AJAX callback / new library)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = true;
    $json_input = json_decode(file_get_contents('php://input'), true);
    
    if (!$json_input) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'msg' => 'Dữ liệu JSON không hợp lệ!']);
        exit;
    }
    
    // Kiểm tra xem là phương thức callback legacy hay new_library
    $method = isset($json_input['method']) ? $json_input['method'] : 'widget_callback';
    
    if ($method === 'new_library') {
        // ========================================
        // PHƯƠNG THỨC 3: Telegram Login Library MỚI (OIDC)
        // ========================================
        // Xử lý id_token hoặc user data từ Telegram Login Library
        $user_data = isset($json_input['user']) ? $json_input['user'] : $json_input;
        
        if (!isset($user_data['id'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'Dữ liệu người dùng từ Telegram không hợp lệ!']);
            exit;
        }
        
        // Nếu có hash, verify bằng HMAC-SHA256 (tương tự legacy)
        if (isset($user_data['hash'])) {
            if (!$bot_token) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'msg' => 'Bot Token chưa được cấu hình!']);
                exit;
            }
            $auth_data = $user_data;
            $check_hash = $auth_data['hash'];
            unset($auth_data['hash']);
            
            $data_check_arr = [];
            foreach ($auth_data as $key => $value) {
                $data_check_arr[] = $key . '=' . $value;
            }
            sort($data_check_arr);
            $data_check_string = implode("\n", $data_check_arr);
            
            $secret_key = hash('sha256', $bot_token, true);
            $hash = hash_hmac('sha256', $data_check_string, $secret_key);
            
            if (strcmp($hash, $check_hash) !== 0) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'msg' => 'Chữ ký xác thực không khớp!']);
                exit;
            }
            
            if (isset($auth_data['auth_date']) && (time() - $auth_data['auth_date']) > 86400) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'msg' => 'Phiên xác thực đã hết hạn!']);
                exit;
            }
        }
        
        $telegram_id = check_string($user_data['id']);
        $telegram_username = isset($user_data['username']) ? check_string($user_data['username']) : '';
        $first_name = isset($user_data['first_name']) ? check_string($user_data['first_name']) : '';
        
    } else {
        // ========================================
        // PHƯƠNG THỨC 2: Legacy Widget — JS Callback (AJAX POST)
        // ========================================
        if (!$bot_token) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'Bot Token chưa được cấu hình!']);
            exit;
        }
        
        $auth_data = $json_input;
        if (!isset($auth_data['hash'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'Thiếu hash xác thực!']);
            exit;
        }
        
        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        
        // Sắp xếp các tham số theo thứ tự bảng chữ cái để tạo chuỗi kiểm tra chữ ký
        $data_check_arr = [];
        foreach ($auth_data as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }
        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        
        // Tính toán chữ ký HMAC-SHA256
        $secret_key = hash('sha256', $bot_token, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);
        
        if (strcmp($hash, $check_hash) !== 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'Dữ liệu xác thực không khớp hoặc đã bị thay đổi!']);
            exit;
        }
        
        if ((time() - $auth_data['auth_date']) > 86400) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'msg' => 'Phiên xác thực đã hết hạn!']);
            exit;
        }
        
        $telegram_id = check_string($auth_data['id']);
        $telegram_username = isset($auth_data['username']) ? check_string($auth_data['username']) : '';
        $first_name = isset($auth_data['first_name']) ? check_string($auth_data['first_name']) : '';
    }
    
} else {
    // ========================================
    // PHƯƠNG THỨC 1: Legacy Widget — Redirect URL (GET)
    // ========================================
    if (!$bot_token) {
        die("<h1>Lỗi: Chưa cấu hình Telegram Bot Token trong hệ thống! Vui lòng liên hệ Admin.</h1>");
    }
    
    // 1. Nhận dữ liệu xác thực từ Telegram gửi về qua URL parameters
    $auth_data = $_GET;
    if (!isset($auth_data['hash'])) {
        die("<h1>Lỗi: Dữ liệu xác thực gửi từ Telegram không hợp lệ!</h1>");
    }
    
    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);
    
    // 2. Sắp xếp các tham số theo thứ tự bảng chữ cái để tạo chuỗi kiểm tra chữ ký
    $data_check_arr = [];
    foreach ($auth_data as $key => $value) {
        $data_check_arr[] = $key . '=' . $value;
    }
    sort($data_check_arr);
    $data_check_string = implode("\n", $data_check_arr);
    
    // 3. Tính toán chữ ký HMAC-SHA256 với khóa bí mật là mã hóa SHA256 của Bot Token
    $secret_key = hash('sha256', $bot_token, true);
    $hash = hash_hmac('sha256', $data_check_string, $secret_key);
    
    // 4. Kiểm tra xem chữ ký có khớp hay không
    if (strcmp($hash, $check_hash) !== 0) {
        die("<h1>Lỗi: Dữ liệu xác thực không khớp hoặc đã bị thay đổi!</h1>");
    }
    
    // 5. Kiểm tra thời hạn hiệu lực (giới hạn trong vòng 24 giờ để chống replay attack)
    if ((time() - $auth_data['auth_date']) > 86400) {
        die("<h1>Lỗi: Phiên xác thực từ Telegram đã hết hạn! Vui lòng thử lại.</h1>");
    }
    
    $telegram_id = check_string($auth_data['id']);
    $telegram_username = isset($auth_data['username']) ? check_string($auth_data['username']) : '';
    $first_name = isset($auth_data['first_name']) ? check_string($auth_data['first_name']) : '';
}

// ========================================
// XỬ LÝ ĐĂNG NHẬP / ĐĂNG KÝ (Chung cho cả 3 phương thức)
// ========================================

// Tìm kiếm người dùng có liên kết telegram_id trong database
$user = $CMSNT->get_row("SELECT * FROM `users` WHERE `telegram_id` = '$telegram_id'");

if (!$user) {
    // Nếu chưa liên kết, kiểm tra xem có tài khoản nào trùng tên đăng nhập với username Telegram không
    if (!empty($telegram_username)) {
        $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '$telegram_username'");
        if ($user) {
            // Liên kết tự động telegram_id vào tài khoản trùng tên đăng nhập
            $CMSNT->update("users", ['telegram_id' => $telegram_id], "`id` = " . $user['id']);
        }
    }
    
    // Nếu vẫn chưa có tài khoản, tiến hành đăng ký tài khoản mới cấp độ thường (user)
    if (!$user) {
        $username = $telegram_username ?: 'tg_' . $telegram_id;
        // Kiểm tra xem username đã tồn tại chưa để tránh bị lỗi trùng khóa UNIQUE
        $check_existing = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '$username'");
        if ($check_existing) {
            $username = $username . '_' . random('1234567890', 4);
        }

        $CMSNT->insert("users", [
            'username' => $username,
            'password' => md5(random('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 16)),
            'level' => 'user',
            'telegram_id' => $telegram_id,
            'created_at' => gettime()
        ]);
        $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `telegram_id` = '$telegram_id'");
    }
}

// Thiết lập phiên đăng nhập (Session)
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['level'] = $user['level'];
$_SESSION['logged_in'] = true;

// Phản hồi tùy theo phương thức gọi
if ($is_ajax) {
    // AJAX: Trả về JSON
    $redirect = ($user['level'] === 'admin') ? base_url('admin/index.php') : base_url('index.php');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'msg' => 'Đăng nhập Telegram thành công!',
        'redirect' => $redirect
    ]);
} else {
    // Redirect: Chuyển hướng người dùng về trang đích tương ứng
    $dest_url = ($user['level'] === 'admin') ? base_url('admin/index.php') : base_url('index.php');
    echo "
    <script type='text/javascript'>
        if (window.opener) {
            // Chuyển hướng trang cha (trang login chính)
            window.opener.location.href = '$dest_url';
            // Đóng popup Telegram
            window.close();
        } else {
            // Nếu không chạy dạng popup, chuyển hướng trực tiếp trang hiện tại
            window.location.href = '$dest_url';
        }
    </script>
    ";
}
exit;
?>
