<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Xử lý xóa log
if (isset($_POST['delete_log'])) {
    $logId = (int) $_POST['log_id'];
    $CMSNT->remove('spam_log', "`id` = $logId");
    admin_log("Đã xóa spam log #$logId");
    echo '<script>Swal.fire("Thành công", "Đã xóa log!", "success");</script>';
}

// Xử lý thêm IP vào blacklist
if (isset($_POST['blacklist_ip'])) {
    $ip = check_string($_POST['ip_address']);
    $reason = check_string($_POST['reason'] ?? 'Spam nạp thẻ');

    $existing = $CMSNT->get_row("SELECT * FROM `ip_blacklist` WHERE `ip_address` = '$ip'");
    if (!$existing) {
        $CMSNT->insert('ip_blacklist', [
            'ip_address' => $ip,
            'reason' => $reason,
            'blocked_by' => $_SESSION['username'] ?? 'admin',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        admin_log("Đã thêm IP $ip vào blacklist");
        echo '<script>Swal.fire("Thành công", "Đã thêm IP vào danh sách đen!", "success");</script>';
    } else {
        echo '<script>Swal.fire("Lỗi", "IP này đã có trong blacklist!", "error");</script>';
    }
}

// Xử lý xóa IP khỏi blacklist
if (isset($_POST['unblock_ip'])) {
    $ip = check_string($_POST['ip_address']);
    $CMSNT->remove('ip_blacklist', "`ip_address` = '$ip'");
    admin_log("Đã xóa IP $ip khỏi blacklist");
    echo '<script>Swal.fire("Thành công", "Đã gỡ block IP!", "success");</script>';
}

// Xử lý xóa tất cả log cũ
if (isset($_POST['cleanup_logs'])) {
    $days = (int) $_POST['days'] ?: 7;
    $CMSNT->query("DELETE FROM `spam_log` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL $days DAY)");
    admin_log("Đã dọn dẹp spam log cũ hơn $days ngày");
    echo '<script>Swal.fire("Thành công", "Đã dọn dẹp log cũ!", "success");</script>';
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$search = check_string($_GET['search'] ?? '');

$whereClause = "1=1";
if ($filter === 'blocked') {
    $whereClause .= " AND `is_blocked` = 1";
} elseif ($filter === 'allowed') {
    $whereClause .= " AND `is_blocked` = 0";
}

if (!empty($search)) {
    $whereClause .= " AND (`ip_address` LIKE '%$search%' OR `user_id` LIKE '%$search%')";
}

// Phân trang Spam Logs
$per_page_spam = 30;
$page_spam = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$start_spam = ($page_spam - 1) * $per_page_spam;

$total_spam_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `spam_log` WHERE $whereClause");
$total_spam = $total_spam_row ? (int) $total_spam_row['total'] : 0;
$logs = $CMSNT->get_list("SELECT * FROM `spam_log` WHERE $whereClause ORDER BY `id` DESC LIMIT $start_spam, $per_page_spam");

// Phân trang Blacklist
$per_page_bl = 20;
$page_bl = isset($_GET['page_bl']) ? max(1, (int) $_GET['page_bl']) : 1;
$start_bl = ($page_bl - 1) * $per_page_bl;

$total_bl_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `ip_blacklist`");
$total_bl = $total_bl_row ? (int) $total_bl_row['total'] : 0;
$blacklist = $CMSNT->get_list("SELECT * FROM `ip_blacklist` ORDER BY `created_at` DESC LIMIT $start_bl, $per_page_bl");

// Build pagination URLs
$spam_params = [];
if (!empty($filter) && $filter !== 'all') $spam_params[] = 'filter=' . urlencode($filter);
if (!empty($search)) $spam_params[] = 'search=' . urlencode($search);
$spam_pagination_url = '/admin/spam_logs.php?' . (count($spam_params) > 0 ? implode('&', $spam_params) . '&' : '');
$bl_pagination_url = '/admin/spam_logs.php?' . (count($spam_params) > 0 ? implode('&', $spam_params) . '&' : '') . '#content-blacklist&';

// Thống kê
$totalLogs = $CMSNT->num_rows("SELECT 1 FROM `spam_log`") ?: 0;
$blockedCount = $CMSNT->num_rows("SELECT 1 FROM `spam_log` WHERE `is_blocked` = 1") ?: 0;
$todayCount = $CMSNT->num_rows("SELECT 1 FROM `spam_log` WHERE DATE(`created_at`) = CURDATE()") ?: 0;
$blacklistCount = $CMSNT->num_rows("SELECT 1 FROM `ip_blacklist`") ?: 0;
?>

<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle me-3" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="fas fa-list-alt fs-5"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">Tổng Log</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($totalLogs) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle me-3" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="fas fa-ban fs-5"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">Bị Block</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($blockedCount) ?></h4>
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
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">Hôm nay</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($todayCount) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="d-flex align-items-center justify-content-center bg-dark-subtle text-dark-emphasis rounded-circle me-3" style="width: 48px; height: 48px; min-width: 48px;">
                    <i class="fas fa-skull-crossbones fs-5"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase fw-semibold">Blacklist</p>
                    <h4 class="mb-0 fw-bold"><?= number_format($blacklistCount) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Card -->
<div class="card shadow-sm mb-4">
    <div class="card-header p-0 bg-light border-bottom">
        <ul class="nav nav-tabs card-header-tabs m-0 border-0" id="spamTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active font-bold py-3 px-4 border-0" id="tab-logs" data-bs-toggle="tab" data-bs-target="#content-logs" type="button" role="tab" aria-controls="content-logs" aria-selected="true">
                    <i class="fas fa-history mr-2"></i>Nhật ký Spam
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link font-bold py-3 px-4 border-0" id="tab-blacklist" data-bs-toggle="tab" data-bs-target="#content-blacklist" type="button" role="tab" aria-controls="content-blacklist" aria-selected="false">
                    <i class="fas fa-user-slash mr-2"></i>IP Blacklist
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link font-bold py-3 px-4 border-0" id="tab-settings" data-bs-toggle="tab" data-bs-target="#content-settings" type="button" role="tab" aria-controls="content-settings" aria-selected="false">
                    <i class="fas fa-cog mr-2"></i>Cài đặt
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="spamTabsContent">
            
            <!-- Tab: Logs -->
            <div class="tab-pane fade show active" id="content-logs" role="tabpanel" aria-labelledby="tab-logs">
                <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="d-inline-flex gap-1">
                        <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">Tất cả</a>
                        <a href="?filter=blocked" class="btn btn-sm <?= $filter === 'blocked' ? 'btn-danger' : 'btn-outline-danger' ?>">Bị Block</a>
                        <a href="?filter=allowed" class="btn btn-sm <?= $filter === 'allowed' ? 'btn-success' : 'btn-outline-success' ?>">Cho phép</a>
                    </div>
                    <form method="GET" class="d-inline-flex gap-1 mb-0">
                        <input type="hidden" name="filter" value="<?= $filter ?>">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm IP hoặc User..." class="form-control form-control-sm">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th class="ps-4">ID</th>
                                <th>IP Address</th>
                                <th>User</th>
                                <th>Hành động</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Lần thử</th>
                                <th>Thời gian</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if ($logs): ?>
                                <?php foreach ($logs as $row): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-mono">#<?= $row['id'] ?></td>
                                        <td class="font-mono text-primary font-bold"><?= $row['ip_address'] ?></td>
                                        <td>
                                            <?php if ($row['user_id']): ?>
                                                <span class="text-dark font-bold"><?= $row['user_id'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted italic">Guest</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-purple text-uppercase"><?= $row['action_type'] ?></span>
                                            <?php if ($row['telco']): ?>
                                                <span class="badge bg-secondary font-mono ml-1"><?= $row['telco'] ?> - <?= number_format($row['amount']) ?>đ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row['is_blocked']): ?>
                                                <span class="badge bg-danger"><i class="fas fa-times-circle mr-1"></i>BLOCKED</span>
                                                <?php if ($row['block_reason']): ?>
                                                    <p class="text-xs text-muted mb-0 mt-1" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['block_reason']) ?>">
                                                        <?= $row['block_reason'] ?>
                                                    </p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle mr-1"></i>OK</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary font-mono"><?= $row['attempt_count'] ?></span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('d/m/Y H:i:s', strtotime($row['created_at'])) ?>
                                            <?php if ($row['expires_at']): ?>
                                                <br><span class="text-danger">Hết block: <?= date('H:i d/m', strtotime($row['expires_at'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="d-inline-flex gap-1">
                                                <form method="POST" onsubmit="return confirm('Thêm IP này vào blacklist?')" class="mb-0">
                                                    <input type="hidden" name="ip_address" value="<?= $row['ip_address'] ?>">
                                                    <input type="hidden" name="reason" value="Spam từ log #<?= $row['id'] ?>">
                                                    <button type="submit" name="blacklist_ip" class="btn btn-sm btn-outline-dark px-2" title="Thêm vào Blacklist">
                                                        <i class="fas fa-skull"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Xóa log này?')" class="mb-0">
                                                    <input type="hidden" name="log_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" name="delete_log" class="btn btn-sm btn-outline-danger px-2" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="p-4 text-center text-muted">Chưa có dữ liệu spam nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_spam > $per_page_spam): ?>
                <div class="card-footer bg-white border-top">
                    <?= admin_phantrang($spam_pagination_url, $start_spam, $total_spam, $per_page_spam) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Blacklist -->
            <div class="tab-pane fade" id="content-blacklist" role="tabpanel" aria-labelledby="tab-blacklist">
                <div class="p-3 bg-light border-bottom">
                    <form method="POST" class="row g-2 align-items-end mb-0">
                        <div class="col-12 col-md-3">
                            <label class="form-label font-semibold small text-dark mb-1">IP Address</label>
                            <input type="text" name="ip_address" required placeholder="192.168.1.1" class="form-control form-control-sm font-mono">
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label font-semibold small text-dark mb-1">Lý do</label>
                            <input type="text" name="reason" placeholder="Spam nạp thẻ" class="form-control form-control-sm">
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" name="blacklist_ip" class="btn btn-sm btn-danger w-100">
                                <i class="fas fa-plus mr-1"></i> Thêm vào Blacklist
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th class="ps-4">ID</th>
                                <th>IP Address</th>
                                <th>Lý do</th>
                                <th>Blocked by</th>
                                <th>Thời gian</th>
                                <th>Hết hạn</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if ($blacklist): ?>
                                <?php foreach ($blacklist as $row): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-mono">#<?= $row['id'] ?></td>
                                        <td class="font-mono text-danger font-bold"><?= $row['ip_address'] ?></td>
                                        <td><?= $row['reason'] ?: '<span class="text-muted">-</span>' ?></td>
                                        <td class="text-muted"><?= $row['blocked_by'] ?></td>
                                        <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <?php if ($row['expires_at']): ?>
                                                <span class="text-warning text-xs font-semibold"><?= date('d/m/Y H:i', strtotime($row['expires_at'])) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">VĨNH VIỄN</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <form method="POST" onsubmit="return confirm('Gỡ block IP này?')" class="mb-0">
                                                <input type="hidden" name="ip_address" value="<?= $row['ip_address'] ?>">
                                                <button type="submit" name="unblock_ip" class="btn btn-sm btn-outline-success px-3">
                                                    <i class="fas fa-unlock mr-1"></i> Gỡ Block
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="p-4 text-center text-muted">Chưa có IP nào bị block.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_bl > $per_page_bl): ?>
                <div class="card-footer bg-white border-top">
                    <?= admin_phantrang($bl_pagination_url, $start_bl, $total_bl, $per_page_bl, 'page_bl') ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Settings -->
            <div class="tab-pane fade p-4" id="content-settings" role="tabpanel" aria-labelledby="tab-settings">
                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <h6 class="font-bold text-dark mb-2"><i class="fas fa-broom mr-2 text-warning"></i> Dọn dẹp Log</h6>
                        <div class="card p-3 bg-light border-0">
                            <p class="text-xs text-muted mb-3">Xóa tất cả log spam cũ hơn số ngày đã chọn để giải phóng dung lượng database.</p>
                            <form method="POST" onsubmit="return confirm('Xác nhận xóa tất cả log cũ?')" class="mb-0">
                                <div class="d-flex gap-2">
                                    <select name="days" class="form-select form-select-sm" style="max-width: 140px;">
                                        <option value="7">7 ngày</option>
                                        <option value="14">14 ngày</option>
                                        <option value="30" selected>30 ngày</option>
                                        <option value="60">60 ngày</option>
                                        <option value="90">90 ngày</option>
                                    </select>
                                    <button type="submit" name="cleanup_logs" class="btn btn-sm btn-orange text-white bg-warning">
                                        <i class="fas fa-trash-alt mr-1"></i> Dọn dẹp ngay
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <h6 class="font-bold text-dark mb-2"><i class="fas fa-sliders-h mr-2 text-primary"></i> Cấu hình Giới hạn (Rate Limit)</h6>
                        <div class="alert alert-info border-0 small mb-0">
                            <p class="mb-2"><i class="fas fa-info-circle mr-1"></i> Để thay đổi cấu hình rate limit, vui lòng chỉnh sửa trong bảng database <code>rate_limit_config</code> hoặc tệp <code>config/spam_protection.php</code>.</p>
                            <strong>Hạn mức mặc định:</strong>
                            <ul class="list-unstyled mb-0 mt-1 text-xs">
                                <li>• Nạp thẻ: 5 lần / 5 phút → block 1 giờ</li>
                                <li>• Đăng nhập: 10 lần / 5 phút → block 30 phút</li>
                                <li>• Đăng ký: 3 lần / 1 giờ → block 24 giờ</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>