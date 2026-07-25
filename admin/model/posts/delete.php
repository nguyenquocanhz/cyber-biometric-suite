<?php
/**
 * API: Delete Post
 * Deletes a post and logs the action
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
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('Invalid post ID');
    }

    // Get post info before deleting
    $post = $CMSNT->get_row("SELECT title FROM `posts` WHERE `id` = '$id'");

    if (!$post) {
        http_response_code(404);
        throw new Exception('Post not found');
    }

    // Delete post
    $deleted = $CMSNT->remove('posts', "`id` = '$id'");

    if ($deleted) {
        // Log action
        admin_log("Xóa bài viết #$id: " . $post['title']);

        echo json_encode([
            'status' => 'success',
            'msg' => 'Xóa bài viết thành công!'
        ]);
    } else {
        throw new Exception('Lỗi khi xóa bài viết');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}
