<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// ========================================
// THỐNG KÊ TỔNG HỢP
// ========================================

// Tổng số thẻ
$totalCards = $CMSNT->num_rows("SELECT 1 FROM `napthe`") ?: 0;

// Tổng tiền nạp (tất cả thẻ)
$totalAmountRow = $CMSNT->get_row("SELECT SUM(amount) as total FROM `napthe`");
$totalAmount = $totalAmountRow['total'] ?: 0;

// Tổng thực nhận (chỉ thẻ thành công - status = 1 or 99)
$totalReceivedRow = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE `status` IN (1, 99) AND `thucnhan` > 0");
$totalReceived = $totalReceivedRow['total'] ?: 0;

// Thẻ chờ duyệt
$pendingCards = $CMSNT->num_rows("SELECT 1 FROM `napthe` WHERE `status` = 0") ?: 0;

// Thẻ hôm nay
$todayCards = $CMSNT->num_rows("SELECT 1 FROM `napthe` WHERE DATE(`thoigian`) = CURDATE()") ?: 0;

// Doanh thu hôm nay
$todayRevenueRow = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE DATE(`thoigian`) = CURDATE() AND `status` IN (1, 99)");
$todayRevenue = $todayRevenueRow['total'] ?: 0;

// ========================================
// DỮ LIỆU BIỂU ĐỒ - 14 NGÀY GẦN NHẤT
// ========================================

$chartDays = 14;
$chartData = [];

for ($i = $chartDays - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $displayDate = date('d/m', strtotime("-$i days"));

    // Tổng nạp trong ngày
    $dayAmountRow = $CMSNT->get_row("SELECT SUM(amount) as total FROM `napthe` WHERE DATE(`thoigian`) = '$date'");
    $dayAmount = $dayAmountRow['total'] ?: 0;

    // Thực nhận trong ngày (chỉ thẻ thành công)
    $dayReceivedRow = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE DATE(`thoigian`) = '$date' AND `status` IN (1, 99)");
    $dayReceived = $dayReceivedRow['total'] ?: 0;

    $chartData[] = [
        'date' => $displayDate,
        'amount' => (int) $dayAmount,
        'received' => (int) $dayReceived
    ];
}

$chartLabels = json_encode(array_column($chartData, 'date'));
$chartAmounts = json_encode(array_column($chartData, 'amount'));
$chartReceived = json_encode(array_column($chartData, 'received'));
?>

<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-2 rounded-circle bg-primary-subtle text-primary mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-bold text-dark"><?= number_format($totalCards) ?></h5>
                    <p class="text-xs text-muted mb-0">Tổng thẻ</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-2 rounded-circle bg-purple-subtle text-purple mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-bold text-dark"><?= number_format($totalAmount) ?></h5>
                    <p class="text-xs text-muted mb-0">Tổng nạp</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-2 rounded-circle bg-success-subtle text-success mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-bold text-dark"><?= number_format($totalReceived) ?></h5>
                    <p class="text-xs text-muted mb-0">Thực nhận</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-2 rounded-circle bg-warning-subtle text-warning mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-bold text-dark"><?= number_format($pendingCards) ?></h5>
                    <p class="text-xs text-muted mb-0">Chờ duyệt</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-2 rounded-circle bg-info-subtle text-info mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-bold text-dark"><?= number_format($todayCards) ?></h5>
                    <p class="text-xs text-muted mb-0">Hôm nay</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-2 rounded-circle bg-danger-subtle text-danger mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h5 class="mb-0 font-bold text-dark"><?= number_format($todayRevenue) ?></h5>
                    <p class="text-xs text-muted mb-0">DT Hôm nay</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-chart-area mr-2 text-primary"></i>Biểu đồ Doanh thu <?= $chartDays ?> ngày gần nhất
        </h5>
        <div class="d-inline-flex gap-2">
            <span class="badge bg-primary text-white">Tổng nạp</span>
            <span class="badge bg-success text-white">Thực nhận</span>
        </div>
    </div>
    <div class="card-body p-3">
        <canvas id="revenueChart" style="height: 250px; width: 100%;"></canvas>
    </div>
</div>

