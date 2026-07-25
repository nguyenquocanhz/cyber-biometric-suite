<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add Money
if (isset($_POST['btnAddMoney'])) {
    $user_id = (int) $_POST['user_id'];
    $amount = (int) $_POST['amount'];
    $note = check_string($_POST['note'] ?? 'Admin cộng tiền');

    if ($amount > 0) {
        $CMSNT->query("UPDATE `users` SET `money` = `money` + $amount WHERE `id` = $user_id");
        // Log transaction
        $CMSNT->insert("money_logs", [
            'user_id' => $user_id,
            'type' => 'add',
            'amount' => $amount,
            'note' => $note,
            'admin' => $_SESSION['username'],
            'time' => gettime()
        ]);
        admin_log("Cộng " . number_format($amount) . "đ cho User ID #" . $user_id . ". Lý do: " . $note);
        echo '<script>Swal.fire("Thành công", "Đã cộng ' . number_format($amount) . 'đ", "success");</script>';
    }
}

// Handle Subtract Money
if (isset($_POST['btnSubtractMoney'])) {
    $user_id = (int) $_POST['user_id'];
    $amount = (int) $_POST['amount'];
    $note = check_string($_POST['note'] ?? 'Admin trừ tiền');

    if ($amount > 0) {
        // Check current balance first
        $user_data = $CMSNT->get_row("SELECT `money`, `username` FROM `users` WHERE `id` = $user_id");

        if (!$user_data) {
            echo '<script>Swal.fire("Lỗi", "Không tìm thấy người dùng!", "error");</script>';
        } elseif ($user_data['money'] < $amount) {
            echo '<script>Swal.fire("Lỗi", "Số dư không đủ! Hiện tại: ' . number_format($user_data['money']) . 'đ", "error");</script>';
        } else {
            $CMSNT->query("UPDATE `users` SET `money` = `money` - $amount WHERE `id` = $user_id");
            $CMSNT->insert("money_logs", [
                'user_id' => $user_id,
                'type' => 'subtract',
                'amount' => -$amount,
                'note' => $note,
                'admin' => $_SESSION['username'],
                'time' => gettime()
            ]);
            admin_log("Trừ " . number_format($amount) . "đ của User ID #" . $user_id . ". Lý do: " . $note);
            echo '<script>Swal.fire("Thành công", "Đã trừ ' . number_format($amount) . 'đ", "success");</script>';
        }
    } else {
        echo '<script>Swal.fire("Lỗi", "Số tiền phải lớn hơn 0!", "error");</script>';
    }
}

// Handle Lock/Unlock Account
if (isset($_POST['btnToggleBan'])) {
    $user_id = (int) $_POST['user_id'];
    $current_status = (int) $_POST['current_status'];
    $new_status = $current_status == 0 ? 1 : 0;

    $CMSNT->update("users", ['banned' => $new_status], " `id` = $user_id ");
    $msg = $new_status == 1 ? 'Đã khóa tài khoản!' : 'Đã mở khóa tài khoản!';
    admin_log($msg . " User ID #" . $user_id);
    echo '<script>Swal.fire("Thành công", "' . $msg . '", "success");</script>';
}

// Handle Delete User
if (isset($_POST['btnDeleteUser'])) {
    $user_id = (int) $_POST['user_id'];
    $CMSNT->remove("users", " `id` = $user_id ");
    admin_log("Xóa User ID #" . $user_id);
    echo '<script>Swal.fire("Thành công", "Đã xóa tài khoản!", "success");</script>';
}

// Search & Pagination
$search = check_string($_GET['search'] ?? '');
$per_page = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Build query
$where = "1=1";
if (!empty($search)) {
    $where .= " AND (`username` LIKE '%$search%' OR `email` LIKE '%$search%' OR `ip` LIKE '%$search%')";
}

$total_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `users` WHERE $where");
$total = $total_row['total'];

