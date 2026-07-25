<?php
/**
 * API: Get Transaction History
 * Returns paginated transaction history for logged-in user
 */

require_once(__DIR__ . "/../config/config.php");
require_once(__DIR__ . "/../config/function.php");

ini_set('display_errors', '0');
error_reporting(0); // Suppress all reporting to output

header('Content-Type: application/json; charset=utf-8');

try {
    if (session_status() === PHP_SESSION_NONE)
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

    // Get parameters
    $page = (int) ($_GET['page'] ?? 1);
    $limit = (int) ($_GET['limit'] ?? 20);
    $filter = check_string($_GET['filter'] ?? 'all'); // all, success, pending, failed
    $type = check_string($_GET['type'] ?? 'cards'); // cards, orders

    $page = max(1, $page);
    $limit = min(100, max(10, $limit)); // Between 10-100
    $offset = ($page - 1) * $limit;

    $CMSNT = new CMSNT();

    // Fetch all games for lookup
    $games_list = $CMSNT->get_list("SELECT id, name FROM games");
    $games_map = [];
    foreach ($games_list as $g) {
        $games_map[$g['id']] = $g['name'];
    }

    // Get current user's username (for cards) or ID (for orders)
    // Cards use `username` column, Orders use `user_id` column used `user_id`.
    // Actually cards use `username`. Let's get both just in case.
    $user = $CMSNT->get_row("SELECT username FROM `users` WHERE `id` = '$user_id'");
    if (!$user) {
        http_response_code(404);
        json_response('error', 'User not found');
    }
    $username = $user['username'];

    if ($type === 'orders') {
        // Fetch from ORDERS table (Balance Payment)
        $where = "user_id = '$user_id'";
        if ($filter === 'success') {
            $where .= " AND status = 1";
        } elseif ($filter === 'pending') {
            $where .= " AND status = 99";
        } elseif ($filter === 'failed') {
            $where .= " AND status = 2 OR status = 3"; // 2=cancelled, however 3 is used widely for failed
        }

        $total = $CMSNT->num_rows("SELECT * FROM `orders` WHERE $where");
        $table_data = $CMSNT->get_list("
            SELECT * FROM `orders` 
            WHERE $where
            ORDER BY id DESC
            LIMIT $limit OFFSET $offset
        ");

        $formatted_data = [];
        foreach ($table_data as $row) {
            // Map status
            $status_text = 'Chờ xử lý';
            $status_class = 'pending';
            if ($row['status'] == 1) {
                $status_text = 'Thành công';
                $status_class = 'success';
            } else if ($row['status'] == 2) {
                $status_text = 'Đã hủy(Hoàn tiền)';
                $status_class = 'failed';
            }

            // Get Game Name (Optional, heavy query inside loop? better join or cache. name is stored in games table)
            // For now just show Game ID or simple Game Name if we can join.
            // Just show game_id or game_account

            $formatted_data[] = [
                'id' => $row['id'],
                'game_name' => $games_map[$row['game_id']] ?? 'Game #' . $row['game_id'],
                'game_id' => $games_map[$row['game_id']] ?? 'Game #' . $row['game_id'], // Keep legacy key for compatibility but use Name
                'game_account' => $row['game_account'],
                'account_info' => ($games_map[$row['game_id']] ?? 'Game') . " - " . $row['game_account'], // Combination for UI
                'amount' => (int) $row['amount'],
                'formatted_amount' => number_format($row['amount'], 0, ',', '.') . 'đ',
                'telco' => 'Số dư ví', // Payment Method
                'status' => $row['status'],
                'status_text' => $status_text,
                'status_class' => $status_class,
                'time' => $row['created_at'],
                'serial' => '', // No serial for balance
                'request_id' => ''
            ];
        }

    } else {
        // Fetch from NAPTHE table (Card Payment) - Default
        $where = "username = '$username'";
        if ($filter === 'success') {
            $where .= " AND status = 1";
        } elseif ($filter === 'pending') {
            $where .= " AND status = 99";
        } elseif ($filter === 'failed') {
            $where .= " AND status = 3";
        }

        $total = $CMSNT->num_rows("SELECT * FROM `napthe` WHERE $where");
        $table_data = $CMSNT->get_list("
            SELECT * FROM `napthe` 
            WHERE $where
            ORDER BY id DESC
            LIMIT $limit OFFSET $offset
        ");

        $formatted_data = [];
        foreach ($table_data as $trans) {
            $status_text = '';
            $status_class = '';
            switch ((int) $trans['status']) {
                case 1:
                    $status_text = 'Thành công';
                    $status_class = 'success';
                    break;
                case 3:
                    $status_text = 'Thất bại';
                    $status_class = 'failed';
                    break;
                case 99:
                    $status_text = 'Đang xử lý';
                    $status_class = 'pending';
                    break;
                default:
                    $status_text = 'Chờ duyệt';
                    $status_class = 'pending';
            }
            $formatted_data[] = [
                'id' => $trans['id'],
                'game_name' => $games_map[$trans['id_game']] ?? 'Game #' . ($trans['id_game'] ?? '?'),
                'game_id' => $games_map[$trans['id_game']] ?? 'Game #' . ($trans['id_game'] ?? '?'),
                'account_info' => $games_map[$trans['id_game']] ?? 'N/A', // Cards don't have account info usually stored here except ID
                'amount' => (int) $trans['amount'],
                'formatted_amount' => number_format($trans['amount'], 0, ',', '.') . ' VNĐ',
                'telco' => $trans['telco'],
                'status' => $trans['status'],
                'status_text' => $status_text,
                'status_class' => $status_class,
                'time' => $trans['thoigian'],
                'serial' => substr($trans['serial'], 0, 4) . '***',
                'request_id' => $trans['request_id']
            ];
        }
    }

    // Calculate pagination
    $total_pages = ceil($total / $limit);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'transactions' => $formatted_data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total,
                'per_page' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Get Transaction History Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Lỗi hệ thống',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
function json_response($status, $msg)
{
    echo json_encode(['status' => $status, 'msg' => $msg]);
    exit;
}