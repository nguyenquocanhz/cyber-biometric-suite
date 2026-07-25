<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

// Handle Add Comment
if (isset($_POST['btnAdd'])) {
    $username = check_string($_POST['username']);
    $content = check_string($_POST['content']);
    $status = check_string($_POST['status']);

    $CMSNT->insert("comments", [
        'username' => $username,
        'content' => $content,
        'status' => $status,
        'time' => date('Y-m-d H:i:s')
    ]);
    echo '<script>Swal.fire("Thành công", "Thêm bình luận thành công!", "success").then(() => window.location.reload());</script>';
}

// Handle Update Comment
if (isset($_POST['btnUpdate'])) {
    $id = check_string($_POST['id']);
    $username = check_string($_POST['username']);
    $content = check_string($_POST['content']);
    $status = check_string($_POST['status']);

    $CMSNT->update("comments", [
        'username' => $username,
        'content' => $content,
        'status' => $status
    ], " `id` = '$id' ");
    echo '<script>Swal.fire("Thành công", "Cập nhật thành công!", "success").then(() => window.location.reload());</script>';
}

// Handle Delete Comment
if (isset($_GET['delete'])) {
    $id = check_string($_GET['delete']);
    $CMSNT->remove("comments", " `id` = '$id' ");
    echo '<script>Swal.fire("Thành công", "Xóa thành công!", "success").then(() => location.href = "comments.php");</script>';
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-comments mr-2 text-primary"></i>Quản lý Bình luận
        </h5>

        <div class="d-inline-flex gap-2">
            <!-- Search Form -->
            <form method="GET" class="d-inline-flex gap-1 mb-0">
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Tìm tên, nội dung..." class="form-control form-control-sm">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($_GET['search'])): ?>
                    <a href="/admin/comments.php" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </form>

            <button onclick="openAddModal()" class="btn btn-sm btn-primary px-3 shadow-xs">
                <i class="fas fa-plus mr-1"></i> Thêm
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4" style="width: 80px;">ID</th>
                        <th>Tên người dùng</th>
                        <th>Nội dung</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th class="text-center pe-4" style="width: 150px;">Hành động</th>
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
                        $where .= " AND (`username` LIKE '%$search%' OR `content` LIKE '%$search%')";
                    }

                    // Get total count
                    $total_row = $CMSNT->get_row("SELECT COUNT(*) as total FROM `comments` WHERE $where");
                    $total = $total_row['total'];

                    // Get paginated list
                    $list = $CMSNT->get_list("SELECT * FROM `comments` WHERE $where ORDER BY `id` DESC LIMIT $start, $per_page");
                    if ($list) {
                        foreach ($list as $row) { ?>
                            <tr>
                                <td class="ps-4 text-muted font-mono">#<?= $row['id'] ?></td>
                                <td class="font-bold text-dark"><?= htmlspecialchars($row['username']) ?></td>
                                <td>
                                    <div style="max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['content']) ?>">
                                        <?= htmlspecialchars($row['content']) ?>
                                    </div>
                                </td>
                                <td class="text-muted small"><?= $row['time'] ?></td>
                                <td>
                                    <span class="badge <?= $row['status'] == 1 ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $row['status'] == 1 ? 'Hiển thị' : 'Ẩn' ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-center">
                                    <div class="d-inline-flex gap-1">
                                        <button onclick='openEditModal(<?= json_encode($row) ?>)' class="btn btn-sm btn-outline-primary px-2" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteComment(<?= $row['id'] ?>)" class="btn btn-sm btn-outline-danger px-2" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php }
                    } else {
                        echo '<tr><td colspan="6" class="p-4 text-center text-muted">Chưa có bình luận nào.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white border-top">
        <?php
        $pagination_url = '/admin/comments.php?' . (!empty($search) ? 'search=' . urlencode($search) . '&' : '');
        echo admin_phantrang($pagination_url, $start, $total, $per_page);
        ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="commentModal" tabindex="-1" aria-labelledby="commentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold text-dark" id="modalTitle">Thêm Bình luận mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="commentForm">
                <input type="hidden" name="id" id="commentId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Tên người dùng</label>
                        <input type="text" name="username" id="commentUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Nội dung</label>
                        <textarea name="content" id="commentContent" rows="4" class="form-control" style="resize: none;" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Trạng thái</label>
                        <select name="status" id="commentStatus" class="form-select">
                            <option value="1">Hiển thị</option>
                            <option value="0">Ẩn</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" id="submitBtn" name="btnAdd" class="btn btn-primary px-4">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let commentModal = null;
    
    $(document).ready(function() {
        commentModal = new bootstrap.Modal(document.getElementById('commentModal'));
    });

    function openAddModal() {
        $('#modalTitle').text('Thêm Bình luận mới');
        $('#commentForm')[0].reset();
        $('#commentId').val('');
        $('#commentUsername').val('Khách');
        $('#submitBtn').attr('name', 'btnAdd').text('Thêm mới');
        commentModal.show();
    }

    function openEditModal(data) {
        $('#modalTitle').text('Chỉnh sửa Bình luận #' + data.id);
        $('#commentId').val(data.id);
        $('#commentUsername').val(data.username);
        $('#commentContent').val(data.content);
        $('#commentStatus').val(data.status);
        $('#submitBtn').attr('name', 'btnUpdate').text('Lưu thay đổi');
        commentModal.show();
    }

    function closeModal() {
        commentModal.hide();
    }

    function deleteComment(id) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: "Bạn có chắc chắn muốn xóa bình luận này?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'comments.php?delete=' + id;
            }
        });
    }
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>