<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add/Delete/Update
if (isset($_POST['btnAdd'])) {
    $name = check_string($_POST['name']);
    $code = strtoupper(check_string($_POST['code']));
    $status = check_string($_POST['status']);

    // Check if code already exists
    $exists = $CMSNT->get_row("SELECT * FROM `telcos` WHERE `code` = '$code'");
    if ($exists) {
        echo '<script>Swal.fire("Lỗi", "Mã nhà mạng đã tồn tại!", "error");</script>';
    } else {
        $CMSNT->insert("telcos", [
            'name' => $name,
            'code' => $code,
            'status' => $status
        ]);
        admin_log("Thêm nhà mạng: $name ($code)");
        echo '<script>Swal.fire("Thành công", "Thêm nhà mạng thành công!", "success").then(() => window.location.reload());</script>';
    }
}

if (isset($_POST['btnDelete'])) {
    $id = check_string($_POST['id']);
    $telco = $CMSNT->get_row("SELECT * FROM `telcos` WHERE `id` = '$id'");
    if ($telco) {
        $CMSNT->remove("telcos", " `id` = '$id' ");
        admin_log("Xóa nhà mạng: " . $telco['name'] . " (" . $telco['code'] . ")");
        echo '<script>Swal.fire("Thành công", "Xóa nhà mạng thành công!", "success").then(() => window.location.reload());</script>';
    }
}

if (isset($_POST['btnUpdate'])) {
    $id = check_string($_POST['id']);
    $name = check_string($_POST['name']);
    $code = strtoupper(check_string($_POST['code']));
    $status = check_string($_POST['status']);

    // Check if code already exists (except current record)
    $exists = $CMSNT->get_row("SELECT * FROM `telcos` WHERE `code` = '$code' AND `id` != '$id'");
    if ($exists) {
        echo '<script>Swal.fire("Lỗi", "Mã nhà mạng đã tồn tại!", "error");</script>';
    } else {
        $CMSNT->update("telcos", [
            'name' => $name,
            'code' => $code,
            'status' => $status
        ], " `id` = '$id' ");
        admin_log("Cập nhật nhà mạng: $name ($code)");
        echo '<script>Swal.fire("Thành công", "Cập nhật thành công!", "success").then(() => window.location.reload());</script>';
    }
}

// Get statistics
$total_telcos = $CMSNT->num_rows("SELECT * FROM `telcos`") ?: 0;
$active_telcos = $CMSNT->num_rows("SELECT * FROM `telcos` WHERE `status` = 1") ?: 0;
?>

<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-3 bg-primary-subtle text-primary rounded-circle mr-3">
                    <i class="fas fa-sim-card text-2xl"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase font-semibold">Tổng Nhà Mạng</p>
                    <h4 class="mb-0 font-bold"><?= $total_telcos ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="p-3 bg-success-subtle text-success rounded-circle mr-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <div>
                    <p class="mb-0 text-muted small text-uppercase font-semibold">Đang Hoạt Động</p>
                    <h4 class="mb-0 font-bold"><?= $active_telcos ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Row -->
<div class="row g-4 mb-4">
    <!-- Left Column: Add Form -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-plus-circle mr-2 text-primary"></i>Thêm Nhà Mạng
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Tên hiển thị <span class="text-danger">*</span></label>
                        <input type="text" name="name" required placeholder="Ví dụ: Viettel" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Mã nhà mạng <span class="text-danger">*</span></label>
                        <input type="text" name="code" required placeholder="Ví dụ: VIETTEL" class="form-control font-mono uppercase" oninput="this.value = this.value.toUpperCase()">
                        <small class="text-muted">Mã phải viết hoa, không dấu</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label font-semibold text-dark">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="1">Hiển thị</option>
                            <option value="0">Ẩn</option>
                        </select>
                    </div>
                    <button type="submit" name="btnAdd" class="btn btn-primary w-100 py-2">
                        <i class="fas fa-plus mr-2"></i>Thêm Nhà Mạng
                    </button>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small shadow-sm border-0">
            <h6 class="font-bold text-info-emphasis mb-2"><i class="fas fa-info-circle mr-1"></i> Lưu ý</h6>
            <ul class="list-unstyled mb-0 space-y-1">
                <li>• Mã nhà mạng phải là chữ IN HOA</li>
                <li>• Không trùng với mã đã có</li>
                <li>• Trạng thái "Hiển thị": Xuất hiện trên trang nạp thẻ</li>
                <li>• Trạng thái "Ẩn": Không hiển thị ngoài giao diện</li>
            </ul>
        </div>
    </div>

    <!-- Right Column: List -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-list mr-2 text-primary"></i>Danh Sách Nhà Mạng
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th class="ps-4">ID</th>
                                <th>Tên</th>
                                <th>Mã</th>
                                <th>Trạng thái</th>
                                <th class="text-center pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            $telcos = $CMSNT->get_list("SELECT * FROM `telcos` ORDER BY id ASC");
                            if (empty($telcos)) {
                                echo '<tr><td colspan="5" class="p-4 text-center text-muted">Chưa có nhà mạng nào. Hãy thêm nhà mạng đầu tiên!</td></tr>';
                            } else {
                                foreach ($telcos as $row) {
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-mono">#<?= $row['id'] ?></td>
                                        <form method="POST">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <td>
                                                <input type="text" name="name" value="<?= $row['name'] ?>" class="form-control form-control-sm" style="min-width: 140px;">
                                            </td>
                                            <td>
                                                <input type="text" name="code" value="<?= $row['code'] ?>" class="form-control form-control-sm font-mono uppercase" style="min-width: 140px;" oninput="this.value = this.value.toUpperCase()">
                                            </td>
                                            <td>
                                                <select name="status" class="form-select form-select-sm" style="min-width: 120px;">
                                                    <option value="1" <?= $row['status'] == 1 ? 'selected' : '' ?>>🟢 Hiển thị</option>
                                                    <option value="0" <?= $row['status'] == 0 ? 'selected' : '' ?>>🔴 Ẩn</option>
                                                </select>
                                            </td>
                                            <td class="pe-4 text-center">
                                                <div class="d-inline-flex gap-1">
                                                    <button type="submit" name="btnUpdate" class="btn btn-sm btn-outline-primary" title="Lưu thay đổi">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                    <button type="submit" name="btnDelete" class="btn btn-sm btn-outline-danger" onclick="return confirm('⚠️ Bạn có chắc muốn xóa nhà mạng này?')" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </form>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
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