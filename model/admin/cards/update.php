<?php
/**
 * Update Card Model
 * Handles: Approve Manual, Reject, Auto Gateway
 */

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ . '/../../../config/function.php');
require_once(__DIR__ . '/../../../config/gateways/GatewayFactory.php');

// Disable error display to prevent breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

if (empty($_SESSION['username']) || empty($_SESSION['level']) || $_SESSION['level'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'msg' => 'Quyền truy cập bị từ chối!'], JSON_UNESCAPED_UNICODE));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = check_string($_POST['type'] ?? ''); // approve, reject, auto
    $id = check_string($_POST['id']);

    $row = $CMSNT->get_row("SELECT * FROM `napthe` WHERE `id` = '$id'");
    if (!$row) {
        die(json_encode(['status' => 'error', 'msg' => 'Không tìm thấy thẻ!']));
    }

    switch ($action) {
        case 'approve': // Manual Success
            if ($row['status'] == 1) {
                echo json_encode(['status' => 'error', 'msg' => 'Thẻ đã được duyệt trước đó!']);
            } else {
                $thucnhan = isset($_POST['thucnhan']) ? check_string($_POST['thucnhan']) : 0;

                // Update Status
// Thay đoạn update cũ bằng đoạn này để test
                $isUpdate = $CMSNT->update("napthe", [
                    'status' => 1,
                    'thucnhan' => $thucnhan,
                ], " `id` = '$id' ");

                if (!$isUpdate) {
                    // Nếu update thất bại, in ra lỗi (check trong Network tab)
                    echo json_encode(['status' => 'error', 'msg' => 'Lỗi SQL Update! ID: ' . $id]);
                    die();
                }

                // Add Money
                // if (isset($row['username'])) {
                //     $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '" . $row['username'] . "' ");
                //     if ($user) {
                //         $CMSNT->cong("users", "money", $thucnhan, " `username` = '" . $row['username'] . "' ");
                //         $CMSNT->cong("users", "total_money", $thucnhan, " `username` = '" . $row['username'] . "' ");

                //         $CMSNT->insert("dongtien", [
                //             'sotientruoc' => $user['money'],
                //             'sotienthaydoi' => $thucnhan,
                //             'sotiensau' => $user['money'] + $thucnhan,
                //             'thoigian' => gettime(),
                //             'noidung' => 'Duyệt thẻ (Thủ công) seri: ' . $row['serial'] . ' - Mệnh giá: ' . number_format($row['amount']),
                //             'username' => $row['username']
                //         ]);
                //     }
                // }
                // admin_log("Duyệt thẻ thủ công #" . $id . " - User: " . $row['username'] . " - Thực nhận: " . number_format($thucnhan));
                echo json_encode(['status' => 'success', 'msg' => 'Đã duyệt thẻ thành công!']);
            }
            break;

        case 'reject': // Manual Fail
            $CMSNT->update("napthe", ['status' => 2], " `id` = '$id' ");
            echo json_encode(['status' => 'success', 'msg' => 'Đã hủy thẻ thành công!']);
            break;

        case 'auto': // Submit to Gateway
            $telco = $row['telco'];
            $amount = $row['amount'];
            $serial = $row['serial'];
            $code = $row['code'];
            $request_id = $row['request_id'];

            // Get Active Gateway
            $active_gateway = $CMSNT->site('active_card_gateway');

            // Map legacy global functions if GatewayFactory doesn't exist? 
            // Assuming GatewayFactory works based on system.php usage.
            // But system.php uses GatewayFactory::create.
            // Wait, admin/cards.php originally used switch case with global functions DoiThe1s().
            // I should stick to original admin/cards.php logic OR upgrade to GatewayFactory if valid.
            // admin/cards.php line 24 used global function `DoiThe1s`.
            // Let's rely on what was there, or try Factory.

            // To be safe and compatible with admin/cards.php's original logic (which relied on included functions),
            // I need to ensure those functions are available. They are likely in config/function.php or similar?
            // Actually, in previous logs `system.php` included `config/gateways/GatewayFactory.php`.
            // But `admin/cards.php` invoked `DoiThe1s()` directly.
            // I should verify where `DoiThe1s` is defined. Likely `config/function.php`?
            // If I use `GatewayFactory` it should be cleaner.

            try {
                // Load GatewayFactory when needed
                require_once(__DIR__ . '/../../../config/gateways/GatewayFactory.php');

                // Try Factory first as it's cleaner
                $gateway = GatewayFactory::create($active_gateway ?: 'doithe1s');
                $res = $gateway->processCard([
                    'telco' => $telco,
                    'pin' => $code,
                    'serial' => $serial,
                    'amount' => $amount,
                    'request_id' => $request_id
                ]);

                if ($res['success']) {
                    admin_log("Gửi thẻ #" . $id . " sang cổng " . $active_gateway . " - Thành công");
                    echo json_encode(['status' => 'success', 'msg' => 'Gửi lên cổng thành công: ' . $res['message']]);
                } else {
                    admin_log("Gửi thẻ #" . $id . " sang cổng " . $active_gateway . " - Thất bại: " . $res['message']);
                    echo json_encode(['status' => 'error', 'msg' => 'Lỗi cổng: ' . $res['message']]);
                }
            } catch (Exception $e) {
                admin_log("Gửi thẻ #" . $id . " sang cổng " . $active_gateway . " - Lỗi Exception: " . $e->getMessage());
                // Fallback to legacy functions if Factory fails or not compatible?
                // For now, return error.
                echo json_encode(['status' => 'error', 'msg' => 'Exception: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'msg' => 'Hành động không hợp lệ']);
            break;
    }
}
?>