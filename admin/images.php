<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Delete
if (isset($_GET['delete'])) {
    $file_path = check_string($_GET['delete']);
    $full_path = __DIR__ . '/../uploads/' . $file_path;

    // Security: Validate path is within uploads directory
    $real_path = realpath($full_path);
    $uploads_dir = realpath(__DIR__ . '/../uploads');

    if ($real_path && strpos($real_path, $uploads_dir) === 0 && file_exists($real_path)) {
        if (unlink($real_path)) {
            echo '<script>Swal.fire("Thành công", "Đã xóa ảnh!", "success").then(() => window.location.href = "images.php");</script>';
        } else {
            echo '<script>Swal.fire("Lỗi", "Không thể xóa file!", "error");</script>';
        }
    } else {
        echo '<script>Swal.fire("Lỗi", "File không hợp lệ!", "error");</script>';
    }
}

// Get current filter
$filter = check_string($_GET['filter'] ?? 'all');

// Scan uploads directory
function getImages($directory, $base_path = '')
{
    $images = [];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!is_dir($directory))
        return $images;

    $files = scandir($directory);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..')
            continue;

        $full_path = $directory . '/' . $file;
        $relative_path = $base_path . $file;

        if (is_dir($full_path)) {
            $images = array_merge($images, getImages($full_path, $relative_path . '/'));
        } else {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_ext)) {
                $images[] = [
                    'name' => $file,
                    'path' => $relative_path,
                    'url' => '../uploads/' . $relative_path,
                    'size' => filesize($full_path),
                    'type' => explode('/', $relative_path)[0] ?? 'other',
                    'modified' => filemtime($full_path)
                ];
            }
        }
    }

    return $images;
}

$uploads_dir = __DIR__ . '/../uploads';
$all_images = getImages($uploads_dir);

// Apply filter
if ($filter !== 'all') {
    $all_images = array_filter($all_images, function ($img) use ($filter) {
        return $img['type'] === $filter;
    });
}

