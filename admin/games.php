<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-gamepad mr-2 text-primary"></i>Quản lý Games
        </h5>
        <button onclick="showAddModal()" class="btn btn-sm btn-primary px-3 shadow-xs">
            <i class="fas fa-plus mr-2"></i>Thêm Game
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4">ID</th>
                        <th>Hình ảnh</th>
                        <th>Tên Game</th>
                        <th>Slug</th>
                        <th>Currency</th>
                        <th>Order</th>
                        <th>Trạng thái</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="games-table" class="small">
                    <tr>
                        <td colspan="8" class="p-4 text-center text-muted">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="game-modal" tabindex="-1" aria-labelledby="gameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold text-dark" id="modal-title">Thêm Game Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="game-form">
                <input type="hidden" id="game-id" name="id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-dark">Tên Game <span class="text-danger">*</span></label>
                            <input type="text" id="game-name" name="name" required class="form-control">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-dark">Slug <span class="text-danger">*</span></label>
                            <input type="text" id="game-slug" name="slug" required class="form-control font-mono">
                            <small class="text-muted">Ví dụ: free-fire, lien-quan-mobile</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-dark">Currency</label>
                            <input type="text" id="game-currency" name="currency" class="form-control" placeholder="Kim Cương, Quân Huy, UC...">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-dark">Color Class</label>
                            <input type="text" id="game-color" name="color" class="form-control font-mono" placeholder="border-blue-500">
                            <small class="text-muted">Tailwind/CSS border color class</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-semibold text-dark">Thứ tự hiển thị</label>
                            <input type="number" id="game-order" name="order_priority" min="0" class="form-control">
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-end pb-2">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="game-active" name="is_active" value="1" checked>
                                <label class="form-check-label font-bold text-dark" for="game-active">Kích hoạt</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold text-dark">Hình ảnh Game</label>
                            <div class="input-group">
                                <input type="text" id="game-image-url" name="image_url" readonly class="form-control bg-light" placeholder="Image URL">
                                <label for="image-upload" class="btn btn-outline-secondary mb-0">
                                    <i class="fas fa-upload mr-1"></i>Upload
                                </label>
                                <input type="file" id="image-upload" accept="image/*" class="d-none">
                            </div>
                            <div id="image-preview" class="mt-2 d-none">
                                <img src="" alt="Preview" class="img-thumbnail" style="max-width: 250px;">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label font-semibold text-dark">Icon Tiền Game</label>
                            <div class="input-group">
                                <input type="text" id="game-currency-icon" name="currency_icon" class="form-control bg-light" placeholder="/uploads/icons/diamond.png">
                                <label for="currency-icon-upload" class="btn btn-outline-secondary mb-0">
                                    <i class="fas fa-gem mr-1"></i>Upload Icon
                                </label>
                                <input type="file" id="currency-icon-upload" accept="image/*" class="d-none">
                            </div>
                            <small class="text-muted">Icon hiển thị cạnh số tiền game (Kim Cương, Quân Huy...)</small>
                            <div id="currency-icon-preview" class="mt-2 d-none">
                                <img src="" alt="Icon Preview" class="img-thumbnail" style="width: 40px; height: 40px; object-fit: contain;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-2"></i>Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let games = [];
    let editingGame = null;
    let gameModal = null;

    $(document).ready(function () {
        gameModal = new bootstrap.Modal(document.getElementById('game-modal'));
        loadGames();

        // Auto-generate slug from name
        $('#game-name').on('input', function () {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[àáạảãâầấậẩẫăằắặẳẵ]/g, 'a')
                .replace(/[èéẹẻẽêềếệểễ]/g, 'e')
                .replace(/[ìíịỉĩ]/g, 'i')
                .replace(/[òóọỏõôồốộổỗơờớợởỡ]/g, 'o')
                .replace(/[ùúụủũưừứựửữ]/g, 'u')
                .replace(/[ỳýỵỷỹ]/g, 'y')
                .replace(/đ/g, 'd')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            $('#game-slug').val(slug);
        });

        // Image upload
        $('#image-upload').on('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                $('#image-preview').removeClass('d-none');
                $('#image-preview img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'game');

            $.ajax({
                url: '../model/upload-image.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    var resp = typeof response === 'object' ? response : JSON.parse(response);
                    if (resp.status === 'success') {
                        $('#game-image-url').val(resp.data.url);
                        Swal.fire({ icon: 'success', title: 'Uploaded!', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire('Error', resp.msg, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Upload failed', 'error');
                }
            });
        });

        // Currency Icon Upload Handler
        $('#currency-icon-upload').on('change', function () {
            const file = this.files[0];
            if (!file) return;

            // Preview
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#currency-icon-preview').removeClass('d-none').find('img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);

            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'avatar'); // Use avatar folder for icons

            $.ajax({
                url: '../model/upload-image.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    var resp = typeof response === 'object' ? response : JSON.parse(response);
                    if (resp.status === 'success') {
                        $('#game-currency-icon').val(resp.data.url);
                        Swal.fire({ icon: 'success', title: 'Icon uploaded!', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire('Error', resp.msg, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Upload failed', 'error');
                }
            });
        });
    });

    function loadGames() {
        $.ajax({
            url: '../model/admin/get-games.php',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                var resp = typeof response === 'object' ? response : JSON.parse(response);
                if (resp.status === 'success') {
                    games = resp.data;
                    renderGamesTable();
                }
            }
        });
    }

    function renderGamesTable() {
        const tbody = $('#games-table');
        tbody.empty();

        if (games.length === 0) {
            tbody.append('<tr><td colspan="8" class="p-4 text-center text-muted">Chưa có game nào</td></tr>');
            return;
        }

        games.forEach(game => {
            const activeHtml = game.is_active == 1
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-danger">Inactive</span>';

            const row = `
            <tr>
                <td class="ps-4 text-muted">#${game.id}</td>
                <td>
                    <img src="${game.image_url}" alt="${game.name}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td class="font-bold text-dark">${game.name}</td>
                <td class="font-mono text-xs">${game.slug}</td>
                <td>${game.currency || '-'}</td>
                <td>${game.order_priority}</td>
                <td>${activeHtml}</td>
                <td class="pe-4 text-center">
                    <button onclick="editGame(${game.id})" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteGame(${game.id}, '${game.name}')" class="btn btn-sm btn-outline-danger" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
            tbody.append(row);
        });
    }

    function showAddModal() {
        editingGame = null;
        $('#modal-title').text('Thêm Game Mới');
        $('#game-form')[0].reset();
        $('#game-id').val('');
        $('#image-preview').addClass('d-none');
        $('#currency-icon-preview').addClass('d-none');
        gameModal.show();
    }

    function editGame(id) {
        const game = games.find(g => g.id == id);
        if (!game) return;

        editingGame = game;
        $('#modal-title').text('Sửa Game: ' + game.name);
        $('#game-id').val(game.id);
        $('#game-name').val(game.name);
        $('#game-slug').val(game.slug);
        $('#game-image-url').val(game.image_url);
        $('#game-currency').val(game.currency);
        $('#game-currency-icon').val(game.currency_icon || '');
        $('#game-color').val(game.color);
        $('#game-order').val(game.order_priority);
        $('#game-active').prop('checked', game.is_active == 1);

        if (game.image_url) {
            $('#image-preview').removeClass('d-none');
            $('#image-preview img').attr('src', game.image_url);
        } else {
            $('#image-preview').addClass('d-none');
        }

        if (game.currency_icon) {
            $('#currency-icon-preview').removeClass('d-none').find('img').attr('src', game.currency_icon);
        } else {
            $('#currency-icon-preview').addClass('d-none');
        }

        gameModal.show();
    }

    function closeModal() {
        gameModal.hide();
    }

    $('#game-form').on('submit', function (e) {
        e.preventDefault();

        const formData = $(this).serialize();
        const url = editingGame ? '../model/admin/update-game.php' : '../model/admin/add-game.php';

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                var resp = typeof response === 'object' ? response : JSON.parse(response);
                if (resp.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Thành công!', text: resp.msg, timer: 1500, showConfirmButton: false });
                    closeModal();
                    loadGames();
                } else {
                    Swal.fire('Lỗi', resp.msg, 'error');
                }
            },
            error: function () {
                Swal.fire('Lỗi', 'Không thể kết nối server', 'error');
            }
        });
    });

    function deleteGame(id, name) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: `Bạn có chắc muốn xóa game "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../model/admin/delete-game.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function (response) {
                        var resp = typeof response === 'object' ? response : JSON.parse(response);
                        if (resp.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Đã xóa!', text: resp.msg, timer: 1500, showConfirmButton: false });
                            loadGames();
                        } else {
                            Swal.fire('Lỗi', resp.msg, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>