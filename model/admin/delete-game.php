<?php
/**
 * Admin API: Delete Game
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

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Invalid game ID'], JSON_UNESCAPED_UNICODE);
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

    // Delete the game
    $result = $CMSNT->remove('games', "`id` = '$id'");

    if ($result) {
        admin_log("Xóa Game #" . $id . " (" . $game['name'] . ")");
        echo json_encode([
            'status' => 'success',
            'msg' => 'Game deleted successfully'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('Failed to delete game');
    }

} catch (Exception $e) {
    error_log('Delete Game Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error deleting game'
    ], JSON_UNESCAPED_UNICODE);
}
?>