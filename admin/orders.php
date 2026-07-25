<?php
// Handle Actions (Complete/Cancel/Delete) - AJAX Only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once(__DIR__ . '/../config/config.php');
    require_once(__DIR__ . '/../config/function.php');

    // Auth Check
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'msg' => 'Vui lòng đăng nhập']);
        exit;
    }
    $user = $CMSNT->get_row("SELECT * FROM `users` WHERE `id` = '$user_id'");
    if (!$user || $user['level'] != 'admin') {
        echo json_encode(['status' => 'error', 'msg' => 'Bạn không có quyền truy cập']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'complete') {
            $CMSNT->update("orders", ['status' => 1, 'updated_at' => date('Y-m-d H:i:s')], "id = $id");
            echo json_encode(['status' => 'success', 'msg' => 'Đã duyệt đơn hàng thành công!']);
            exit;
        } elseif ($action === 'cancel') {
            $order = $CMSNT->get_row("SELECT * FROM orders WHERE id = $id AND status = 99");
            if ($order) {
                $user_order = $CMSNT->get_row("SELECT * FROM users WHERE id = " . $order['user_id']);
                if ($user_order) {
                    $CMSNT->update("users", ['money' => $user_order['money'] + $order['amount']], "id = " . $order['user_id']);
                    // Log Money
                    $CMSNT->insert("dongtien", [
                        'sotientruoc' => $user_order['money'],
                        'sotienthaydoi' => $order['amount'],
                        'sotiensau' => $user_order['money'] + $order['amount'],
                        'thoigian' => gettime(),
                        'noidung' => "Hoàn tiền đơn hàng #" . $id . " (Admin Hủy)",
                        'username' => $user_order['username']
                    ]);
                }
                $CMSNT->update("orders", ['status' => 2, 'updated_at' => date('Y-m-d H:i:s')], "id = $id");
                echo json_encode(['status' => 'success', 'msg' => 'Đã hủy đơn hàng và hoàn tiền!']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Đơn hàng không hợp lệ hoặc đã xử lý']);
            }
            exit;
        } elseif ($action === 'delete') {
            $CMSNT->remove("orders", "id = $id");
            echo json_encode(['status' => 'success', 'msg' => 'Đã xóa đơn hàng!']);
            exit;
        }
    }
    exit;
}

require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-shopping-cart mr-2 text-primary"></i>Quản lý Đơn hàng
        </h5>
        <div class="d-inline-flex gap-1">
            <a href="/admin/orders.php" class="btn btn-sm btn-outline-secondary">Tất cả</a>
            <a href="/admin/orders.php?status=99" class="btn btn-sm btn-outline-warning">Chờ xử lý</a>
            <a href="/admin/orders.php?status=1" class="btn btn-sm btn-outline-success">Thành công</a>
            <a href="/admin/orders.php?status=2" class="btn btn-sm btn-outline-danger">Đã hủy</a>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4">ID</th>
                        <th>Người dùng</th>
                        <th>Game</th>
                        <th>Tài khoản Game</th>
                        <th>Gói nạp</th>
                        <th>Thanh toán</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th class="text-center pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php
                    // Pagination
                    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                    $page = max(1, $page);
                    $limit = 20;
                    $offset = ($page - 1) * $limit;

                    $where = "WHERE id > 0";
                    if (isset($_GET['status']) && $_GET['status'] != '') {
                        $where .= " AND status = " . (int) $_GET['status'];
                    }

                    $total_rows = $CMSNT->num_rows("SELECT * FROM orders $where");
                    $orders = $CMSNT->get_list("SELECT * FROM orders $where ORDER BY id DESC LIMIT $limit OFFSET $offset");

                    if ($orders):
                        foreach ($orders as $row):
                            $user = $CMSNT->get_row("SELECT username FROM users WHERE id = '" . $row['user_id'] . "'");
                            $game = $CMSNT->get_row("SELECT name FROM games WHERE id = '" . $row['game_id'] . "'");
                            $game_name = $game ? $game['name'] : 'Unknown Game (' . $row['game_id'] . ')';

                            $status_badge = '';
                            switch ($row['status']) {
                                case 99:
                                    $status_badge = '<span class="badge bg-warning text-dark">Chờ xử lý</span>';
                                    break;
                                case 1:
                                    $status_badge = '<span class="badge bg-success">Thành công</span>';
                                    break;
                                case 2:
                                    $status_badge = '<span class="badge bg-danger">Đã hủy</span>';
                                    break;
                                default:
                                    $status_badge = '<span class="badge bg-secondary">Khác</span>';
                                    break;
                            }
                            ?>
                            <tr>
                                <td class="ps-4 font-mono text-muted">#<?= $row['id'] ?></td>
                                <td class="font-bold text-primary"><?= $user ? $user['username'] : 'N/A' ?></td>
                                <td class="font-bold text-dark"><?= $game_name ?></td>
                                <td><?= htmlspecialchars($row['game_account']) ?></td>
                                <td>
                                    <p class="font-bold text-dark mb-0"><?= number_format($row['value']) ?> Token</p>
                                    <p class="text-xs text-muted mb-0">Gói ID: <?= $row['package_id'] ?></p>
                                </td>
                                <td class="font-bold text-danger"><?= number_format($row['amount']) ?>đ</td>
                                <td class="text-muted small"><?= $row['created_at'] ?></td>
                                <td><?= $status_badge ?></td>
                                <td class="pe-4 text-center">
                                    <div class="d-inline-flex gap-1">
                                        <?php if ($row['status'] == 99): ?>
                                            <button onclick="updateOrder(<?= $row['id'] ?>, 'complete')" class="btn btn-sm btn-outline-success px-2" title="Hoàn thành">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="updateOrder(<?= $row['id'] ?>, 'cancel')" class="btn btn-sm btn-outline-warning px-2" title="Hủy & Hoàn tiền">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="updateOrder(<?= $row['id'] ?>, 'delete')" class="btn btn-sm btn-outline-danger px-2" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="9" class="p-4 text-center text-muted">Chưa có đơn hàng nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white border-top">
        <?= admin_phantrang(base_url('admin/orders.php?') . (isset($_GET['status']) ? 'status=' . urlencode($_GET['status']) . '&' : ''), $offset, $total_rows, $limit) ?>
    </div>
</div>

<script>
    function updateOrder(id, action) {
        let title = '';
        let text = '';
        let confirmBtn = '';

        if (action === 'complete') {
            title = 'Duyệt đơn hàng?';
            text = 'Xác nhận đơn hàng đã được xử lý thành công?';
            confirmBtn = 'Duyệt';
        } else if (action === 'cancel') {
            title = 'Hủy đơn hàng?';
            text = 'Hủy đơn hàng và hoàn tiền lại cho người dùng?';
            confirmBtn = 'Hủy & Hoàn tiền';
        } else {
            title = 'Xóa đơn hàng?';
            text = 'Bạn có chắc muốn xóa lịch sử đơn hàng này?';
            confirmBtn = 'Xóa';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: confirmBtn,
            cancelButtonText: 'Đóng'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.location.href, // Post to self
                    type: 'POST',
                    data: { id: id, action: action },
                    dataType: 'json',
                    success: function (res) {
                        var resp = typeof res === 'object' ? res : JSON.parse(res);
                        if (resp.status === 'success') {
                            Swal.fire({title: 'Thành công', text: resp.msg, icon: 'success', timer: 1500, showConfirmButton: false}).then(() => location.reload());
                        } else {
                            Swal.fire('Lỗi', resp.msg, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>