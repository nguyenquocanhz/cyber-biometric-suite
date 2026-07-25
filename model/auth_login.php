<?php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $CMSNT->escape(check_string($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        die(json_encode(['status' => 'error', 'msg' => 'Vui lòng nhập đầy đủ thông tin']));
    }

    $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '$username'");

    if (!$user) {
        die(json_encode(['status' => 'error', 'msg' => 'Tài khoản không tồn tại']));
    }

    if ($user['banned'] == 1) {
        die(json_encode(['status' => 'error', 'msg' => 'Tài khoản của bạn đã bị khóa']));
    }

    if (md5($password) == $user['password']) {
        // Set session for logged-in user
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['level'] = $user['level'];
        $_SESSION['logged_in'] = true;

        die(json_encode(['status' => 'success', 'msg' => 'Đăng nhập thành công!']));
    } else {
        die(json_encode(['status' => 'error', 'msg' => 'Mật khẩu không chính xác']));
    }
}
?>