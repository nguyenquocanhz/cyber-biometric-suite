<?php
/**
 * Truncate System Logs
 */
require_once(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ . '/../../../config/function.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check admin permission (though included path usually assumes admin checks, standalone needs verify)
    // Assume this is called from admin panel where session is active, but safeguard:
    if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
        http_response_code(403);
        die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!'], JSON_UNESCAPED_UNICODE));
    }

    if ($CMSNT->query("TRUNCATE TABLE `logs`")) {
        echo json_encode(['status' => 'success', 'msg' => 'Cleared all logs']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Failed to truncate']);
    }
}
?>