<?php
/**
 * Get System Logs
 */
require_once(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ . '/../../../config/function.php');

if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!']));
}

$limit = 10;
$page = 1;
if (isset($_GET['page'])) {
    $page = (int) $_GET['page'];
}
if (isset($_GET['limit'])) {
    $limit = (int) $_GET['limit'];
}

$offset = ($page - 1) * $limit;

$list = $CMSNT->get_list("SELECT * FROM `logs` ORDER BY id DESC LIMIT $offset, $limit");
$total_rows = $CMSNT->num_rows("SELECT * FROM `logs`");

echo json_encode([
    'status' => 'success',
    'data' => $list,
    'total' => $total_rows,
    'page' => $page,
    'total_pages' => ceil($total_rows / $limit)
]);
?>