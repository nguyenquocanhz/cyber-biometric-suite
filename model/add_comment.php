<?php
require_once(__DIR__ . '/../config/config.php');
require_once(__DIR__ . '/../config/function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = check_string($_POST['content']);

    if (empty($content)) {
        die(json_encode(['status' => 'error', 'msg' => 'Nội dung không được để trống']));
    }

    // Check for logged in user
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $user = $CMSNT->get_row("SELECT username FROM `users` WHERE `id` = '$user_id'");
        $name = $user ? $user['username'] : 'Người dùng';
    } else {
        // Generate random masked phone number for guests
        $prefixes = ['09', '03', '08', '07', '05'];
        $prefix = $prefixes[array_rand($prefixes)];
        $name = $prefix . rand(10, 99) . '****' . rand(100, 999);
    }

    // Insert into database
    $isInserted = $CMSNT->insert("comments", [
        'username' => $name,
        'content' => $content,
        'status' => 1, // 1 = Auto Approve
        'time' => date('Y-m-d H:i:s')
    ]);

    if ($isInserted) {
        echo json_encode(['status' => 'success', 'msg' => 'Bình luận thành công', 'name' => $name]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống']);
    }
}
?>