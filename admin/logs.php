<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// ============================================================
// POST Actions - Delete single log / Truncate all
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_log') {
        $log_id = (int) ($_POST['id'] ?? 0);
        if ($log_id > 0) {
            $CMSNT->remove('logs', "id = $log_id");
            admin_log("Xóa log #$log_id");
            admin_msg_success('Đã xóa log thành công!', '/admin/logs.php?' . http_build_query($_GET), 1200);
        } else {
            admin_msg_error('ID log không hợp lệ!', '/admin/logs.php', 1500);
        }
        exit;
    }

    if ($action === 'truncate_logs') {
        $CMSNT->query("TRUNCATE TABLE `logs`");
        admin_log("Xóa toàn bộ system logs");
        admin_msg_success('Đã xóa toàn bộ logs!', '/admin/logs.php', 1200);
        exit;
    }
}

// ============================================================
// GET Filters & Pagination
// ============================================================
$search     = check_string($_GET['search'] ?? '');
$filter_ip  = check_string($_GET['ip'] ?? '');
$date_from  = check_string($_GET['date_from'] ?? '');
$date_to    = check_string($_GET['date_to'] ?? '');
$per_page   = 30;
$page       = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$start      = ($page - 1) * $per_page;

// Build WHERE clause
$where = "1=1";
if (!empty($search)) {
    $s = $CMSNT->escape($search);
    $where .= " AND (`content` LIKE '%$s%')";
}
if (!empty($filter_ip)) {
    $ip = $CMSNT->escape($filter_ip);
    $where .= " AND (`ip` LIKE '%$ip%')";
}
if (!empty($date_from)) {
    $where .= " AND (`createdate` >= '" . $CMSNT->escape($date_from) . " 00:00:00')";
}
if (!empty($date_to)) {
    $where .= " AND (`createdate` <= '" . $CMSNT->escape($date_to) . " 23:59:59')";
}

// ============================================================
// Queries
// ============================================================
$total_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `logs` WHERE $where");
$total     = $total_row ? (int) $total_row['total'] : 0;

$logs = $CMSNT->get_list("
    SELECT l.*, u.username 
    FROM `logs` l 
    LEFT JOIN `users` u ON l.user_id = u.id 
    WHERE $where 
    ORDER BY l.id DESC 
    LIMIT $start, $per_page
");

// Stats
$stat_total = $CMSNT->get_row("SELECT COUNT(*) as c FROM `logs`");
$stat_total = $stat_total ? (int) $stat_total['c'] : 0;

$today = date('Y-m-d');
$stat_today = $CMSNT->get_row("SELECT COUNT(*) as c FROM `logs` WHERE DATE(`createdate`) = '$today'");
$stat_today = $stat_today ? (int) $stat_today['c'] : 0;

$stat_ips = $CMSNT->get_row("SELECT COUNT(DISTINCT `ip`) as c FROM `logs`");
$stat_ips = $stat_ips ? (int) $stat_ips['c'] : 0;

$hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
$stat_recent = $CMSNT->get_row("SELECT COUNT(*) as c FROM `logs` WHERE `createdate` >= '$hour_ago'");
$stat_recent = $stat_recent ? (int) $stat_recent['c'] : 0;

// Build pagination URL
$params = [];
if (!empty($search))    $params[] = 'search=' . urlencode($search);
if (!empty($filter_ip))  $params[] = 'ip=' . urlencode($filter_ip);
if (!empty($date_from))  $params[] = 'date_from=' . urlencode($date_from);
if (!empty($date_to))    $params[] = 'date_to=' . urlencode($date_to);
$pagination_url = '/admin/logs.php?' . (count($params) > 0 ? implode('&', $params) . '&' : '');
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle me-3" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="fas fa-list-alt fs-5"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">Tổng Logs</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($stat_total) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle me-3" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="fas fa-calendar-day fs-5"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">Hôm Nay</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($stat_today) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center bg-info-subtle text-info rounded-circle me-3" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="fas fa-network-wired fs-5"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">IP Duy Nhất</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($stat_ips) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-circle me-3" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="fas fa-bolt fs-5"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">1 Giờ Qua</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($stat_recent) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Logs Table Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-scroll text-primary me-2"></i>
                Nhật Ký Hệ Thống
                <?php if ($total > 0): ?>
                    <span class="badge bg-primary ms-2"><?= number_format($total) ?> kết quả</span>
                <?php endif; ?>
            </h5>
            <div class="d-flex gap-2">
                <button type="button" onclick="truncateAllLogs()" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash-alt me-1"></i> Xóa tất cả
                </button>
                <button type="button" onclick="exportLogs()" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-file-export me-1"></i> Xuất CSV
                </button>
            </div>
        </div>

        <!-- Search & Filter Form -->
        <form method="GET" class="d-flex flex-wrap gap-2 mt-3 mb-0">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Tìm nội dung..." class="form-control form-control-sm" style="max-width: 200px;">
            <input type="text" name="ip" value="<?= htmlspecialchars($filter_ip) ?>"
                placeholder="Lọc theo IP..." class="form-control form-control-sm" style="max-width: 150px;">
            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                class="form-control form-control-sm" style="max-width: 155px;" title="Từ ngày">
            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                class="form-control form-control-sm" style="max-width: 155px;" title="Đến ngày">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-search"></i>
            </button>
            <?php if (!empty($search) || !empty($filter_ip) || !empty($date_from) || !empty($date_to)): ?>
                <a href="/admin/logs.php" class="btn btn-sm btn-secondary" title="Xóa bộ lọc">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap" id="logsTable">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4" style="width: 70px;">ID</th>
                        <th style="width: 120px;">Thời gian</th>
                        <th>Nội dung</th>
                        <th style="width: 120px;">Người dùng</th>
                        <th style="width: 130px;">IP</th>
                        <th class="text-center pe-4" style="width: 80px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php if ($logs && count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr id="log-row-<?= $log['id'] ?>">
                                <td class="ps-4 text-muted font-mono">#<?= $log['id'] ?></td>
                                <td>
                                    <span class="text-muted" title="<?= $log['createdate'] ?>">
                                        <?= date('H:i:s', strtotime($log['createdate'])) ?>
                                        <br>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($log['createdate'])) ?></small>
                                    </span>
                                </td>
                                <td style="white-space: normal; max-width: 400px;">
                                    <span class="text-dark"><?= htmlspecialchars($log['content'] ?? '') ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($log['username'])): ?>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($log['username']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['ip'])): ?>
                                        <code class="text-danger"><?= htmlspecialchars($log['ip']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                        onclick="deleteLog(<?= $log['id'] ?>)" title="Xóa log này">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-5 text-center">
                                <div class="text-muted">
                                    <i class="fas fa-inbox text-4xl mb-3 d-block opacity-50"></i>
                                    <p class="mb-0 font-semibold">Không có dữ liệu log nào</p>
                                    <?php if (!empty($search) || !empty($filter_ip) || !empty($date_from)): ?>
                                        <small>Thử thay đổi bộ lọc hoặc <a href="/admin/logs.php">xem tất cả</a></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total > $per_page): ?>
        <div class="card-footer bg-white border-top">
            <?php echo admin_phantrang($pagination_url, $start, $total, $per_page); ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Delete single log
