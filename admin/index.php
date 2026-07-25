<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Helper function for cards using AdminLTE 4 info boxes
// Helper function for dashboard cards with gradient backgrounds matching the second row
function dashboard_card($title, $value, $gradient, $icon, $subtitle = '')
{
    $subtitleHtml = $subtitle ? '<small class="opacity-75 d-block mt-1" style="font-size: 11px;">' . $subtitle . '</small>' : '';
    return '
    <div class="card border-0 text-white shadow-sm h-100" style="background: ' . $gradient . ';">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div>
                <p class="mb-1 small opacity-75">' . $title . '</p>
                <h4 class="mb-1 font-bold">' . $value . '</h4>
                ' . $subtitleHtml . '
            </div>
            <i class="' . $icon . ' fs-1 opacity-50"></i>
        </div>
    </div>';
}

// ========================================
// THỐNG KÊ TỔNG HỢP (CHỈ STATUS = 1 THÀNH CÔNG)
// ========================================

// Tổng thành viên
$total_users = $CMSNT->num_rows("SELECT 1 FROM `users`") ?: 0;

// Thẻ chờ duyệt (status = 0 hoặc 99)
$pending_cards = $CMSNT->num_rows("SELECT 1 FROM `napthe` WHERE `status` IN (0, 99)") ?: 0;

// Tổng mệnh giá (chỉ thẻ thành công status = 1)
$total_amount_row = $CMSNT->get_row("SELECT SUM(amount) as total FROM `napthe` WHERE `status` = 1");
$total_amount = $total_amount_row['total'] ?: 0;

// Tổng thực nhận (chỉ thẻ thành công status = 1)
$total_received_row = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE `status` = 1");
$total_received = $total_received_row['total'] ?: 0;

// Doanh thu hôm nay
$today = date('Y-m-d');
$today_amount_row = $CMSNT->get_row("SELECT SUM(amount) as total FROM `napthe` WHERE `status` = 1 AND DATE(`thoigian`) = '$today'");
$today_amount = $today_amount_row['total'] ?: 0;

$today_received_row = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE `status` = 1 AND DATE(`thoigian`) = '$today'");
$today_received = $today_received_row['total'] ?: 0;

// Doanh thu tháng này
$current_month = date('Y-m');
$month_amount_row = $CMSNT->get_row("SELECT SUM(amount) as total FROM `napthe` WHERE `status` = 1 AND DATE_FORMAT(`thoigian`, '%Y-%m') = '$current_month'");
$month_amount = $month_amount_row['total'] ?: 0;

$month_received_row = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE `status` = 1 AND DATE_FORMAT(`thoigian`, '%Y-%m') = '$current_month'");
$month_received = $month_received_row['total'] ?: 0;

// Số thẻ thành công hôm nay
$today_success_cards = $CMSNT->num_rows("SELECT 1 FROM `napthe` WHERE `status` = 1 AND DATE(`thoigian`) = '$today'") ?: 0;

// ========================================
// DỮ LIỆU BIỂU ĐỒ THEO NGÀY - 14 NGÀY GẦN NHẤT
// ========================================

$chartDays = 14;
$dailyChartData = [];

for ($i = $chartDays - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $displayDate = date('d/m', strtotime("-$i days"));

    // Tổng mệnh giá trong ngày (chỉ thẻ thành công)
    $dayAmountRow = $CMSNT->get_row("SELECT SUM(amount) as total FROM `napthe` WHERE DATE(`thoigian`) = '$date' AND `status` = 1");
    $dayAmount = $dayAmountRow['total'] ?: 0;

    // Thực nhận trong ngày (chỉ thẻ thành công)
    $dayReceivedRow = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE DATE(`thoigian`) = '$date' AND `status` = 1");
    $dayReceived = $dayReceivedRow['total'] ?: 0;

    $dailyChartData[] = [
        'date' => $displayDate,
        'amount' => (int) $dayAmount,
        'received' => (int) $dayReceived
    ];
}

$dailyLabels = json_encode(array_column($dailyChartData, 'date'));
$dailyAmounts = json_encode(array_column($dailyChartData, 'amount'));
$dailyReceived = json_encode(array_column($dailyChartData, 'received'));

// ========================================
// DỮ LIỆU BIỂU ĐỒ THEO THÁNG - 6 THÁNG GẦN NHẤT
// ========================================

$monthlyChartData = [];

