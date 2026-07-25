<?php
/**
 * Admin API: Update Game
 */

require_once(__DIR__ . "/../../config/config.php");
require_once(__DIR__ . "/../../config/function.php");

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!']));
}

try {
    $id = (int) ($_POST['id'] ?? 0);
    $name = check_string($_POST['name'] ?? '');
    $slug = check_string($_POST['slug'] ?? '');
    $image_url = check_string($_POST['image_url'] ?? '');
    $currency = check_string($_POST['currency'] ?? '');
    $currency_icon = check_string($_POST['currency_icon'] ?? '');
    $color = check_string($_POST['color'] ?? 'border-blue-500');
    $order_priority = (int) ($_POST['order_priority'] ?? 0);
    $is_active = (int) ($_POST['is_active'] ?? 1);

    if ($id <= 0 || empty($name) || empty($slug)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Invalid input'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $CMSNT = new CMSNT();

    // Check if game exists
    $game = $CMSNT->get_row("SELECT * FROM `games` WHERE `id` = '$id'");
    if (!$game) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'msg' => 'Game not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check slug uniqueness (except current game)
    $existing = $CMSNT->get_row("SELECT * FROM `games` WHERE `slug` = '$slug' AND `id` != '$id'");
    if ($existing) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Slug already exists'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $CMSNT->update('games', [
        'name' => $name,
        'slug' => $slug,
        'image_url' => $image_url,
        'currency' => $currency,
        'currency_icon' => $currency_icon,
        'color' => $color,
        'order_priority' => $order_priority,
        'is_active' => $is_active
    ], "`id` = '$id'");

    if ($result) {
        admin_log("Cập nhật Game #" . $id . ": " . $name);
        echo json_encode([
            'status' => 'success',
            'msg' => 'Game updated successfully'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Failed to update game');
    }

} catch (Exception $e) {
    error_log('Update Game Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error updating game'
    ], JSON_UNESCAPED_UNICODE);
}
?>