function deleteLog(id) {
    Swal.fire({
        title: 'Xóa log #' + id + '?',
        text: 'Bạn có chắc chắn muốn xóa log này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/logs.php?' + new URLSearchParams(window.location.search).toString(),
                type: 'POST',
                data: { action: 'delete_log', id: id },
                success: function () {
                    // Animate row removal
                    const row = document.getElementById('log-row-' + id);
                    if (row) {
                        row.style.transition = 'opacity 0.3s, transform 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => row.remove(), 300);
                    }
                    Swal.fire({
                        title: 'Đã xóa!',
                        text: 'Log #' + id + ' đã được xóa.',
                        icon: 'success',
                        timer: 1200,
                        showConfirmButton: false
                    });
                },
                error: function () {
                    Swal.fire('Lỗi', 'Không thể xóa log. Thử lại sau.', 'error');
                }
            });
        }
    });
}

// Truncate all logs
function truncateAllLogs() {
    Swal.fire({
        title: 'Xóa toàn bộ logs?',
        html: '<p class="text-danger mb-0">Hành động này không thể hoàn tác!</p><p class="small text-muted">Tất cả nhật ký hệ thống sẽ bị xóa vĩnh viễn.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Xóa tất cả',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/logs.php';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = 'truncate_logs';
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Export logs to CSV
function exportLogs() {
    const table = document.getElementById('logsTable');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    if (rows.length === 0 || (rows.length === 1 && rows[0].querySelector('td[colspan]'))) {
        Swal.fire('Thông báo', 'Không có dữ liệu để xuất.', 'info');
        return;
    }

    let csv = '\uFEFF'; // BOM for UTF-8
    csv += 'ID,Thời gian,Nội dung,Người dùng,IP\n';

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 5) {
            const id = cells[0].textContent.trim();
            const time = cells[1].textContent.trim().replace(/\s+/g, ' ');
            const content = '"' + cells[2].textContent.trim().replace(/"/g, '""') + '"';
            const user = cells[3].textContent.trim();
            const ip = cells[4].textContent.trim();
            csv += `${id},${time},${content},${user},${ip}\n`;
        }
    });

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'system_logs_' + new Date().toISOString().slice(0, 10) + '.csv';
    link.click();
    URL.revokeObjectURL(link.href);

    Swal.fire({
        title: 'Đã xuất!',
        text: 'File CSV đã được tải xuống.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
    });
}
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>