for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $displayMonth = date('m/Y', strtotime("-$i months"));

    // Tổng mệnh giá trong tháng (chỉ thẻ thành công)
    $monthAmountRow = $CMSNT->get_row("SELECT SUM(amount) as total FROM `napthe` WHERE DATE_FORMAT(`thoigian`, '%Y-%m') = '$monthDate' AND `status` = 1");
    $monthAmountVal = $monthAmountRow['total'] ?: 0;

    // Thực nhận trong tháng (chỉ thẻ thành công)
    $monthReceivedRow = $CMSNT->get_row("SELECT SUM(thucnhan) as total FROM `napthe` WHERE DATE_FORMAT(`thoigian`, '%Y-%m') = '$monthDate' AND `status` = 1");
    $monthReceivedVal = $monthReceivedRow['total'] ?: 0;

    $monthlyChartData[] = [
        'date' => $displayMonth,
        'amount' => (int) $monthAmountVal,
        'received' => (int) $monthReceivedVal
    ];
}

$monthlyLabels = json_encode(array_column($monthlyChartData, 'date'));
$monthlyAmounts = json_encode(array_column($monthlyChartData, 'amount'));
$monthlyReceived = json_encode(array_column($monthlyChartData, 'received'));
?>

<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <?= dashboard_card("Tổng thành viên", number_format($total_users), "linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%)", "fas fa-users") ?>
    </div>
    <div class="col-6 col-md-3">
        <?= dashboard_card("Thẻ chờ duyệt", number_format($pending_cards), "linear-gradient(135deg, #d97706 0%, #f59e0b 100%)", "fas fa-clock") ?>
    </div>
    <div class="col-6 col-md-3">
        <?= dashboard_card("Tổng mệnh giá", number_format($total_amount) . 'đ', "linear-gradient(135deg, #059669 0%, #10b981 100%)", "fas fa-wallet", "Chỉ thẻ thành công") ?>
    </div>
    <div class="col-6 col-md-3">
        <?= dashboard_card("Tổng thực nhận", number_format($total_received) . 'đ', "linear-gradient(135deg, #6d28d9 0%, #8b5cf6 100%)", "fas fa-hand-holding-usd", "Chỉ thẻ thành công") ?>
    </div>
</div>

<!-- Today & Month Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 text-white shadow-sm h-100" style="background: linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%);">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 small opacity-75">Mệnh giá hôm nay</p>
                    <h4 class="mb-1 font-bold"><?= number_format($today_amount) ?>đ</h4>
                    <small class="opacity-75"><?= number_format($today_success_cards) ?> thẻ thành công</small>
                </div>
                <i class="fas fa-calendar-day fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-white shadow-sm h-100" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 small opacity-75">Thực nhận hôm nay</p>
                    <h4 class="mb-1 font-bold"><?= number_format($today_received) ?>đ</h4>
                    <small class="opacity-75"><?= date('d/m/Y') ?></small>
                </div>
                <i class="fas fa-coins fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-white shadow-sm h-100" style="background: linear-gradient(135deg, #a855f7 0%, #4f46e5 100%);">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 small opacity-75">Mệnh giá tháng <?= date('m') ?></p>
                    <h4 class="mb-1 font-bold"><?= number_format($month_amount) ?>đ</h4>
                    <small class="opacity-75">Tháng <?= date('m/Y') ?></small>
                </div>
                <i class="fas fa-chart-bar fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 text-white shadow-sm h-100" style="background: linear-gradient(135deg, #ec4899 0%, #e11d48 100%);">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 small opacity-75">Thực nhận tháng <?= date('m') ?></p>
                    <h4 class="mb-1 font-bold"><?= number_format($month_received) ?>đ</h4>
                    <small class="opacity-75">Tháng <?= date('m/Y') ?></small>
                </div>
                <i class="fas fa-piggy-bank fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<!-- Charts Section -->
<div class="row g-4 mb-4">
    <!-- Daily Chart -->
    <div class="col-12 col-lg-6 d-flex flex-column">
        <div class="card shadow-sm h-100 d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-chart-line mr-2 text-primary"></i>Doanh thu theo ngày
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary text-white">Mệnh giá</span>
                    <span class="badge bg-success text-white">Thực nhận</span>
                </div>
            </div>
            <div class="card-body p-3 flex-grow-1">
                <canvas id="dailyChart" height="150"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Chart -->
    <div class="col-12 col-lg-6 d-flex flex-column">
        <div class="card shadow-sm h-100 d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-chart-bar mr-2 text-indigo text-primary"></i>Doanh thu theo tháng
                </h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-info text-white">Mệnh giá</span>
                    <span class="badge bg-danger text-white">Thực nhận</span>
                </div>
            </div>
            <div class="card-body p-3 flex-grow-1">
                <canvas id="monthlyChart" height="150"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Tables -->