<!-- Cards Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-4">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-money-bill-wave mr-2 text-primary"></i>Lịch sử Nạp thẻ
                </h5>
            </div>
            
            <!-- Search & Filter -->
            <div class="col-12 col-md-8">
                <form method="GET" class="d-flex flex-wrap justify-content-md-end gap-2 mb-0">
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        placeholder="Tìm ID Game, Serial..." class="form-control form-control-sm w-auto" style="max-width: 180px;">
                    <select name="status" class="form-select form-select-sm w-auto">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Thành công</option>
                        <option value="2" <?= ($_GET['status'] ?? '') === '2' ? 'selected' : '' ?>>Thất bại</option>
                        <option value="99" <?= ($_GET['status'] ?? '') === '99' ? 'selected' : '' ?>>Chờ duyệt</option>
                        <option value="0" <?= ($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>Xử lý</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($_GET['search']) || isset($_GET['status'])): ?>
                        <a href="/admin/cards.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Action Bar -->
    <div id="bulkActionBar" class="d-none p-3 bg-primary-subtle text-primary-emphasis border-bottom d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <span class="small font-semibold">
                <i class="fas fa-check-square mr-1"></i>
                Đã chọn: <span id="selectedCount" class="font-bold">0</span> thẻ
            </span>
            <span class="text-muted">|</span>
            <span id="selectedAmount" class="small font-bold">0đ</span>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <button onclick="bulkAction('approve')" class="btn btn-sm btn-success">
                <i class="fas fa-check-double mr-1"></i> Duyệt tất cả
            </button>
            <button onclick="bulkAction('auto')" class="btn btn-sm btn-primary">
                <i class="fas fa-upload mr-1"></i> Gửi API hàng loạt
            </button>
            <button onclick="bulkAction('reject')" class="btn btn-sm btn-warning text-dark font-bold">
                <i class="fas fa-times mr-1"></i> Hủy tất cả
            </button>
            <button onclick="bulkAction('delete')" class="btn btn-sm btn-danger">
                <i class="fas fa-trash mr-1"></i> Xóa tất cả
            </button>
            <button onclick="clearSelection()" class="btn btn-sm btn-secondary">
                <i class="fas fa-times-circle mr-1"></i> Bỏ chọn
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap" id="cardsTable">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4" style="width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll()" class="form-check-input cursor-pointer">
                        </th>
                        <th>ID</th>
                        <th>ID Game</th>
                        <th>Nhà mạng</th>
                        <th>Thông tin thẻ</th>
                        <th>Mệnh giá</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th class="text-center pe-4">Hành động</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php
                    // Search & Pagination
                    $search = check_string($_GET['search'] ?? '');
                    $status_filter = $_GET['status'] ?? '';
                    $per_page = 20;
                    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                    $start = ($page - 1) * $per_page;

                    // Build query
                    $where = "1=1";
                    if (!empty($search)) {
                        $where .= " AND (`id_game` LIKE '%$search%' OR `serial` LIKE '%$search%' OR `code` LIKE '%$search%')";
                    }
                    if ($status_filter !== '') {
                        $where .= " AND `status` = '" . (int) $status_filter . "'";
                    }

                    // Get total count
                    $total_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `napthe` WHERE $where");
                    $total = $total_row['total'];

                    // Get paginated list
                    $list_card = $CMSNT->get_list("SELECT * FROM `napthe` WHERE $where ORDER BY `id` DESC LIMIT $start, $per_page");
                    
                    if (empty($list_card)) {
                        echo '<tr><td colspan="9" class="p-4 text-center text-muted">Chưa có giao dịch gạch thẻ nào.</td></tr>';
                    } else {
                        foreach ($list_card as $row) {
                            $status_badge = '';
                            $action_btn = '';

                            if ($row['status'] == 1) {
                                $status_badge = '<span class="badge bg-success">Thành công</span>';
                            } else if ($row['status'] == 2) {
                                $status_badge = '<span class="badge bg-danger">Thất bại</span>';
                            } else if ($row['status'] == 99) {
                                $status_badge = '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                            } else {
                                $status_badge = '<span class="badge bg-secondary">Đang xử lý</span>';
                            }

                            // Action Buttons
                            $action_btn .= '
                            <div class="d-inline-flex gap-1">
                                <button onclick="handleCard(' . $row['id'] . ', \'auto\')" title="Gửi API" class="btn btn-sm btn-outline-primary px-2"><i class="fas fa-upload"></i></button>
                                <button onclick="openApproveModal(' . $row['id'] . ', ' . $row['amount'] . ')" title="Duyệt" class="btn btn-sm btn-outline-success px-2"><i class="fas fa-check"></i></button>
                                <button onclick="handleCard(' . $row['id'] . ', \'reject\')" title="Hủy Thẻ" class="btn btn-sm btn-outline-warning px-2"><i class="fas fa-times"></i></button>
                                <button onclick="deleteCard(' . $row['id'] . ')" title="Xóa" class="btn btn-sm btn-outline-danger px-2"><i class="fas fa-trash"></i></button>
                            </div>
                            ';
                            ?>
                            <tr data-id="<?= $row['id'] ?>" data-amount="<?= $row['amount'] ?>">
                                <td class="ps-4">
                                    <input type="checkbox" class="card-checkbox form-check-input cursor-pointer" value="<?= $row['id'] ?>" data-amount="<?= $row['amount'] ?>" onclick="updateSelection()">
                                </td>
                                <td class="text-muted font-mono">#<?= $row['id'] ?></td>
                                <td class="font-bold text-dark"><?= $row['id_game'] ?></td>
                                <td><span class="font-bold text-primary font-mono"><?= $row['telco'] ?></span></td>
                                <td>
                                    <div class="text-xs">
                                        <p class="mb-0">Seri: <span class="font-mono text-muted"><?= $row['serial'] ?></span></p>
                                        <p class="mb-0">Pin: <span class="font-mono text-muted"><?= $row['code'] ?></span></p>
                                    </div>
                                </td>
                                <td class="font-bold"><?= number_format($row['amount']) ?>đ</td>
                                <td><?= $status_badge ?></td>
                                <td class="text-muted small"><?= $row['thoigian'] ?></td>
                                <td class="pe-4 text-center"><?= $action_btn ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white border-top">
        <?php
        $params = [];
        if (!empty($search))
            $params[] = 'search=' . urlencode($search);
        if ($status_filter !== '')
            $params[] = 'status=' . urlencode($status_filter);
        $pagination_url = '/admin/cards.php?' . (count($params) > 0 ? implode('&', $params) . '&' : '');
        echo admin_phantrang($pagination_url, $start, $total, $per_page);
        ?>
    </div>
</div>

<!-- Queue Progress Modal -->
<div class="modal fade" id="queueModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="queueModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold text-dark" id="queueModalLabel"><i class="fas fa-tasks mr-2 text-primary"></i>Đang xử lý hàng loạt</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Tiến độ: <span id="queueProgress">0</span>/<span id="queueTotal">0</span></span>
                        <span id="queuePercent">0%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div id="queueProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div id="queueLog" class="bg-light rounded p-3 text-xs font-mono" style="max-height: 200px; overflow-y: auto;">
                    <!-- Logs will appear here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="queueCancelBtn" onclick="cancelQueue()" class="btn btn-danger">
                    <i class="fas fa-stop mr-1"></i> Dừng
                </button>
                <button type="button" id="queueCloseBtn" onclick="closeQueueModal()" class="btn btn-secondary d-none">
                    <i class="fas fa-times mr-1"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold text-dark" id="approveModalLabel"><i class="fas fa-check-circle text-success mr-2"></i>Duyệt thẻ thành công</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Xác nhận duyệt thẻ này? Bạn có thể chỉnh sửa số tiền thực nhận.</p>
                <input type="hidden" id="modal_id">
                <div class="mb-3">
                    <label for="modal_amount" class="form-label font-semibold text-dark">Thực nhận (VND)</label>
                    <input type="number" id="modal_amount" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" onclick="submitApprove()" class="btn btn-success px-4">Duyệt ngay</button>
            </div>
        </div>
    </div>
</div>

<script>
    let approveModal = null;
    let queueModal = null;
    
    $(document).ready(function() {
        approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
        queueModal = new bootstrap.Modal(document.getElementById('queueModal'));
    });

    function openApproveModal(id, amount) {
        document.getElementById('modal_id').value = id;
        document.getElementById('modal_amount').value = amount;
        approveModal.show();
    }

    function closeApproveModal() {
        approveModal.hide();
    }

    // Submit Manual Approve
    function submitApprove() {
        const id = document.getElementById('modal_id').value;
        const thucnhan = document.getElementById('modal_amount').value;

        $.ajax({
            url: '../model/admin/cards/update.php',
            type: 'POST',
            dataType: 'json',
            data: { type: 'approve', id: id, thucnhan: thucnhan },
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

    // Handle Auto Gateway & Reject
    function handleCard(id, type) {
        let text = type === 'auto' ? 'Gửi thẻ sang API gạch thẻ?' : 'Xác nhận HỦY thẻ này?';
        let confirmBtn = type === 'auto' ? 'Gửi ngay' : 'Hủy thẻ';
        let confirmColor = type === 'auto' ? '#0d6efd' : '#ffc107';

        Swal.fire({
            title: 'Xác nhận',
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: confirmBtn,
            confirmButtonColor: confirmColor,
            cancelButtonText: 'Đóng'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../model/admin/cards/update.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { type: type, id: id },
                    success: function (res) {
                        var resp = typeof res === 'object' ? res : JSON.parse(res);
                        if (resp.status === 'success') {
                            Swal.fire({title: 'Thành công', text: resp.msg, icon: 'success', timer: 1500, showConfirmButton: false}).then(() => location.reload());
                        } else {
                            Swal.fire('Lỗi', resp.msg, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Lỗi', 'Lỗi kết nối server', 'error');
                    }
                });
            }
        });
    }

    function deleteCard(id) {
        Swal.fire({
            title: 'Xóa bản ghi?',
            text: "Hành động này không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../model/admin/cards/delete.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id: id },
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

    // ========================================
    // BULK SELECTION & QUEUE FUNCTIONS
    // ========================================

    let selectedCards = [];
    let queueRunning = false;
    let queueCancelled = false;

    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.card-checkbox');

        checkboxes.forEach(cb => {
            cb.checked = selectAllCheckbox.checked;
        });

        updateSelection();
    }

    function updateSelection() {
        const checkboxes = document.querySelectorAll('.card-checkbox:checked');
        selectedCards = [];
        let totalAmount = 0;

        checkboxes.forEach(cb => {
            selectedCards.push({
                id: cb.value,
                amount: parseInt(cb.dataset.amount) || 0
            });
            totalAmount += parseInt(cb.dataset.amount) || 0;
        });

        const bulkBar = document.getElementById('bulkActionBar');
        const countEl = document.getElementById('selectedCount');
        const amountEl = document.getElementById('selectedAmount');

        countEl.textContent = selectedCards.length;
        amountEl.textContent = new Intl.NumberFormat('vi-VN').format(totalAmount) + 'đ';

        if (selectedCards.length > 0) {
            bulkBar.classList.remove('d-none');
        } else {
            bulkBar.classList.add('d-none');
        }

        // Update select all checkbox
        const allCheckboxes = document.querySelectorAll('.card-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length && allCheckboxes.length > 0;
    }

    function clearSelection() {
        const checkboxes = document.querySelectorAll('.card-checkbox');
        checkboxes.forEach(cb => cb.checked = false);
        document.getElementById('selectAll').checked = false;
        updateSelection();
    }

    function bulkAction(actionType) {
        if (selectedCards.length === 0) {
            Swal.fire('Lỗi', 'Vui lòng chọn ít nhất 1 thẻ!', 'error');
            return;
        }

        let confirmText = '';
        let confirmColor = '#0d6efd';

        switch (actionType) {
            case 'approve':
                confirmText = `Duyệt thành công ${selectedCards.length} thẻ?`;
                confirmColor = '#198754';
                break;
            case 'auto':
                confirmText = `Gửi ${selectedCards.length} thẻ qua API gạch thẻ?`;
                confirmColor = '#0d6efd';
                break;
            case 'reject':
                confirmText = `Hủy ${selectedCards.length} thẻ?`;
                confirmColor = '#ffc107';
                break;
            case 'delete':
                confirmText = `Xóa vĩnh viễn ${selectedCards.length} thẻ?`;
                confirmColor = '#dc3545';
                break;
        }

        Swal.fire({
            title: 'Xác nhận thao tác hàng loạt',
            text: confirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Xác nhận',
            confirmButtonColor: confirmColor,
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                startQueue(actionType);
            }
        });
    }

    function startQueue(actionType) {
        queueRunning = true;
        queueCancelled = false;

        // Show modal
        queueModal.show();
        document.getElementById('queueCancelBtn').classList.remove('d-none');
        document.getElementById('queueCloseBtn').classList.add('d-none');
        document.getElementById('queueLog').innerHTML = '';
        document.getElementById('queueTotal').textContent = selectedCards.length;
        document.getElementById('queueProgress').textContent = '0';
        document.getElementById('queuePercent').textContent = '0%';
        document.getElementById('queueProgressBar').style.width = '0%';

        processQueue(actionType, 0);
    }

    async function processQueue(actionType, index) {
        if (queueCancelled || index >= selectedCards.length) {
            finishQueue(index);
            return;
        }

        const card = selectedCards[index];
        const logEl = document.getElementById('queueLog');

        // Add processing log
        const logItem = document.createElement('div');
        logItem.className = 'text-muted';
        logItem.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý #${card.id}...`;
        logEl.appendChild(logItem);
        logEl.scrollTop = logEl.scrollHeight;

        try {
            let url = '../model/admin/cards/update.php';
            let data = { type: actionType, id: card.id };

            if (actionType === 'approve') {
                data.thucnhan = card.amount;
            }

            if (actionType === 'delete') {
                url = '../model/admin/cards/delete.php';
                data = { id: card.id };
            }

            const response = await $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: data,
                timeout: 30000
            });

            var resp = typeof response === 'object' ? response : JSON.parse(response);
            if (resp.status === 'success') {
                logItem.className = 'text-success font-semibold';
                logItem.innerHTML = `<i class="fas fa-check mr-1"></i> #${card.id}: ${resp.msg}`;
            } else {
                logItem.className = 'text-danger font-semibold';
                logItem.innerHTML = `<i class="fas fa-times mr-1"></i> #${card.id}: ${resp.msg || 'Lỗi'}`;
            }
        } catch (error) {
            logItem.className = 'text-danger font-semibold';
            let errorMsg = 'Lỗi kết nối';
            if (error.responseText) {
                try {
                    const errJson = JSON.parse(error.responseText);
                    errorMsg = errJson.msg || error.responseText.substring(0, 100);
                } catch(e) {
                    errorMsg = error.responseText.substring(0, 100);
                }
            } else if (error.statusText) {
                errorMsg = `${error.status}: ${error.statusText}`;
            }
            logItem.innerHTML = `<i class="fas fa-exclamation-triangle mr-1"></i> #${card.id}: ${errorMsg}`;
        }

        // Update progress
        const progress = index + 1;
        const percent = Math.round((progress / selectedCards.length) * 100);
        document.getElementById('queueProgress').textContent = progress;
        document.getElementById('queuePercent').textContent = percent + '%';
        document.getElementById('queueProgressBar').style.width = percent + '%';

        logEl.scrollTop = logEl.scrollHeight;

        // Small delay between requests to avoid overwhelming server
        await new Promise(resolve => setTimeout(resolve, 300));

        // Process next
        processQueue(actionType, index + 1);
    }

    function finishQueue(processed) {
        queueRunning = false;

        document.getElementById('queueCancelBtn').classList.add('d-none');
        document.getElementById('queueCloseBtn').classList.remove('d-none');

        const logEl = document.getElementById('queueLog');
        const finishLog = document.createElement('div');
        finishLog.className = 'text-primary font-bold mt-2 pt-2 border-top border-light-subtle';

        if (queueCancelled) {
            finishLog.innerHTML = `<i class="fas fa-stop-circle mr-1"></i> Đã dừng! Xử lý được ${processed}/${selectedCards.length} thẻ.`;
        } else {
            finishLog.innerHTML = `<i class="fas fa-check-circle mr-1"></i> Hoàn tất! Đã xử lý ${processed} thẻ.`;
        }

        logEl.appendChild(finishLog);
        logEl.scrollTop = logEl.scrollHeight;
    }

    function cancelQueue() {
        queueCancelled = true;
        const logEl = document.getElementById('queueLog');
        const cancelLog = document.createElement('div');
        cancelLog.className = 'text-warning font-semibold';
        cancelLog.innerHTML = '<i class="fas fa-hand-paper mr-1"></i> Đang dừng...';
        logEl.appendChild(cancelLog);
    }

    function closeQueueModal() {
        queueModal.hide();
        location.reload();
    }
</script>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');

    const chartLabels = <?= $chartLabels ?>;
    const chartAmounts = <?= $chartAmounts ?>;
    const chartReceived = <?= $chartReceived ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Tổng nạp',
                    data: chartAmounts,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
                {
                    label: 'Thực nhận',
                    data: chartReceived,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + 'đ';
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(0) + 'K';
                            }
                            return value;
                        }
                    }
                }
            }
        }
    });
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>