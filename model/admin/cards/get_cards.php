<?php
/**
 * Get Cards Model (API)
 */
require_once(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ . '/../../../config/function.php');

if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!']));
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search & Filter
$search = check_string($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';

$where = "1=1";
if (!empty($search)) {
    $where .= " AND (`id_game` LIKE '%$search%' OR `serial` LIKE '%$search%' OR `code` LIKE '%$search%' OR `username` LIKE '%$search%')";
}
if ($status_filter !== '') {
    $where .= " AND `status` = '" . (int) $status_filter . "'";
}

// Data
$list = $CMSNT->get_list("SELECT * FROM `napthe` WHERE $where ORDER BY `id` DESC LIMIT $start, $limit");
$total_rows = $CMSNT->num_rows("SELECT COUNT(*) as total FROM `napthe` WHERE $where")['total'] ?? 0; // num_rows usually returns array for COUNT queries if using wrapper? 
// Actually CMSNT->num_rows usually used with SELECT * or specific. 
// If get_row logic: $_this->num_rows($sql) might return int or fetch.
// Let's stick to get_row for Count if num_rows implementation is row count of query result.
// Standard CMSNT num_rows usage: count($result).
// Best practice: get_row("SELECT COUNT(...)") then accessing field.
$total_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `napthe` WHERE $where");
$total = $total_row['total'];

echo json_encode([
    'status' => 'success',
    'data' => $list,
    'total' => $total,
    'page' => $page,
    'total_pages' => ceil($total / $limit)
]);
?>