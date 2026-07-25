<?php
/**
 * Admin API: Add New Game
 */

require_once(__DIR__ . "/../../config/config.php");
require_once(__DIR__ . "/../../config/function.php");

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!']));
}

try {
    $name = check_string($_POST['name'] ?? '');
    $slug = check_string($_POST['slug'] ?? '');
    $image_url = check_string($_POST['image_url'] ?? '');
    $currency = check_string($_POST['currency'] ?? '');
    $currency_icon = check_string($_POST['currency_icon'] ?? '');
    $color = check_string($_POST['color'] ?? 'border-blue-500');
    $order_priority = (int) ($_POST['order_priority'] ?? 0);

    if (empty($name) || empty($slug)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Name and slug are required'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $CMSNT = new CMSNT();

    // Check if slug already exists
    $existing = $CMSNT->get_row("SELECT * FROM `games` WHERE `slug` = '$slug'");
    if ($existing) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Slug already exists'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $CMSNT->insert('games', [
        'name' => $name,
        'slug' => $slug,
        'image_url' => $image_url,
        'currency' => $currency,
        'currency_icon' => $currency_icon,
        'color' => $color,
        'order_priority' => $order_priority,
        'is_active' => 1
    ]);

    if ($result) {
        admin_log("Thêm mới Game: " . $name);
        echo json_encode([
            'status' => 'success',
            'msg' => 'Game added successfully'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Failed to insert game');
    }

} catch (Exception $e) {
    error_log('Add Game Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error adding game'
    ], JSON_UNESCAPED_UNICODE);
}
?>