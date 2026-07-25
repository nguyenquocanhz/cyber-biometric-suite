<?php
/**
 * API: Update Post
 * Updates existing post with Editor.js JSON
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
    $title = check_string($_POST['title'] ?? '');
    $description = check_string($_POST['description'] ?? '');
    $image = check_string($_POST['image'] ?? '');
    $content_json = $_POST['content_json'] ?? '';
    $status = check_string($_POST['status'] ?? 'draft');

    // Validate
    if ($id <= 0) {
        throw new Exception('Invalid post ID');
    }

    if (empty($title)) {
        throw new Exception('Tiêu đề không được để trống');
    }

    if (!in_array($status, ['draft', 'published'])) {
        $status = 'draft';
    }

    // Check if post exists
    $post = $CMSNT->get_row("SELECT * FROM `posts` WHERE `id` = '$id'");
    if (!$post) {
        http_response_code(404);
        throw new Exception('Post not found');
    }

    // Generate new slug if title changed
    $slug = $post['slug'];
    if ($title !== $post['title']) {
        $slug = create_slug($title);

        // Check slug uniqueness
        $existing = $CMSNT->get_row("SELECT id FROM `posts` WHERE `slug` = '$slug' AND id != '$id'");
        if ($existing) {
            $slug = $slug . '-' . time();
        }
    }

    // Convert Editor.js JSON to HTML (placeholder)
    $content = '<p>Content rendered by Editor.js</p>';

    // Update post
    $updated = $CMSNT->update('posts', [
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'content_json' => $content_json,
        'description' => $description,
        'image' => $image,
        'status' => $status,
        'updated_at' => gettime()
    ], "`id` = '$id'");

    if ($updated) {
        // Log action
        admin_log("Cập nhật bài viết #$id: $title");

        echo json_encode([
            'status' => 'success',
            'msg' => 'Cập nhật bài viết thành công!',
            'slug' => $slug
        ]);
    } else {
        throw new Exception('Không có thay đổi nào');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}
