<?php
/**
 * Add System Log
 */
require_once(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ . '/../../../config/function.php');

if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!']));
}

// Define table if not exists
// (User should run SQL, but we can't ensure it here without error)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = check_string($_POST['content'] ?? '');
    $user_id = $_SESSION['user_id'] ?? 0; // Optional: get from session

    if (!empty($content)) {
        $CMSNT->insert("logs", [
            'user_id' => $user_id,
            'content' => $content,
            'createdate' => gettime(),
            'ip' => myip()
        ]);

        echo json_encode(['status' => 'success', 'msg' => 'Log added']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Content empty']);
    }
}
?>