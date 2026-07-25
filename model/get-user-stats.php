<?php
/**
 * API: Get User Statistics
 * Returns wallet balance, total deposit, total spent, and unique game IDs
 */

require_once(__DIR__ . "/../config/config.php");
require_once(__DIR__ . "/../config/function.php");

// Disable error display
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

try {
    session_start();

    // Check authentication
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Vui lòng đăng nhập'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $CMSNT = new CMSNT();

    // Get user info including verification fields
    $user = $CMSNT->get_row("SELECT money, total_money, username, email, phone, telegram_id, phone_verified, email_verified, telegram_verified, level, create_time FROM `users` WHERE `id` = '$user_id'");

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'msg' => 'Người dùng không tồn tại'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Get successful transactions total FOR THIS USER
    $success_query = $CMSNT->get_row("
        SELECT 
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM `napthe` 
        WHERE `status` = '1' AND `username` = '" . $user['username'] . "'
    ");

    $total_spent = $success_query['total_amount'] ?? 0;
    $success_count = $success_query['count'] ?? 0;

    // Get unique game IDs count FOR THIS USER (successful transactions only)
    $unique_ids = $CMSNT->get_list("
        SELECT DISTINCT id_game 
        FROM `napthe` 
        WHERE `status` = '1' 
        AND `username` = '" . $user['username'] . "'
        AND id_game IS NOT NULL 
        AND id_game != ''
    ");

    $unique_game_count = count($unique_ids);

    // Calculate total spent (since total_money is total deposited)
    $total_deposit = (int) $user['total_money'];
    $current_balance = (int) $user['money'];
    $calculated_spent = $total_deposit - $current_balance;

    // Return statistics with verification status
    echo json_encode([
        'status' => 'success',
        'data' => [
            'user' => [
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? '',
                'telegram_id' => $user['telegram_id'] ?? '',
                'level' => $user['level'],
                'member_since' => $user['create_time']
            ],
            'wallet' => [
                'balance' => $current_balance,
                'formatted_balance' => number_format($current_balance, 0, ',', '.') . ' VNĐ'
            ],
            'statistics' => [
                'total_deposit' => $total_deposit,
                'total_spent' => $calculated_spent,
                'success_transactions' => $success_count,
                'unique_game_ids' => $unique_game_count
            ],
            'verification' => [
                'phone_verified' => ($user['phone_verified'] ?? 0) == 1,
                'email_verified' => ($user['email_verified'] ?? 0) == 1,
                'telegram_verified' => ($user['telegram_verified'] ?? 0) == 1
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Get User Stats Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Lỗi hệ thống',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>