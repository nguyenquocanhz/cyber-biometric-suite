<?php
/**
 * API: List Posts with Pagination and Filtering
 * Returns posts list, pagination, and stats
 */

require_once(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ . '/../../../config/function.php');

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE)
    session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Unauthorized']));
}

try {
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $search = check_string($_GET['search'] ?? '');
    $status = check_string($_GET['status'] ?? '');
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Build WHERE clause
    $where = "1=1";
    if (!empty($search)) {
        $where .= " AND (`title` LIKE '%$search%' OR `content` LIKE '%$search%')";
    }
    if (!empty($status) && in_array($status, ['draft', 'published'])) {
        $where .= " AND `status` = '$status'";
    }

    // Get total count
    $total = $CMSNT->num_rows("SELECT * FROM `posts` WHERE $where");

    // Get posts
    $posts = $CMSNT->get_list("
        SELECT * FROM `posts` 
        WHERE $where 
        ORDER BY id DESC 
        LIMIT $limit OFFSET $offset
    ");

    // Get stats
    $stats = [
        'total' => $CMSNT->num_rows("SELECT * FROM `posts`"),
        'published' => $CMSNT->num_rows("SELECT * FROM `posts` WHERE `status` = 'published'"),
        'draft' => $CMSNT->num_rows("SELECT * FROM `posts` WHERE `status` = 'draft'")
    ];

    // Pagination
    $total_pages = ceil($total / $limit);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'posts' => $posts,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total,
                'per_page' => $limit
            ],
            'stats' => $stats
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}
