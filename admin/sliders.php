<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add Slider
if (isset($_POST['btnAddSlider'])) {
    $title = check_string($_POST['title']);
    $image_url = check_string($_POST['image_url']);
    $link = check_string($_POST['link'] ?? '');
    $order_priority = (int) $_POST['order_priority'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $CMSNT->insert("sliders", [
        'title' => $title,
        'image_url' => $image_url,
        'link' => $link,
        'order_priority' => $order_priority,
        'is_active' => $is_active
    ]);
    echo '<script>Swal.fire("Thành công", "Đã thêm slider!", "success").then(() => location.href = "sliders.php");</script>';
}

// Handle Update Slider
if (isset($_POST['btnUpdateSlider'])) {
    $id = (int) $_POST['id'];
    $title = check_string($_POST['title']);
    $image_url = check_string($_POST['image_url']);
    $link = check_string($_POST['link'] ?? '');
    $order_priority = (int) $_POST['order_priority'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $CMSNT->update("sliders", [
        'title' => $title,
        'image_url' => $image_url,
        'link' => $link,
        'order_priority' => $order_priority,
        'is_active' => $is_active
    ], " `id` = $id ");
    echo '<script>Swal.fire("Thành công", "Đã cập nhật slider!", "success").then(() => location.href = "sliders.php");</script>';
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $CMSNT->remove("sliders", " `id` = $id ");
    echo '<script>Swal.fire("Thành công", "Đã xóa slider!", "success").then(() => location.href = "sliders.php");</script>';
}
?>

<div class="row g-4">
    <!-- Add/Edit Form -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark" id="formTitle">
                    <i class="fas fa-plus-circle text-primary mr-2"></i>Thêm Slider
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="sliderForm">
                    <input type="hidden" name="id" id="slider_id">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Tiêu đề</label>
                        <input type="text" name="title" id="slider_title" required class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">URL Hình ảnh</label>
                        <div class="input-group">
                            <input type="text" name="image_url" id="slider_image" required class="form-control font-mono">
                            <label class="btn btn-outline-secondary mb-0 d-flex align-items-center cursor-pointer">
                                <i class="fas fa-upload"></i>
                                <input type="file" id="upload_slider" accept="image/*" class="d-none">
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Link (khi click)</label>
                        <input type="text" name="link" id="slider_link" placeholder="https://..." class="form-control font-mono">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Thứ tự ưu tiên</label>
                        <input type="number" name="order_priority" id="slider_order" value="0" min="0" class="form-control font-mono">
                    </div>
                    <div class="mb-4 form-check form-switch">
                        <input type="checkbox" name="is_active" id="slider_active" value="1" checked class="form-check-input cursor-pointer">
                        <label for="slider_active" class="form-check-label font-semibold text-dark cursor-pointer">Kích hoạt hiển thị</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="btnAddSlider" id="submitBtn" class="btn btn-primary w-100 py-2">
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

    <!-- Slider List -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white border-bottom">
                <h5 class="card-title mb-0 font-bold text-dark">
                    <i class="fas fa-images text-primary mr-2"></i>Danh sách Slider
                </h5>
            </div>
            
            <div class="card-body p-4">
                <div class="row g-3">
                    <?php
                    $sliders = $CMSNT->get_list("SELECT * FROM `sliders` ORDER BY `order_priority` ASC, `id` DESC");
                    if ($sliders):
                        foreach ($sliders as $slide):
                            ?>
                            <div class="col-12 col-sm-6">
                                <div class="card h-100 shadow-sm border <?= $slide['is_active'] ? 'border-success-subtle' : 'border-light-subtle opacity-75' ?>">
                                    <div class="position-relative overflow-hidden bg-light" style="padding-top: 56.25%;">
                                        <img src="<?= $slide['image_url'] ?>" alt="<?= htmlspecialchars($slide['title']) ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                                        <span class="position-absolute top-2 start-2 badge <?= $slide['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $slide['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <span class="position-absolute top-2 end-2 badge bg-primary">
                                            #<?= $slide['order_priority'] ?>
                                        </span>
                                    </div>
                                    <div class="card-body p-3 d-flex flex-column">
                                        <h6 class="font-bold text-dark text-truncate mb-1"><?= htmlspecialchars($slide['title']) ?></h6>
                                        <?php if ($slide['link']): ?>
                                            <small class="text-muted text-truncate mb-3 d-block font-mono"><?= htmlspecialchars($slide['link']) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted mb-3 d-block">Không có liên kết</small>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2 mt-auto">
                                            <button onclick='editSlider(<?= json_encode($slide) ?>)' class="btn btn-sm btn-outline-primary w-100">
                                                <i class="fas fa-edit mr-1"></i> Sửa
                                            </button>
                                            <button onclick="deleteSlider(<?= $slide['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endforeach;
                    else:
                        ?>
                        <div class="col-12 text-center py-4 text-muted">
                            Chưa có slider nào.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function editSlider(data) {
        document.getElementById('slider_id').value = data.id;
        document.getElementById('slider_title').value = data.title;
        document.getElementById('slider_image').value = data.image_url;
        document.getElementById('slider_link').value = data.link || '';
        document.getElementById('slider_order').value = data.order_priority;
        document.getElementById('slider_active').checked = data.is_active == 1;

        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-primary mr-2"></i>Chỉnh sửa Slider';
        document.getElementById('submitBtn').name = 'btnUpdateSlider';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-1"></i> Lưu thay đổi';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('sliderForm').reset();
        document.getElementById('slider_id').value = '';
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle text-primary mr-2"></i>Thêm Slider';
        document.getElementById('submitBtn').name = 'btnAddSlider';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus mr-1"></i> Thêm mới';
    }

    function deleteSlider(id) {
        Swal.fire({
            title: 'Xóa slider này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'sliders.php?delete=' + id;
            }
        });
    }

    // Upload handler
    document.getElementById('upload_slider').addEventListener('change', function () {
        if (!this.files[0]) return;

        const formData = new FormData();
        formData.append('image', this.files[0]);
        formData.append('type', 'banner');

        $.ajax({
            url: '../model/upload-image.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                var resp = typeof response === 'object' ? response : JSON.parse(response);
                if (resp.status === 'success') {
                    document.getElementById('slider_image').value = resp.data.url;
                    Swal.fire({ icon: 'success', title: 'Đã tải lên ảnh!', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Lỗi', resp.msg, 'error');
                }
            }
        });
    });
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>