<div class="row g-4 mb-4">
    <!-- Recent Cards Table -->
    <div class="col-12 col-lg-6 d-flex flex-column">
        <div class="card shadow-sm h-100 d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-credit-card mr-2 text-success"></i>Nạp Thẻ Gần Đây
                </h5>
                <a href="<?= base_url('admin/cards.php') ?>" class="btn btn-sm btn-outline-primary">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="card-body p-0 flex-grow-1" style="height: 380px; max-height: 380px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th>User</th>
                                <th>Thẻ</th>
                                <th>Mệnh giá</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            $recent_cards = $CMSNT->get_list("SELECT * FROM `napthe` ORDER BY `id` DESC LIMIT 8");

                            if ($recent_cards) {
                                foreach ($recent_cards as $row) {
                                    $status_badge = '';
                                    if ($row['status'] == 1)
                                        $status_badge = '<span class="badge bg-success">OK</span>';
                                    else if ($row['status'] == 2)
                                        $status_badge = '<span class="badge bg-danger">Lỗi</span>';
                                    else if ($row['status'] == 99 || $row['status'] == 0)
                                        $status_badge = '<span class="badge bg-warning text-dark">Chờ</span>';
                                    else
                                        $status_badge = '<span class="badge bg-secondary">...</span>';
                                    ?>
                                    <tr>
                                        <td class="font-bold text-dark"><?= $row['id_game'] ?? $row['username'] ?></td>
                                        <td><span class="font-mono text-primary font-bold"><?= $row['telco'] ?></span></td>
                                        <td class="font-bold"><?= number_format($row['amount']) ?>đ</td>
                                        <td><?= $status_badge ?></td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="4" class="p-4 text-center text-muted">Chưa có giao dịch nào.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- System Logs -->
    <div class="col-12 col-lg-6 d-flex flex-column">
        <div class="card shadow-sm h-100 d-flex flex-column">
            <div class="card-header d-flex justify-content-between align-items-center py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-clipboard-list mr-2 text-primary"></i>Nhật Ký Hệ Thống
                </h5>
                <button onclick="clearLogs()" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash-alt mr-1"></i> Xóa
                </button>
            </div>
            <div class="card-body p-0 flex-grow-1" style="height: 380px; max-height: 380px; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th>Nội dung</th>
                                <th style="width: 120px;">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            try {
                                $logs = $CMSNT->get_list("SELECT * FROM `logs` ORDER BY `id` DESC LIMIT 10");

                                if ($logs) {
                                    foreach ($logs as $log) {
                                        ?>
                                        <tr>
                                            <td>
                                                <p class="text-dark mb-0 font-semibold"><?= $log['content'] ?></p>
                                                <span class="text-xs text-muted font-mono"><?= $log['ip'] ?></span>
                                            </td>
                                            <td class="text-muted"><?= date('H:i d/m', strtotime($log['createdate'])) ?></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="2" class="p-4 text-center text-muted">Chưa có nhật ký.</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="2" class="p-4 text-center text-danger">Chưa tạo bảng logs.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
    // Daily Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?= $dailyLabels ?>,
            datasets: [
                {
                    label: 'Mệnh giá',
                    data: <?= $dailyAmounts ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                },
                {
                    label: 'Thực nhận',
                    data: <?= $dailyReceived ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + 'đ';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                            return value;
                        }
                    }
                }
            }
        }
    });

    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: <?= $monthlyLabels ?>,
            datasets: [
                {
                    label: 'Mệnh giá',
                    data: <?= $monthlyAmounts ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: 'rgb(139, 92, 246)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Thực nhận',
                    data: <?= $monthlyReceived ?>,
                    backgroundColor: 'rgba(236, 72, 153, 0.8)',
                    borderColor: 'rgb(236, 72, 153)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return context.dataset.label + ': ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + 'đ';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                            return value;
                        }
                    }
                }
            }
        }
    });

    function clearLogs() {
        Swal.fire({
            title: 'Xóa toàn bộ logs?',
            text: "Bạn không thể hoàn tác hành động này!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Vâng, xóa hết!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= base_url('model/admin/logs/truncate_logs.php') ?>',
                    type: 'POST',
                    success: function (response) {
                        const res = typeof response === 'object' ? response : JSON.parse(response);
                        if (res.status === 'success') {
                            Swal.fire('Đã xóa!', res.msg, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Lỗi', res.msg, 'error');
                        }
                    }
                });
            }
        })
    }
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>