$list_user = $CMSNT->get_list("SELECT * FROM `users` WHERE $where ORDER BY `id` DESC LIMIT $start, $per_page");
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-6">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-users mr-2 text-primary"></i>Quản lý Thành viên
                    <span class="text-sm font-normal text-muted">(<?= $total ?> users)</span>
                </h5>
            </div>
            
            <!-- Search Form -->
            <div class="col-12 col-md-6">
                <form method="GET" class="d-flex justify-content-md-end gap-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Tìm username, email, IP..."
                        class="form-control form-control-sm w-50" style="max-width: 250px;">
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="/admin/users.php" class="btn btn-sm btn-secondary px-3">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4">ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Số dư</th>
                        <th>Cấp độ</th>
                        <th>IP</th>
                        <th>Trạng thái</th>
                        <th class="text-center pe-4">Hành động</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if (empty($list_user)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-muted">Không tìm thấy người dùng nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($list_user as $row): ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $row['id'] ?></td>
                                <td class="font-bold text-dark"><?= $row['username'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td class="font-bold text-success"><?= number_format($row['money']) ?>đ</td>
                                <td>
                                    <span class="badge <?= $row['level'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> text-uppercase">
                                        <?= $row['level'] ?>
                                    </span>
                                </td>
                                <td class="font-mono"><?= $row['ip'] ?></td>
                                <td>
                                    <?= $row['banned'] == 0
                                        ? '<span class="badge bg-success">Hoạt động</span>'
                                        : '<span class="badge bg-danger">Bị khóa</span>' ?>
                                </td>
                                <td class="pe-4 text-center">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Add Money -->
                                        <button onclick="showMoneyModal(<?= $row['id'] ?>, '<?= $row['username'] ?>', 'add')"
                                            class="btn btn-sm btn-success px-2"
                                            title="Cộng tiền">
                                            <i class="fas fa-plus"></i>
                                        </button>

                                        <!-- Subtract Money -->
                                        <button onclick="showMoneyModal(<?= $row['id'] ?>, '<?= $row['username'] ?>', 'subtract')"
                                            class="btn btn-sm btn-warning text-dark px-2"
                                            title="Trừ tiền">
                                            <i class="fas fa-minus"></i>
                                        </button>

                                        <!-- Lock/Unlock -->
                                        <form method="POST" class="d-inline"
                                            onsubmit="return confirm('<?= $row['banned'] == 0 ? 'Khóa' : 'Mở khóa' ?> tài khoản này?');">
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $row['banned'] ?>">
                                            <button type="submit" name="btnToggleBan"
                                                class="btn btn-sm <?= $row['banned'] == 0 ? 'btn-secondary' : 'btn-info' ?> px-2"
                                                title="<?= $row['banned'] == 0 ? 'Khóa tài khoản' : 'Mở khóa' ?>">
                                                <i class="fas fa-<?= $row['banned'] == 0 ? 'lock' : 'unlock' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form method="POST" class="d-inline"
                                            onsubmit="return confirm('XÓA tài khoản này? Hành động không thể hoàn tác!');">
                                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="btnDeleteUser"
                                                class="btn btn-sm btn-danger px-2"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white border-top">
        <?php
        $pagination_url = '/admin/users.php?' . (!empty($search) ? 'search=' . urlencode($search) . '&' : '');
        echo admin_phantrang($pagination_url, $start, $total, $per_page);
        ?>
    </div>
</div>

<!-- Money Modal -->
<div class="modal fade" id="moneyModal" tabindex="-1" aria-labelledby="moneyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold" id="moneyModalTitle">Xử lý số dư</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="moneyForm" method="POST">
                <input type="hidden" name="user_id" id="money_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold">Số tiền (VNĐ)</label>
                        <input type="number" name="amount" id="money_amount" min="1000" step="1000" required
                            class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold">Ghi chú (tùy chọn)</label>
                        <input type="text" name="note" placeholder="Lý do cộng/trừ tiền..." class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="moneySubmitBtn" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i> Xác nhận
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let moneyModal = null;
    $(document).ready(function() {
        moneyModal = new bootstrap.Modal(document.getElementById('moneyModal'));
    });

    function showMoneyModal(userId, username, type) {
        document.getElementById('money_user_id').value = userId;
        document.getElementById('money_amount').value = '';

        const title = document.getElementById('moneyModalTitle');
        const btn = document.getElementById('moneySubmitBtn');

        if (type === 'add') {
            title.innerHTML = '<i class="fas fa-plus-circle text-success mr-2"></i>Cộng tiền cho ' + username;
            btn.name = 'btnAddMoney';
            btn.className = 'btn btn-success';
        } else {
            title.innerHTML = '<i class="fas fa-minus-circle text-warning mr-2"></i>Trừ tiền của ' + username;
            btn.name = 'btnSubtractMoney';
            btn.className = 'btn btn-warning';
        }

        moneyModal.show();
    }
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>