// Sort by modified date (newest first)
usort($all_images, function ($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Get unique types
$types = array_unique(array_column($all_images, 'type'));
?>

<style>
    .group-hover-container:hover .hover-overlay {
        opacity: 1 !important;
    }
    .transition-opacity {
        transition: opacity 0.2s ease-in-out;
    }
</style>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-images mr-2 text-primary"></i>Quản lý Hình ảnh
            <span class="text-sm font-normal text-muted">(<?= count($all_images) ?> ảnh)</span>
        </h5>

        <div class="d-inline-flex gap-2">
            <!-- Filter -->
            <select id="filter-select" onchange="applyFilter(this.value)" class="form-select form-select-sm" style="max-width: 140px;">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                <option value="games" <?= $filter === 'games' ? 'selected' : '' ?>>Games</option>
                <option value="banners" <?= $filter === 'banners' ? 'selected' : '' ?>>Banners</option>
                <option value="avatars" <?= $filter === 'avatars' ? 'selected' : '' ?>>Avatars</option>
            </select>

            <!-- Upload Button -->
            <button onclick="showUploadModal()" class="btn btn-sm btn-primary px-3 shadow-xs">
                <i class="fas fa-upload mr-1"></i> Tải lên
            </button>
        </div>
    </div>

    <div class="card-body p-4">
        <?php if (empty($all_images)): ?>
            <div class="text-center py-5">
                <i class="fas fa-image text-muted text-5xl mb-3"></i>
                <p class="text-muted">Chưa có hình ảnh nào trong danh mục này.</p>
                <button onclick="showUploadModal()" class="btn btn-sm btn-outline-primary mt-2">
                    <i class="fas fa-plus mr-1"></i> Tải ảnh đầu tiên
                </button>
            </div>
        <?php else: ?>
            <!-- Image Grid -->
            <div class="row g-3">
                <?php foreach ($all_images as $img): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <div class="card h-100 shadow-sm border border-light-subtle overflow-hidden position-relative group-hover-container">
                            <div class="position-relative overflow-hidden bg-light" style="padding-top: 100%;">
                                <img src="<?= $img['url'] ?>" alt="<?= htmlspecialchars($img['name']) ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover cursor-pointer" onclick="previewImage('<?= $img['url'] ?>', '<?= htmlspecialchars($img['name']) ?>')">
                                
                                <!-- Overlay Actions -->
                                <div class="hover-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-50 opacity-0 transition-opacity" style="z-index: 2;">
                                    <div class="d-inline-flex gap-2">
                                        <button onclick="copyUrl('<?= $img['url'] ?>')" class="btn btn-sm btn-light rounded-circle p-2 shadow-sm" title="Copy URL" style="width: 36px; height: 36px;">
                                            <i class="fas fa-link"></i>
                                        </button>
                                        <button onclick="deleteImage('<?= $img['path'] ?>')" class="btn btn-sm btn-danger rounded-circle p-2 shadow-sm" title="Xóa" style="width: 36px; height: 36px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Info Badge -->
                            <div class="p-2 border-top bg-white" style="z-index: 1;">
                                <p class="mb-0 text-truncate font-bold small text-dark" style="font-size: 11px; max-width: 100%;"><?= htmlspecialchars($img['name']) ?></p>
                                <small class="text-muted font-mono" style="font-size: 10px;"><?= round($img['size'] / 1024, 1) ?> KB</small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold text-dark" id="uploadModalLabel">Tải lên Hình ảnh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Loại ảnh</label>
                        <select name="type" id="uploadType" class="form-select">
                            <option value="game">Game</option>
                            <option value="banner">Banner</option>
                            <option value="avatar">Avatar</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Chọn tệp ảnh</label>
                        <div id="dropZone" class="border border-2 border-dashed rounded p-5 text-center cursor-pointer bg-light hover-bg-primary-subtle" style="border-color: #dee2e6 !important;">
                            <i class="fas fa-cloud-upload-alt text-3xl text-muted mb-2"></i>
                            <p class="mb-1 text-dark font-semibold small">Kéo thả file hoặc click vào đây để chọn</p>
                            <small class="text-muted d-block">Định dạng JPG, PNG, GIF, WebP (Max 2MB)</small>
                            <input type="file" name="image" id="imageInput" accept="image/*" class="d-none">
                        </div>
                        <div id="previewContainer" class="mt-3 text-center d-none">
                            <img id="previewImg" class="img-fluid rounded border border-light-subtle shadow-sm" style="max-height: 160px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4" disabled>
                        <i class="fas fa-upload mr-1"></i> Tải lên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="modal-header border-0 justify-content-end p-2 bg-dark rounded-top">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center bg-dark rounded-bottom">
                <img id="previewFullImg" class="img-fluid" style="max-height: 75vh;">
                <div class="py-2 text-white bg-dark">
                    <small class="font-bold font-mono" id="previewName"></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let uploadModal = null;
    let previewModal = null;

    $(document).ready(function() {
        uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
        previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    });

    function applyFilter(value) {
        window.location.href = 'images.php?filter=' + value;
    }

    function showUploadModal() {
        uploadModal.show();
    }

    function closeUploadModal() {
        uploadModal.hide();
        document.getElementById('uploadForm').reset();
        document.getElementById('previewContainer').classList.add('d-none');
        document.getElementById('submitBtn').disabled = true;
    }

    function copyUrl(url) {
        const fullUrl = window.location.origin + url.replace('../', '/');
        navigator.clipboard.writeText(fullUrl).then(() => {
            Swal.fire({ icon: 'success', title: 'Đã copy URL!', text: fullUrl, timer: 2000, showConfirmButton: false });
        });
    }

    function deleteImage(path) {
        Swal.fire({
            title: 'Xóa ảnh này?',
            text: 'Hành động này không thể hoàn tác!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'images.php?delete=' + encodeURIComponent(path);
            }
        });
    }

    function previewImage(url, name) {
        document.getElementById('previewFullImg').src = url;
        document.getElementById('previewName').textContent = name;
        previewModal.show();
    }

    function closePreview() {
        previewModal.hide();
    }

    // Drag & Drop
    const dropZone = document.getElementById('dropZone');
    const imageInput = document.getElementById('imageInput');

    if (dropZone) {
        dropZone.addEventListener('click', () => imageInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-primary', 'bg-primary-subtle');
        });
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-primary', 'bg-primary-subtle');
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-primary', 'bg-primary-subtle');
            if (e.dataTransfer.files.length) {
                imageInput.files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
    }

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            if (this.files[0]) handleFileSelect(this.files[0]);
        });
    }

    function handleFileSelect(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('previewContainer').classList.remove('d-none');
            document.getElementById('submitBtn').disabled = false;
        };
        reader.readAsDataURL(file);
    }

    // Upload Form Submit
    document.getElementById('uploadForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang tải...';

        $.ajax({
            url: '../model/upload-image.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                var resp = typeof response === 'object' ? response : JSON.parse(response);
                if (resp.status === 'success') {
                    Swal.fire({title: 'Thành công!', text: 'Đã tải ảnh lên thành công!', icon: 'success', timer: 1500, showConfirmButton: false}).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Lỗi', resp.msg, 'error');
                }
            },
            error: function () {
                Swal.fire('Lỗi', 'Không thể kết nối server', 'error');
            },
            complete: function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-upload"></i> Tải lên';
            }
        });
    });
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>