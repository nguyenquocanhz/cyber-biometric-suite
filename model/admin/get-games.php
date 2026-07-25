<?php
/**
 * Admin API: Get All Games
 * Returns list of all games (including inactive)
 */

require_once(__DIR__ . "/../../config/config.php");
require_once(__DIR__ . "/../../config/function.php");

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $CMSNT = new CMSNT();

    $games = $CMSNT->get_list("
        SELECT * FROM `games`
        ORDER BY `order_priority` ASC, `id` ASC
    ");

    echo json_encode([
        'status' => 'success',
        'data' => $games
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Get Games Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error loading games'
    ], JSON_UNESCAPED_UNICODE);
}
?>