<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add/Delete/Update
if (isset($_POST['btnAdd'])) {
    $CMSNT->insert("packages", [
        'amount' => check_string($_POST['amount']),
        'diamonds' => check_string($_POST['diamonds']),
        'promotion' => check_string($_POST['promotion'])
    ]);
    echo '<script>Swal.fire("Thành công", "Thêm gói nạp thành công!", "success");</script>';
}

if (isset($_POST['btnDelete'])) {
    $id = check_string($_POST['id']);
    $CMSNT->remove("packages", " `id` = '$id' ");
    echo '<script>Swal.fire("Thành công", "Xóa thành công!", "success");</script>';
}

if (isset($_POST['btnUpdate'])) {
    $id = check_string($_POST['id']);
    $CMSNT->update("packages", [
        'amount' => check_string($_POST['amount']),
        'diamonds' => check_string($_POST['diamonds']),
        'promotion' => check_string($_POST['promotion'])
    ], " `id` = '$id' ");
    echo '<script>Swal.fire("Thành công", "Cập nhật thành công!", "success");</script>';
}
?>

<div class="row g-4 mb-4">
    <!-- Left Column: Add Form -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-plus-circle mr-2 text-primary"></i>Thêm Gói Nạp
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Mệnh giá (VND)</label>
                        <input type="number" name="amount" required placeholder="50000" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Kim Cương Nhận</label>
                        <input type="text" name="diamonds" required placeholder="1.132" class="form-control">
                    </div>
                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">Khuyến mãi (Text)</label>
                        <input type="text" name="promotion" placeholder="X2" class="form-control">
                    </div>
                    <button type="submit" name="btnAdd" class="btn btn-primary w-100 py-2">
                        <i class="fas fa-plus mr-2"></i>Thêm Mới
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: List -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-list mr-2 text-primary"></i>Danh sách Gói Nạp
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th class="ps-4">ID</th>
                                <th>Mệnh giá</th>
                                <th>Kim Cương</th>
                                <th>Khuyến mãi</th>
                                <th class="text-center pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($CMSNT->get_list("SELECT * FROM `packages` ORDER BY amount ASC") as $row) { ?>
                                <tr>
                                    <td class="ps-4 text-muted font-mono">#<?= $row['id'] ?></td>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <td>
                                            <input type="number" name="amount" value="<?= $row['amount'] ?>" class="form-control form-control-sm" style="min-width: 120px;">
                                        </td>
                                        <td>
                                            <input type="text" name="diamonds" value="<?= $row['diamonds'] ?>" class="form-control form-control-sm" style="min-width: 120px;">
                                        </td>
                                        <td>
                                            <input type="text" name="promotion" value="<?= $row['promotion'] ?>" class="form-control form-control-sm" style="min-width: 80px;">
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="d-inline-flex gap-1">
                                                <button type="submit" name="btnUpdate" class="btn btn-sm btn-outline-primary" title="Lưu">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                                <button type="submit" name="btnDelete" class="btn btn-sm btn-outline-danger" onclick="return confirm('⚠️ Xác nhận xóa gói nạp này?')" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </form>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>