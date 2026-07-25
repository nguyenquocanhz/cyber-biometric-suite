<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');
?>

<!-- Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-xs text-muted mb-1 text-uppercase font-semibold">Tổng bài viết</p>
                    <h4 class="mb-0 font-bold text-dark" id="totalPosts">0</h4>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-circle">
                    <i class="fas fa-newspaper text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-xs text-muted mb-1 text-uppercase font-semibold">Đã xuất bản</p>
                    <h4 class="mb-0 font-bold text-success" id="publishedPosts">0</h4>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-circle">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-xs text-muted mb-1 text-uppercase font-semibold">Bản nháp</p>
                    <h4 class="mb-0 font-bold text-warning" id="draftPosts">0</h4>
                </div>
                <div class="p-3 bg-warning-subtle text-warning rounded-circle">
                    <i class="fas fa-edit text-xl"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Posts Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-newspaper mr-2 text-primary"></i>Danh sách Bài viết
        </h5>
        <button onclick="openAddModal()" class="btn btn-sm btn-primary px-3 shadow-xs">
            <i class="fas fa-plus mr-1"></i> Tạo bài mới
        </button>
    </div>

    <div class="card-body p-3 bg-light border-bottom">
        <div class="row g-2">
            <div class="col-12 col-md-6 col-lg-7">
                <input type="text" id="searchInput" placeholder="Tìm kiếm bài viết..." class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3 col-lg-3">
                <select id="statusFilter" class="form-select form-select-sm">
                    <option value="">Tất cả trạng thái</option>
                    <option value="published">Đã xuất bản</option>
                    <option value="draft">Bản nháp</option>
                </select>
            </div>
            <div class="col-12 col-md-3 col-lg-2">
                <button onclick="loadPosts()" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-search mr-1"></i> Lọc
                </button>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle text-nowrap">
                <thead class="table-light">
                    <tr class="text-uppercase small font-bold text-muted">
                        <th class="ps-4" style="width: 80px;">ID</th>
                        <th>Tiêu đề</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="text-center pe-4" style="width: 150px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="postsTableBody" class="small">
                    <tr>
                        <td colspan="5" class="p-4 text-center text-muted">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center">
        <div class="text-xs text-muted" id="paginationInfo">Hiển thị 0-0 trong 0 bài viết</div>
        <div id="pagination" class="d-flex gap-1"></div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-bold text-dark" id="modalTitle">Tạo bài viết mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <form id="postForm">
                    <input type="hidden" id="postId" name="id">

                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" id="postTitle" name="title" required class="form-control">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label font-semibold text-dark">Ảnh đại diện (URL)</label>
                            <input type="url" id="postImage" name="image" placeholder="https://..." class="form-control font-mono">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label font-semibold text-dark">Trạng thái</label>
                            <select id="postStatus" name="status" class="form-select">
                                <option value="draft">Bản nháp</option>
                                <option value="published">Xuất bản</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Mô tả ngắn</label>
                        <textarea id="postDescription" name="description" rows="2" class="form-control" placeholder="Mô tả tóm tắt nội dung hiển thị ở danh sách bài viết..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Danh mục</label>
                        <select id="postCategories" name="categories[]" multiple class="form-select" style="height: 120px;">
                            <?php
                            $categories = $CMSNT->get_list("SELECT * FROM `categories` ORDER BY name ASC");
                            foreach ($categories as $cat):
                                ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều danh mục</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-semibold text-dark">Nội dung bài viết <span class="text-danger">*</span></label>
                        <div id="editorjs" class="border rounded p-3 min-h-[400px] bg-white"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" onclick="savePost()" class="btn btn-primary px-4">
                    <i class="fas fa-save mr-1"></i> Lưu bài viết
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Editor.js CDN - Using versioned jsdelivr UMD builds -->
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.29.1/dist/editorjs.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/paragraph@2.11.6/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@2.8.7/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@1.10.0/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2.7.2/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@2.9.2/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@1.4.2/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/table@2.4.1/dist/table.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/warning@1.4.0/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/marker@1.4.0/dist/bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@1.5.1/dist/bundle.min.js"></script>

