<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Save Settings
if (isset($_POST['btnSaveOption'])) {
    foreach ($_POST as $key => $value) {
        $CMSNT->update("options", array(
            'value' => $value
        ), " `name` = '$key' ");
    }
    echo '<script>Swal.fire("Thành công", "Lưu cài đặt thành công!", "success");</script>';
}

if (isset($_POST['btnSyncAPI'])) {
    $partner_id = $CMSNT->site('partner_id');
    $url = "https://doithe1s.vn/chargingws/v2/getfee?partner_id=$partner_id";
    $data = file_get_contents($url);
    $json = json_decode($data, true);

    if ($json) {
        $CMSNT->query("TRUNCATE TABLE `card_fees`"); // Clear old data
        foreach ($json as $row) {
            $CMSNT->insert("card_fees", [
                'telco' => check_string($row['telco']),
                'value' => check_string($row['value']),
                'fees' => check_string($row['fees']),
                'penalty' => check_string($row['penalty']),
                'status' => 1
            ]);
        }
        echo '<script>Swal.fire("Thành công", "Cập nhật từ API thành công!", "success");</script>';
    } else {
        echo '<script>Swal.fire("Lỗi", "Không thể lấy dữ liệu từ API!", "error");</script>';
    }
}

// Handle Reset Default (User JSON)
if (isset($_POST['btnResetDefault'])) {
    $defaultFees = '[
        {"telco": "VIETTEL", "value": 10000, "fees": 14, "penalty": 50},
        {"telco": "VINAPHONE", "value": 10000, "fees": 16, "penalty": 50},
        {"telco": "MOBIFONE", "value": 10000, "fees": 17.5, "penalty": 50},
        {"telco": "VNMOBI", "value": 10000, "fees": 100, "penalty": 50},
        {"telco": "ZING", "value": 10000, "fees": 11, "penalty": 50},
        {"telco": "GATE", "value": 10000, "fees": 10.5, "penalty": 50},
        {"telco": "VCOIN", "value": 10000, "fees": 11.5, "penalty": 50},
        {"telco": "SCOIN", "value": 10000, "fees": 29, "penalty": 50},
        {"telco": "GARENA", "value": 10000, "fees": 8.3, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 10000, "fees": 9, "penalty": 50},
        {"telco": "VIETTEL", "value": 20000, "fees": 17, "penalty": 50},
        {"telco": "VINAPHONE", "value": 20000, "fees": 14.5, "penalty": 50},
        {"telco": "MOBIFONE", "value": 20000, "fees": 17.5, "penalty": 50},
        {"telco": "VNMOBI", "value": 20000, "fees": 100, "penalty": 50},
        {"telco": "ZING", "value": 20000, "fees": 11, "penalty": 50},
        {"telco": "GATE", "value": 20000, "fees": 10.5, "penalty": 50},
        {"telco": "GARENA", "value": 20000, "fees": 8.3, "penalty": 50},
        {"telco": "VCOIN", "value": 20000, "fees": 11.5, "penalty": 50},
        {"telco": "SCOIN", "value": 20000, "fees": 29, "penalty": 50},
        {"telco": "GARENACHAM", "value": 20000, "fees": 8, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 20000, "fees": 9, "penalty": 50},
        {"telco": "ZINGCHAM", "value": 20000, "fees": 8, "penalty": 50},
        {"telco": "VIETTEL", "value": 30000, "fees": 17, "penalty": 50},
        {"telco": "VINAPHONE", "value": 30000, "fees": 13.5, "penalty": 50},
        {"telco": "MOBIFONE", "value": 30000, "fees": 17.5, "penalty": 50},
        {"telco": "VNMOBI", "value": 30000, "fees": 100, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 30000, "fees": 9, "penalty": 50},
        {"telco": "VIETTEL", "value": 50000, "fees": 16.5, "penalty": 50},
        {"telco": "VINAPHONE", "value": 50000, "fees": 13, "penalty": 50},
        {"telco": "MOBIFONE", "value": 50000, "fees": 17.2, "penalty": 50},
        {"telco": "VNMOBI", "value": 50000, "fees": 100, "penalty": 50},
        {"telco": "ZING", "value": 50000, "fees": 11, "penalty": 50},
        {"telco": "GATE", "value": 50000, "fees": 10.5, "penalty": 50},
        {"telco": "GARENA", "value": 50000, "fees": 8.3, "penalty": 50},
        {"telco": "VCOIN", "value": 50000, "fees": 11.5, "penalty": 50},
        {"telco": "APPOTA", "value": 50000, "fees": 14.5, "penalty": 50},
        {"telco": "SCOIN", "value": 50000, "fees": 29, "penalty": 50},
        {"telco": "ZINGCHAM", "value": 50000, "fees": 8, "penalty": 50},
        {"telco": "GARENACHAM", "value": 50000, "fees": 8, "penalty": 50},
        {"telco": "VIETTELCHAM", "value": 50000, "fees": 9, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 50000, "fees": 9, "penalty": 50},
        {"telco": "VIETTEL", "value": 100000, "fees": 16.5, "penalty": 50},
        {"telco": "VINAPHONE", "value": 100000, "fees": 13.3, "penalty": 50},
        {"telco": "MOBIFONE", "value": 100000, "fees": 17.2, "penalty": 50},
        {"telco": "VNMOBI", "value": 100000, "fees": 100, "penalty": 50},
        {"telco": "ZING", "value": 100000, "fees": 11, "penalty": 50},
        {"telco": "GATE", "value": 100000, "fees": 10.5, "penalty": 50},
        {"telco": "GARENA", "value": 100000, "fees": 8.3, "penalty": 50},
        {"telco": "VCOIN", "value": 100000, "fees": 11.5, "penalty": 50},
        {"telco": "APPOTA", "value": 100000, "fees": 14.5, "penalty": 50},
        {"telco": "SCOIN", "value": 100000, "fees": 29, "penalty": 50},
        {"telco": "ZINGCHAM", "value": 100000, "fees": 8, "penalty": 50},
        {"telco": "GARENACHAM", "value": 100000, "fees": 8, "penalty": 50},
        {"telco": "VIETTELCHAM", "value": 100000, "fees": 9, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 100000, "fees": 9, "penalty": 50},
        {"telco": "VIETTEL", "value": 200000, "fees": 17.8, "penalty": 50},
        {"telco": "VINAPHONE", "value": 200000, "fees": 14.3, "penalty": 50},
        {"telco": "MOBIFONE", "value": 200000, "fees": 18.5, "penalty": 50},
        {"telco": "VNMOBI", "value": 200000, "fees": 100, "penalty": 50},
        {"telco": "ZING", "value": 200000, "fees": 11, "penalty": 50},
        {"telco": "GATE", "value": 200000, "fees": 10.5, "penalty": 50},
        {"telco": "GARENA", "value": 200000, "fees": 8.3, "penalty": 50},
        {"telco": "VCOIN", "value": 200000, "fees": 11.5, "penalty": 50},
        {"telco": "APPOTA", "value": 200000, "fees": 14.5, "penalty": 50},
        {"telco": "SCOIN", "value": 200000, "fees": 29, "penalty": 50},
        {"telco": "ZINGCHAM", "value": 200000, "fees": 8, "penalty": 50},
        {"telco": "GARENACHAM", "value": 200000, "fees": 8, "penalty": 50},
        {"telco": "VIETTELCHAM", "value": 200000, "fees": 9, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 200000, "fees": 9, "penalty": 50},
        {"telco": "VIETTEL", "value": 300000, "fees": 17.8, "penalty": 50},
        {"telco": "VINAPHONE", "value": 300000, "fees": 14.3, "penalty": 50},
        {"telco": "MOBIFONE", "value": 300000, "fees": 18.5, "penalty": 50},
        {"telco": "VNMOBI", "value": 300000, "fees": 100, "penalty": 50},
        {"telco": "VCOIN", "value": 300000, "fees": 11.5, "penalty": 50},
        {"telco": "SCOIN", "value": 300000, "fees": 29, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 300000, "fees": 9, "penalty": 50},
        {"telco": "VIETTEL", "value": 500000, "fees": 20.5, "penalty": 50},
        {"telco": "VINAPHONE", "value": 500000, "fees": 14.3, "penalty": 50},
        {"telco": "MOBIFONE", "value": 500000, "fees": 18.5, "penalty": 50},
        {"telco": "VNMOBI", "value": 500000, "fees": 100, "penalty": 50},
        {"telco": "ZING", "value": 500000, "fees": 12, "penalty": 50},
        {"telco": "GATE", "value": 500000, "fees": 10.5, "penalty": 50},
        {"telco": "GARENA", "value": 500000, "fees": 8.3, "penalty": 50},
        {"telco": "VCOIN", "value": 500000, "fees": 11.5, "penalty": 50},
        {"telco": "APPOTA", "value": 500000, "fees": 14.5, "penalty": 50},
        {"telco": "SCOIN", "value": 500000, "fees": 29, "penalty": 50},
        {"telco": "GARENACHAM", "value": 500000, "fees": 8, "penalty": 50},
        {"telco": "VIETTELCHAM", "value": 500000, "fees": 9, "penalty": 50},
        {"telco": "VINAPHONECHAM", "value": 500000, "fees": 9, "penalty": 50},
        {"telco": "VIETTEL", "value": 1000000, "fees": 20.5, "penalty": 50},
        {"telco": "ZING", "value": 1000000, "fees": 12, "penalty": 50},
        {"telco": "GATE", "value": 1000000, "fees": 10.5, "penalty": 50},
        {"telco": "VCOIN", "value": 1000000, "fees": 11.5, "penalty": 50},
        {"telco": "APPOTA", "value": 1000000, "fees": 11, "penalty": 50},
        {"telco": "SCOIN", "value": 1000000, "fees": 29, "penalty": 50},
        {"telco": "VCOIN", "value": 2000000, "fees": 13.5, "penalty": 50},
        {"telco": "APPOTA", "value": 2000000, "fees": 11, "penalty": 50},
        {"telco": "SCOIN", "value": 2000000, "fees": 29, "penalty": 50},
        {"telco": "GATE", "value": 5000000, "fees": 10.5, "penalty": 50},
        {"telco": "VCOIN", "value": 5000000, "fees": 13.5, "penalty": 50},
        {"telco": "APPOTA", "value": 5000000, "fees": 11, "penalty": 50},
        {"telco": "SCOIN", "value": 5000000, "fees": 29, "penalty": 50},
        {"telco": "GATE", "value": 10000000, "fees": 10.5, "penalty": 50},
        {"telco": "VCOIN", "value": 10000000, "fees": 16, "penalty": 50}
    ]';

    $json = json_decode($defaultFees, true);
    if ($json) {
        $CMSNT->query("TRUNCATE TABLE `card_fees`"); // Clear old data
        foreach ($json as $row) {
            $CMSNT->insert("card_fees", [
                'telco' => check_string($row['telco']),
                'value' => check_string($row['value']),
                'fees' => check_string($row['fees']),
                'penalty' => check_string($row['penalty']),
                'status' => 1
            ]);
        }
        echo '<script>Swal.fire("Thành công", "Đã khôi phục dữ liệu mặc định!", "success");</script>';
    }
}

