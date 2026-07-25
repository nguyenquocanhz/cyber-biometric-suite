<?php
/**
 * API: Get Post Data for Editing
 * Returns post data including Editor.js JSON
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
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('Invalid post ID');
    }

    $post = $CMSNT->get_row("SELECT * FROM `posts` WHERE `id` = '$id'");

    if (!$post) {
        http_response_code(404);
        throw new Exception('Post not found');
    }

    echo json_encode([
        'status' => 'success',
        'data' => $post
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}
