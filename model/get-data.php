<?php
/**
 * API: Get Games and Packages Data
 * Returns JSON data for frontend consumption
 * Updated: 2026-01-03 - Adapted to actual database structure
 */

require_once(__DIR__ . "/../config/config.php");
require_once(__DIR__ . "/../config/function.php");

header('Content-Type: application/json; charset=utf-8');

try {
    $CMSNT = new CMSNT();

    // =========================================
    // Fetch Games from Database
    // =========================================
    $games_data = $CMSNT->get_list("
        SELECT 
            id,
            name,
            slug,
            image_url as img,
            currency,
            currency_icon,
            color
        FROM `games`
        WHERE is_active = 1
        ORDER BY order_priority ASC
    ");

    // Format games data
    $games = [];
    foreach ($games_data as $game) {
        $games[] = [
            'id' => (int) $game['id'],
            'name' => $game['name'],
            'slug' => $game['slug'],
            'img' => $game['img'],
            'color' => $game['color'],
            'currency' => $game['currency'],
            'currency_icon' => $game['currency_icon'] ?? null
        ];
    }
    // ========================================
    // PACKAGES: From Database
    // Table structure:
    // - id, amount (VND), diamonds (string), promotion (X1/X2/X3/X10)
    // ========================================
    $packages_db = $CMSNT->get_list("
        SELECT id, amount, diamonds, promotion, created_at
        FROM `packages` 
        ORDER BY `amount` ASC
    ");

    // Transform packages to frontend format
    $packages = [];
    foreach ($packages_db as $pkg) {
        // Parse diamonds: "113" -> 113, "1.132" -> 1132, "2.750" -> 2750
        $diamonds_cleaned = str_replace(['.', ',', ' '], '', $pkg['diamonds']);
        $value = (int) $diamonds_cleaned;

        // Calculate bonus based on promotion
        $bonus = 0;
        $promo = strtoupper(trim($pkg['promotion']));
        switch ($promo) {
            case 'X2':
                $bonus = (int) ($value * 0.1); // 10% bonus
                break;
            case 'X3':
                $bonus = (int) ($value * 0.15); // 15% bonus
                break;
            case 'X10':
                $bonus = (int) ($value * 0.3); // 30% bonus
                break;
            default:
                $bonus = 0;
        }

        $packages[] = [
            'id' => (int) $pkg['id'],
            'price' => (int) $pkg['amount'],
            'value' => $value,
            'bonus' => $bonus
        ];
    }

    // Return success response
    echo json_encode([
        'status' => 'success',
        'data' => [
            'games' => $games,
            'packages' => $packages
        ],
        'meta' => [
            'games_source' => 'hardcoded',
            'games_count' => count($games),
            'packages_source' => 'database',
            'packages_count' => count($packages),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('get-data.php Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Không thể tải dữ liệu. Vui lòng thử lại sau.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>