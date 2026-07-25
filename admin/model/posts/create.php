<?php
/**
 * API: Create New Post
 * Accepts Editor.js JSON and saves post
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
    $title = check_string($_POST['title'] ?? '');
    $description = check_string($_POST['description'] ?? '');
    $image = check_string($_POST['image'] ?? '');
    $categories = $_POST['categories'] ?? []; // Array of category IDs
    $content_json = $_POST['content_json'] ?? ''; // Editor.js JSON
    $status = check_string($_POST['status'] ?? 'draft');

    // Validate
    if (empty($title)) {
        throw new Exception('Tiêu đề không được để trống');
    }

    if (!in_array($status, ['draft', 'published'])) {
        $status = 'draft';
    }

    // Generate slug
    $slug = create_slug($title);

    // Check slug uniqueness
    $existing = $CMSNT->get_row("SELECT id FROM `posts` WHERE `slug` = '$slug'");
    if ($existing) {
        $slug = $slug . '-' . time();
    }

    // Convert Editor.js JSON to HTML for backward compatibility
    $content = ''; // We'll render this on frontend from JSON
    if (!empty($content_json)) {
        // For now, store JSON as-is. Frontend will render it.
        // You can add a JSON-to-HTML converter here if needed
        $content = '<p>Content rendered by Editor.js</p>';
    }

    // Insert post
    $inserted = $CMSNT->insert('posts', [
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'content_json' => $content_json,
        'description' => $description,
        'image' => $image,
        'status' => $status,
        'author_id' => $_SESSION['user_id'],
        'created_at' => gettime(),
        'updated_at' => gettime()
    ]);

    if ($inserted) {
        $post_id = $CMSNT->insert_id();

        // Save categories
        if (!empty($categories) && is_array($categories)) {
            foreach ($categories as $cat_id) {
                $cat_id = (int) $cat_id;
                if ($cat_id > 0) {
                    $CMSNT->insert('post_categories', [
                        'post_id' => $post_id,
                        'category_id' => $cat_id,
                        'created_at' => gettime()
                    ]);
                }
            }
            // Update category post counts
            $cat_ids = implode(',', array_map('intval', $categories));
            $CMSNT->query("UPDATE `categories` SET `post_count` = (SELECT COUNT(*) FROM `post_categories` WHERE `category_id` = `categories`.`id`) WHERE `id` IN ($cat_ids)");
        }

        // Log action
        admin_log("Tạo bài viết mới #$post_id: $title");

        echo json_encode([
            'status' => 'success',
            'msg' => 'Tạo bài viết thành công!',
            'post_id' => $post_id,
            'slug' => $slug
        ]);
    } else {
        throw new Exception('Lỗi khi lưu bài viết');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}
