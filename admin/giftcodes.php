<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add Giftcode
if (isset($_POST['btnAdd'])) {
    $code = check_string($_POST['code']);
    $desc = check_string($_POST['description']);
    $amount = isset($_POST['amount']) ? check_string($_POST['amount']) : 0;

    // Check if exists
    if ($CMSNT->num_rows("SELECT * FROM `giftcodes` WHERE `code` = '$code'") > 0) {
        echo '<script>Swal.fire("Lỗi", "Giftcode này đã tồn tại!", "error");</script>';
    } else {
        $CMSNT->insert("giftcodes", [
            'code' => $code,
            'description' => $desc,
            'is_active' => 1
        ]);
        echo '<script>Swal.fire("Thành công", "Thêm Giftcode thành công!", "success").then(() => location.href = "giftcodes.php");</script>';
    }
}

// Handle Delete
if (isset($_POST['btnDelete'])) {
    $id = check_string($_POST['id']);
    $CMSNT->remove("giftcodes", " `id` = '$id' ");
    echo '<script>Swal.fire("Thành công", "Xóa thành công!", "success").then(() => location.href = "giftcodes.php");</script>';
}
?>

<div class="row g-4">
    <!-- Add Form -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-plus-circle mr-2 text-primary"></i>Thêm Giftcode
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Mã Giftcode</label>
                        <input type="text" name="code" required placeholder="NHANTHE20K" class="form-control text-uppercase">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Mô tả / Ghi chú</label>
                        <textarea name="description" rows="3" placeholder="Nhập ghi chú hoặc mô tả về giftcode..." class="form-control"></textarea>
                    </div>
                    <button type="submit" name="btnAdd" class="btn btn-primary w-100 py-2">
                        <i class="fas fa-save mr-1"></i> Thêm Mới
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-gift mr-2 text-primary"></i>Danh sách Giftcode
                </h5>
                <form method="GET" class="d-inline-flex gap-1 mb-0">
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Tìm mã code..." class="form-control form-control-sm">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($_GET['search'])): ?>
                        <a href="/admin/giftcodes.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th class="ps-4" style="width: 80px;">ID</th>
                                <th>Mã Code</th>
                                <th>Mô tả</th>
                                <th>Trạng thái</th>
                                <th class="text-center pe-4" style="width: 100px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            // Search & Pagination
                            $search = check_string($_GET['search'] ?? '');
                            $per_page = 20;
                            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
                            $start = ($page - 1) * $per_page;

                            // Build query
                            $where = "1=1";
                            if (!empty($search)) {
                                $where .= " AND (`code` LIKE '%$search%' OR `description` LIKE '%$search%')";
                            }

                            // Get total count
                            $total_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `giftcodes` WHERE $where");
                            $total = $total_row['total'];

                            // Get paginated list
                            $list_gift = $CMSNT->get_list("SELECT * FROM `giftcodes` WHERE $where ORDER BY id DESC LIMIT $start, $per_page");

                            if (empty($list_gift)) {
                                echo '<tr><td colspan="5" class="p-4 text-center text-muted">Chưa có giftcode nào.</td></tr>';
                            } else {
                                foreach ($list_gift as $row) { ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-mono">#<?= $row['id'] ?></td>
                                        <td class="font-mono font-bold text-primary"><?= htmlspecialchars($row['code']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($row['description']) ?></td>
                                        <td>
                                            <?= $row['is_active'] == 1
                                                ? '<span class="badge bg-success">Active</span>'
                                                : '<span class="badge bg-secondary">Inactive</span>'
                                            ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <form method="POST" onsubmit="return confirm('⚠️ Xác nhận xóa giftcode này?')" class="mb-0">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" name="btnDelete" class="btn btn-sm btn-outline-danger px-2" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php }
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer bg-white border-top">
                <?php
                if ($total > 0) {
                    $pagination_url = '/admin/giftcodes.php?' . (!empty($search) ? 'search=' . urlencode($search) . '&' : '');
                    echo admin_phantrang($pagination_url, $start, $total, $per_page);
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>