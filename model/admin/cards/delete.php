<?php
/**
 * Delete Card Model
 */
require_once(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ . '/../../../config/function.php');

if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = check_string($_POST['id']);

    // Check if entry exists
    $row = $CMSNT->get_row("SELECT * FROM `napthe` WHERE `id` = '$id'");
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => 'Không tìm thấy thẻ!']));
    }

    if ($CMSNT->remove("napthe", " `id` = '$id' ")) {
        admin_log("Xóa thẻ cào #" . $id . " (Serial: " . $row['serial'] . ")");
        echo json_encode(['status' => 'success', 'msg' => 'Xóa thành công!']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Lỗi hệ thống!']);
    }
}
?>