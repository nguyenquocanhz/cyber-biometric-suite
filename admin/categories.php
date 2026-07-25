<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add Category
if (isset($_POST['btnAdd'])) {
    $name = check_string($_POST['name']);
    $description = check_string($_POST['description'] ?? '');
    $icon = check_string($_POST['icon'] ?? 'fa-folder');
    $color = check_string($_POST['color'] ?? '#3B82F6');
    $slug = create_slug($name);

    // Check slug uniqueness
    $existing = $CMSNT->get_row("SELECT id FROM `categories` WHERE `slug` = '$slug'");
    if ($existing) {
        $slug = $slug . '-' . time();
    }

    $CMSNT->insert('categories', [
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'icon' => $icon,
        'color' => $color,
        'created_at' => gettime()
    ]);

    admin_log("Tạo danh mục mới: $name");
    echo '<script>Swal.fire("Thành công", "Đã tạo danh mục!", "success").then(() => location.href = "categories.php");</script>';
}

// Handle Edit Category
if (isset($_POST['btnEdit'])) {
    $id = (int) $_POST['id'];
    $name = check_string($_POST['name']);
    $description = check_string($_POST['description'] ?? '');
    $icon = check_string($_POST['icon'] ?? 'fa-folder');
    $color = check_string($_POST['color'] ?? '#3B82F6');

    $CMSNT->update('categories', [
        'name' => $name,
        'description' => $description,
        'icon' => $icon,
        'color' => $color
    ], "`id` = '$id'");

    admin_log("Cập nhật danh mục #$id: $name");
    echo '<script>Swal.fire("Thành công", "Đã cập nhật danh mục!", "success").then(() => location.href = "categories.php");</script>';
}

// Handle Delete Category
if (isset($_POST['btnDelete'])) {
    $id = (int) $_POST['id'];
    $cat = $CMSNT->get_row("SELECT name FROM `categories` WHERE `id` = '$id'");

    $CMSNT->remove('categories', "`id` = '$id'");
    admin_log("Xóa danh mục #$id: " . $cat['name']);
    echo '<script>Swal.fire("Thành công", "Đã xóa danh mục!", "success").then(() => location.href = "categories.php");</script>';
}

$categories = $CMSNT->get_list("SELECT * FROM `categories` ORDER BY id DESC");
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-folder-open mr-2 text-primary"></i>Quản lý Danh mục Bài viết
        </h5>
        <button onclick="openAddModal()" class="btn btn-sm btn-primary px-3 shadow-xs">
            <i class="fas fa-plus mr-1"></i> Tạo danh mục mới
        </button>
    </div>
    
    <div class="card-body p-4">
        <!-- Categories Grid -->
        <div class="row g-3">
            <?php if (empty($categories)): ?>
                <div class="col-12 text-center text-muted p-4">Chưa có danh mục nào. Hãy bấm "Tạo danh mục mới".</div>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card shadow-sm h-100 border-start border-4" style="border-left-color: <?= $cat['color'] ?> !important;">
                            <div class="card-body p-3 d-flex flex-column h-100">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white text-xl"
                                            style="background-color: <?= $cat['color'] ?>; width: 44px; height: 44px; flex-shrink:0;">
                                            <i class="fas <?= $cat['icon'] ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold text-dark mb-0"><?= htmlspecialchars($cat['name']) ?></h6>
                                            <small class="text-muted font-mono">/<?= htmlspecialchars($cat['slug']) ?></small>
                                        </div>
                                    </div>
                                    <div class="d-inline-flex gap-1">
                                        <button onclick='editCategory(<?= json_encode($cat) ?>)' class="btn btn-xs btn-outline-primary" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Xóa danh mục này?')" class="mb-0">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" name="btnDelete" class="btn btn-xs btn-outline-danger" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-muted small mb-3">
                                    <?= htmlspecialchars($cat['description'] ?: 'Chưa có mô tả') ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-auto text-xs text-muted">
                                    <span>
                                        <i class="fas fa-newspaper mr-1"></i>
                                        <?= $cat['post_count'] ?> bài viết
                                    </span>
                                    <span>
                                        <?= date('d/m/Y', strtotime($cat['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold text-dark" id="modalTitle">Tạo danh mục mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="categoryForm">
                <input type="hidden" name="id" id="catId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-semibold text-dark">Tên danh mục <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="catName" required class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold text-dark">Mô tả</label>
                            <textarea name="description" id="catDesc" rows="3" class="form-control" placeholder="Mô tả ngắn gọn về danh mục..."></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-dark">Icon (FontAwesome)</label>
                            <input type="text" name="icon" id="catIcon" value="fa-folder" placeholder="fa-folder" class="form-control font-mono">
                            <small class="text-muted">Ví dụ: fa-newspaper, fa-calendar, fa-book</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-dark">Màu sắc đại diện</label>
                            <input type="color" name="color" id="catColor" value="#3B82F6" class="form-control form-control-color w-100" style="height: 38px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="btnAdd" id="btnSubmit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let categoryModal = null;
    
    $(document).ready(function() {
        categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    });

    function openAddModal() {
        $('#modalTitle').text('Tạo danh mục mới');
        $('#categoryForm')[0].reset();
        $('#catId').val('');
        $('#btnSubmit').attr('name', 'btnAdd').html('<i class="fas fa-save mr-1"></i> Lưu');
        categoryModal.show();
    }

    function editCategory(cat) {
        $('#modalTitle').text('Chỉnh sửa danh mục');
        $('#catId').val(cat.id);
        $('#catName').val(cat.name);
        $('#catDesc').val(cat.description);
        $('#catIcon').val(cat.icon);
        $('#catColor').val(cat.color);
        $('#btnSubmit').attr('name', 'btnEdit').html('<i class="fas fa-save mr-1"></i> Cập nhật');
        categoryModal.show();
    }

    function closeModal() {
        categoryModal.hide();
    }
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>