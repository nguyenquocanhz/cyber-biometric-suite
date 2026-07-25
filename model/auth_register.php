<?php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = check_string($_POST['username']);
    $email = check_string($_POST['email']);
    $password = check_string($_POST['password']);
    $confirm_password = check_string($_POST['confirm_password']);

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng nhập đầy đủ thông tin']));
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        die(json_encode(['status' => 'error', 'msg' => 'Tên đăng nhập không được chứa ký tự đặc biệt']));
    }

    if (strlen($username) < 6 || strlen($username) > 32) {
        die(json_encode(['status' => 'error', 'msg' => 'Tên đăng nhập phải từ 6 đến 32 ký tự']));
    }

    if ($password !== $confirm_password) {
        die(json_encode(['status' => 'error', 'msg' => 'Mật khẩu xác nhận không khớp']));
    }

    if ($CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '$username'")) {
        die(json_encode(['status' => 'error', 'msg' => 'Tên đăng nhập đã tồn tại']));
    }

    if ($CMSNT->get_row("SELECT * FROM `users` WHERE `email` = '$email'")) {
        die(json_encode(['status' => 'error', 'msg' => 'Email đã tồn tại']));
    }

    $isInserted = $CMSNT->insert("users", [
        'username' => $username,
        'email' => $email,
        'password' => MD5_Password($password),
        'token' => md5(uniqid()),
        'money' => 0,
        'level' => 'member',
        'ip' => myip(),
        'banned' => 0,
        'create_time' => date('Y-m-d H:i:s')
    ]);

    if ($isInserted) {
        // Get the newly created user
        $new_user = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '$username'");

        // Auto-login: Set session
        $_SESSION['user_id'] = $new_user['id'];
        $_SESSION['username'] = $new_user['username'];
        $_SESSION['email'] = $new_user['email'];
        $_SESSION['level'] = $new_user['level'];
        $_SESSION['logged_in'] = true;

        die(json_encode([
            'status' => 'success',
            'msg' => 'Đăng ký thành công! Đang chuyển hướng...',
            'redirect' => '/' // Redirect to homepage instead of login
        ]));
    } else {
        die(json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống, vui lòng thử lại sau']));
    }
}
?>