// Handle Update Individual
if (isset($_POST['btnUpdate'])) {
    $id = check_string($_POST['id']);
    $fees = check_string($_POST['fees']);
    $penalty = check_string($_POST['penalty']);
    $status = check_string($_POST['status']);

    $CMSNT->update("card_fees", [
        'fees' => $fees,
        'penalty' => $penalty,
        'status' => $status
    ], " `id` = '$id' ");
    echo '<script>Swal.fire("Thành công", "Cập nhật thành công!", "success");</script>';
}

// Handle Delete
if (isset($_POST['btnDelete'])) {
    $id = check_string($_POST['id']);
    $CMSNT->remove("card_fees", " `id` = '$id' ");
    echo '<script>Swal.fire("Thành công", "Xóa thành công!", "success");</script>';
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-sync-alt mr-2 text-primary"></i>Cấu hình Tự động
        </h5>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row align-items-end g-3">
                <div class="col-12 col-md-6 col-lg-8">
                    <label class="form-label font-semibold text-dark">Tự động cập nhật bảng giá (Cronjob)</label>
                    <select name="auto_update_card_fees" class="form-select">
                        <option value="1" <?= $CMSNT->site('auto_update_card_fees') == 1 ? 'selected' : '' ?>>Bật (ON)</option>
                        <option value="0" <?= $CMSNT->site('auto_update_card_fees') == 0 ? 'selected' : '' ?>>Tắt (OFF)</option>
                    </select>
                    <small class="text-muted mt-1 d-block">Nếu bật, hệ thống sẽ tự động đồng bộ giá từ API theo chu kỳ Cron.</small>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <button type="submit" name="btnSaveOption" class="btn btn-primary w-100 py-2">
                        <i class="fas fa-save mr-1"></i> Lưu Cấu Hình
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-percent mr-2 text-primary"></i>Quản lý Bảng giá / Phí gạch thẻ
        </h5>
        <div class="d-inline-flex gap-1">
            <form method="POST" onsubmit="return confirm('Bạn có chắc muốn reset về mặc định?');" class="mb-0">
                <button type="submit" name="btnResetDefault" class="btn btn-sm btn-warning text-dark font-bold">
                    <i class="fas fa-undo mr-1"></i> Khôi phục Mặc định
                </button>
            </form>
            <form method="POST" onsubmit="return confirm('Toàn bộ dữ liệu cũ sẽ bị thay thế bởi dữ liệu từ API. Tiếp tục?');" class="mb-0">
                <button type="submit" name="btnSyncAPI" class="btn btn-sm btn-info text-white font-bold">
                    <i class="fas fa-cloud-download-alt mr-1"></i> Cập nhật từ API
                </button>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4">ID</th>
                        <th>Nhà mạng</th>
                        <th>Mệnh giá</th>
                        <th>Chiết khấu (%)</th>
                        <th>Phạt (%)</th>
                        <th>Trạng thái</th>
                        <th class="text-center pe-4">Hành động</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <?php
                    // Pagination Setup
                    $page = isset($_GET['page']) ? check_string($_GET['page']) : 1;
                    $limit = 50;
                    $start = ($page - 1) * $limit;

                    // Fetch Data
                    $list = $CMSNT->get_list("SELECT * FROM `card_fees` ORDER BY telco ASC, value ASC LIMIT $start, $limit");
                    $total_rows = $CMSNT->num_rows("SELECT * FROM `card_fees`");

                    if ($list) {
                        foreach ($list as $row) { ?>
                            <tr>
                                <td class="ps-4 text-muted font-mono">#<?= $row['id'] ?></td>
                                <td class="font-bold text-dark"><?= $row['telco'] ?></td>
                                <td class="font-mono text-primary font-bold"><?= number_format($row['value']) ?>đ</td>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <td>
                                        <input type="text" name="fees" value="<?= $row['fees'] ?>" class="form-control form-control-sm text-center" style="max-width: 90px;" required>
                                    </td>
                                    <td>
                                        <input type="text" name="penalty" value="<?= $row['penalty'] ?>" class="form-control form-control-sm text-center" style="max-width: 90px;" required>
                                    </td>
                                    <td>
                                        <select name="status" class="form-select form-select-sm" style="max-width: 100px;">
                                            <option value="1" <?= $row['status'] == 1 ? 'selected' : '' ?>>ON</option>
                                            <option value="0" <?= $row['status'] == 0 ? 'selected' : '' ?>>OFF</option>
                                        </select>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <div class="d-inline-flex gap-1">
                                            <button type="submit" name="btnUpdate" class="btn btn-sm btn-outline-primary" title="Lưu">
                                                <i class="fas fa-save"></i>
                                            </button>
                                            <button type="submit" name="btnDelete" class="btn btn-sm btn-outline-danger" onclick="return confirm('⚠️ Xác nhận xóa cấu hình này?')" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php }
                    } else {
                        echo '<tr><td colspan="7" class="p-4 text-center text-muted">Chưa có dữ liệu. Vui lòng cập nhật API hoặc Khôi phục mặc định.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top">
        <?= admin_phantrang(base_url('admin/card-fees.php?'), $start, $total_rows, $limit) ?>
    </div>
</div>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>