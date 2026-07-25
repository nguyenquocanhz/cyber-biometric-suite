<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add Ad
if (isset($_POST['btnAddAd'])) {
    $name = check_string($_POST['name']);
    $position = check_string($_POST['position']);
    $content = $_POST['content']; // Allow HTML
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $CMSNT->insert("ads", [
        'name' => $name,
        'position' => $position,
        'content' => $content,
        'is_active' => $is_active
    ]);
    echo '<script>Swal.fire("Thành công", "Đã thêm quảng cáo!", "success").then(() => location.href = "ads.php");</script>';
}

// Handle Update Ad
if (isset($_POST['btnUpdateAd'])) {
    $id = (int) $_POST['id'];
    $name = check_string($_POST['name']);
    $position = check_string($_POST['position']);
    $content = $_POST['content'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $CMSNT->update("ads", [
        'name' => $name,
        'position' => $position,
        'content' => $content,
        'is_active' => $is_active
    ], " `id` = $id ");
    echo '<script>Swal.fire("Thành công", "Đã cập nhật!", "success").then(() => location.href = "ads.php");</script>';
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $CMSNT->remove("ads", " `id` = $id ");
    echo '<script>Swal.fire("Thành công", "Đã xóa!", "success").then(() => location.href = "ads.php");</script>';
}

// Position options
$positions = [
    'header_top' => 'Header (Top)',
    'header_bottom' => 'Header (Bottom)',
    'sidebar_top' => 'Sidebar (Top)',
    'sidebar_bottom' => 'Sidebar (Bottom)',
    'content_top' => 'Nội dung (Top)',
    'content_bottom' => 'Nội dung (Bottom)',
    'footer' => 'Footer',
    'popup' => 'Popup'
];
?>

<div class="row g-4">
    <!-- Add/Edit Form -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark" id="formTitle">
                    <i class="fas fa-plus-circle text-primary mr-2"></i>Thêm Quảng cáo
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="adForm">
                    <input type="hidden" name="id" id="ad_id">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Tên quảng cáo</label>
                        <input type="text" name="name" id="ad_name" required placeholder="VD: Banner Header" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Vị trí hiển thị</label>
                        <select name="position" id="ad_position" required class="form-select">
                            <?php foreach ($positions as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Nội dung (HTML/Script)</label>
                        <textarea name="content" id="ad_content" rows="6" required placeholder="<a href='...'><img src='...'></a> hoặc mã quảng cáo Google..." class="form-control font-mono small"></textarea>
                        <small class="text-muted">Hỗ trợ HTML, thẻ hình ảnh, hoặc mã embed (Google Adsense, v.v.)</small>
                    </div>
                    <div class="mb-4 form-check form-switch">
                        <input type="checkbox" name="is_active" id="ad_active" value="1" checked class="form-check-input cursor-pointer">
                        <label for="ad_active" class="form-check-label font-semibold text-dark cursor-pointer">Kích hoạt quảng cáo</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="btnAddAd" id="submitBtn" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-plus mr-1"></i> Thêm mới
                        </button>
                        <button type="button" onclick="resetForm()" class="btn btn-secondary px-3" title="Làm mới">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ads List -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-list text-primary mr-2"></i>Danh sách Quảng cáo
                </h5>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                        <thead class="table-light">
                            <tr class="text-uppercase small font-bold text-muted">
                                <th class="ps-4" style="width: 80px;">ID</th>
                                <th>Tên quảng cáo</th>
                                <th>Vị trí</th>
                                <th>Trạng thái</th>
                                <th class="text-center pe-4" style="width: 120px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php
                            $ads = $CMSNT->get_list("SELECT * FROM `ads` ORDER BY `position`, `id` DESC");
                            if ($ads):
                                foreach ($ads as $ad):
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-mono">#<?= $ad['id'] ?></td>
                                        <td class="font-bold text-dark"><?= htmlspecialchars($ad['name']) ?></td>
                                        <td>
                                            <span class="badge bg-purple text-uppercase">
                                                <?= $positions[$ad['position']] ?? $ad['position'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $ad['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $ad['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <div class="d-inline-flex gap-1">
                                                <button onclick='editAd(<?= json_encode($ad) ?>)' class="btn btn-sm btn-outline-primary px-2" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteAd(<?= $ad['id'] ?>)" class="btn btn-sm btn-outline-danger px-2" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                endforeach;
                            else:
                                ?>
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-muted">Chưa có quảng cáo nào được cấu hình.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function editAd(data) {
        document.getElementById('ad_id').value = data.id;
        document.getElementById('ad_name').value = data.name;
        document.getElementById('ad_position').value = data.position;
        document.getElementById('ad_content').value = data.content;
        document.getElementById('ad_active').checked = data.is_active == 1;

        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-primary mr-2"></i>Chỉnh sửa Quảng cáo';
        document.getElementById('submitBtn').name = 'btnUpdateAd';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-1"></i> Lưu thay đổi';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('adForm').reset();
        document.getElementById('ad_id').value = '';
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-primary mr-2"></i>Thêm Quảng cáo';
        document.getElementById('submitBtn').name = 'btnAddAd';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus mr-1"></i> Thêm mới';
    }

    function deleteAd(id) {
        Swal.fire({
            title: 'Xóa quảng cáo này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'ads.php?delete=' + id;
            }
        });
    }
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>