<script>
    let editor;
    let currentPage = 1;
    let postModal = null;

    $(document).ready(function () {
        postModal = new bootstrap.Modal(document.getElementById('postModal'));
        loadPosts();
        $('#searchInput').on('keypress', function (e) {
            if (e.which === 13) loadPosts();
        });
    });

    // Initialize Editor.js
    function initEditor(data = null) {
        if (editor) {
            editor.destroy();
        }

        editor = new EditorJS({
            holder: 'editorjs',
            tools: {
                paragraph: {
                    class: Paragraph,
                    inlineToolbar: true
                },
                header: {
                    class: Header,
                    config: {
                        levels: [1, 2, 3, 4, 5, 6],
                        defaultLevel: 2
                    },
                    inlineToolbar: true
                },
                list: {
                    class: List,
                    inlineToolbar: true
                },
                quote: {
                    class: Quote,
                    inlineToolbar: true
                },
                code: {
                    class: CodeTool
                },
                delimiter: Delimiter,
                table: {
                    class: Table,
                    inlineToolbar: true
                },
                warning: {
                    class: Warning,
                    inlineToolbar: true
                },
                marker: {
                    class: Marker,
                    shortcut: 'CMD+SHIFT+M'
                },
                inlineCode: {
                    class: InlineCode,
                    shortcut: 'CMD+SHIFT+C'
                }
            },
            data: data || {},
            placeholder: 'Nhập nội dung bài viết...',
            autofocus: true
        });
    }

    // Load posts
    function loadPosts(page = 1) {
        currentPage = page;
        const search = $('#searchInput').val();
        const status = $('#statusFilter').val();

        $.ajax({
            url: '../model/posts/list.php',
            method: 'GET',
            data: { page, search, status },
            dataType: 'json',
            success: function (response) {
                var resp = typeof response === 'object' ? response : JSON.parse(response);
                if (resp.status === 'success') {
                    renderPosts(resp.data.posts);
                    renderPagination(resp.data.pagination);
                    updateStats(resp.data.stats);
                }
            },
            error: function () {
                $('#postsTableBody').html('<tr><td colspan="5" class="p-4 text-center text-danger">Lỗi tải dữ liệu</td></tr>');
            }
        });
    }

    // Render posts
    function renderPosts(posts) {
        if (posts.length === 0) {
            $('#postsTableBody').html('<tr><td colspan="5" class="p-4 text-center text-muted">Chưa có bài viết nào</td></tr>');
            return;
        }

        let html = '';
        posts.forEach(post => {
            const statusClass = post.status === 'published' ? 'bg-success' : 'bg-warning text-dark';
            const statusText = post.status === 'published' ? 'Đã xuất bản' : 'Bản nháp';

            html += `
            <tr>
                <td class="ps-4 text-muted font-mono">#${post.id}</td>
                <td>
                    <div class="font-bold text-dark mb-0">${post.title}</div>
                    <small class="text-muted font-mono">/${post.slug}</small>
                </td>
                <td>
                    <span class="badge ${statusClass}">${statusText}</span>
                </td>
                <td class="text-muted">${post.created_at}</td>
                <td class="pe-4 text-center">
                    <div class="d-inline-flex gap-1">
                        <button onclick="editPost(${post.id})" class="btn btn-sm btn-outline-primary px-2" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deletePost(${post.id})" class="btn btn-sm btn-outline-danger px-2" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                        <a href="../pages/news_detail.php?id=${post.id}" target="_blank" class="btn btn-sm btn-outline-success px-2" title="Xem">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
        `;
        });

        $('#postsTableBody').html(html);
    }

    // Render pagination
    function renderPagination(pagination) {
        const from = pagination.total_records === 0 ? 0 : (pagination.current_page - 1) * pagination.per_page + 1;
        const to = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);
        $('#paginationInfo').text(`Hiển thị ${from}-${to} trong ${pagination.total_records} bài viết`);

        let html = '';
        if (pagination.current_page > 1) {
            html += `<button onclick="loadPosts(${pagination.current_page - 1})" class="btn btn-xs btn-outline-secondary"><i class="fas fa-chevron-left"></i></button>`;
        }

        for (let i = 1; i <= pagination.total_pages; i++) {
            const active = i === pagination.current_page ? 'btn-primary text-white' : 'btn-outline-secondary';
            html += `<button onclick="loadPosts(${i})" class="btn btn-xs ${active}">${i}</button>`;
        }

        if (pagination.current_page < pagination.total_pages) {
            html += `<button onclick="loadPosts(${pagination.current_page + 1})" class="btn btn-xs btn-outline-secondary"><i class="fas fa-chevron-right"></i></button>`;
        }

        $('#pagination').html(html);
    }

    // Update stats
    function updateStats(stats) {
        $('#totalPosts').text(stats.total);
        $('#publishedPosts').text(stats.published);
        $('#draftPosts').text(stats.draft);
    }

    // Open add modal
    function openAddModal() {
        $('#modalTitle').text('Tạo bài viết mới');
        $('#postForm')[0].reset();
        $('#postId').val('');
        initEditor();
        postModal.show();
    }

    // Close modal
    function closeModal() {
        postModal.hide();
        if (editor) editor.destroy();
    }

    // Edit post
    function editPost(id) {
        $.ajax({
            url: '../model/posts/get.php',
            method: 'GET',
            data: { id },
            dataType: 'json',
            success: function (response) {
                var resp = typeof response === 'object' ? response : JSON.parse(response);
                if (resp.status === 'success') {
                    const post = resp.data;
                    $('#modalTitle').text('Chỉnh sửa bài viết');
                    $('#postId').val(post.id);
                    $('#postTitle').val(post.title);
                    $('#postDescription').val(post.description);
                    $('#postImage').val(post.image);
                    
                    // Selected Categories
                    $('#postCategories option').prop('selected', false);
                    if (post.categories) {
                        post.categories.forEach(catId => {
                            $(`#postCategories option[value="${catId}"]`).prop('selected', true);
                        });
                    }
                    
                    $('#postStatus').val(post.status);

                    const editorData = post.content_json ? JSON.parse(post.content_json) : {};
                    initEditor(editorData);

                    postModal.show();
                } else {
                    Swal.fire('Lỗi', resp.msg, 'error');
                }
            }
        });
    }

    // Save post
    async function savePost() {
        try {
            const outputData = await editor.save();
            const content_json = JSON.stringify(outputData);

            const formData = new FormData($('#postForm')[0]);
            formData.append('content_json', content_json);

            const postId = $('#postId').val();
            const url = postId ? '../model/posts/update.php' : '../model/posts/create.php';

            $.ajax({
                url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    var resp = typeof response === 'object' ? response : JSON.parse(response);
                    if (resp.status === 'success') {
                        Swal.fire({title: 'Thành công!', text: resp.msg, icon: 'success', timer: 1500, showConfirmButton: false});
                        closeModal();
                        loadPosts(currentPage);
                    } else {
                        Swal.fire('Lỗi', resp.msg, 'error');
                    }
                }
            });
        } catch (error) {
            Swal.fire('Lỗi', 'Lỗi khi lưu nội dung: ' + error.message, 'error');
        }
    }

    // Delete post
    function deletePost(id) {
        Swal.fire({
            title: 'Xác nhận xóa?',
            text: 'Bạn có chắc muốn xóa bài viết này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../model/posts/delete.php',
                    method: 'POST',
                    data: { id },
                    dataType: 'json',
                    success: function (response) {
                        var resp = typeof response === 'object' ? response : JSON.parse(response);
                        if (resp.status === 'success') {
                            Swal.fire({title: 'Đã xóa!', text: resp.msg, icon: 'success', timer: 1500, showConfirmButton: false});
                            loadPosts(currentPage);
                        } else {
                            Swal.fire('Lỗi', resp.msg, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>