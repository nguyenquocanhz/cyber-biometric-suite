<?php
require_once('../config/config.php');
require_once('../config/function.php');
$title = 'ĐĂNG NHẬP HỆ THỐNG';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Admin Panel</title>
    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .login-page {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }
        .btn-telegram {
            background: #2AABEE;
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-telegram:hover {
            background: #229ED9;
            color: #fff;
            box-shadow: 0 4px 12px rgba(42, 171, 238, 0.3);
        }
        .swal2-container { z-index: 99999 !important; }
    </style>
</head>

<body class="login-page d-flex align-items-center justify-content-center min-vh-100">
    <div class="login-box" style="width: 400px; max-width: 90%;">
        <!-- /.login-logo -->
        <div class="card card-outline card-primary">
            <div class="card-header text-center py-4">
                <h3 class="mb-0 font-bold text-primary"><i class="fas fa-shield-halved mr-2"></i>Admin Panel</h3>
                <small class="text-muted">Đăng nhập để quản lý hệ thống</small>
            </div>
            <div class="card-body login-card-body p-4">
                <div id="thongbao"></div>

                <form class="mb-3">
                    <div class="input-group mb-3">
                        <input type="text" id="username" class="form-control" placeholder="Tài khoản" autocomplete="username">
                        <div class="input-group-text">
                            <span class="fas fa-user text-muted"></span>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" id="password" class="form-control" placeholder="Mật khẩu" autocomplete="current-password">
                        <div class="input-group-text">
                            <span class="fas fa-lock text-muted"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" id="Login" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-right-to-bracket mr-2"></i>ĐĂNG NHẬP
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Telegram Login -->
                <?php if ($CMSNT->site('enable_telegram_login') == 1): ?>
                <?php 
                    $tg_method = $CMSNT->site('telegram_login_method') ?: 'widget_redirect';
                    $tg_bot_username = ltrim($CMSNT->site('telegram_bot_username'), '@');
                    $tg_client_id = $CMSNT->site('telegram_client_id');
                    
                    // Trích xuất bot_id từ telegram_bot_token nếu client_id trống
                    $tg_bot_id = $tg_client_id;
                    if (empty($tg_bot_id)) {
                        $bot_token = $CMSNT->site('telegram_bot_token');
                        if (!empty($bot_token) && strpos($bot_token, ':') !== false) {
                            $tg_bot_id = explode(':', $bot_token)[0];
                        }
                    }
                    
                    $tg_ready = false;
                    if ($tg_method === 'new_library' && !empty($tg_client_id)) {
                        $tg_ready = true;
                    } elseif ($tg_method !== 'new_library' && !empty($tg_bot_username) && !empty($tg_bot_id)) {
                        $tg_ready = true;
                    }
                ?>
                <?php if ($tg_ready): ?>
                <div class="mt-3">
                    <!-- Divider -->
                    <div class="d-flex align-items-center mb-3">
                        <hr class="flex-grow-1 my-0 text-muted">
                        <span class="px-2 text-uppercase text-muted fs-6 small">hoặc</span>
                        <hr class="flex-grow-1 my-0 text-muted">
                    </div>

                    <?php if ($tg_method === 'widget_redirect' || $tg_method === 'widget_callback'): ?>
                        <!-- Custom Telegram Button thay cho widget mặc định -->
                        <button type="button" id="tg-custom-btn"
                            class="btn btn-telegram w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            Đăng nhập bằng Telegram
                        </button>

                        <!-- Hidden widget container -->
                        <div id="tg-widget-wrap" style="position:absolute; opacity:0; pointer-events:none; height:0; overflow:hidden;">
                            <?php if ($tg_method === 'widget_redirect'): ?>
                                <script async src="https://telegram.org/js/telegram-widget.js?22" 
                                        data-telegram-login="<?= $tg_bot_username ?>" 
                                        data-size="large" 
                                        data-auth-url="<?= base_url('model/telegram_auth.php') ?>" 
                                        data-request-access="write"></script>
                            <?php else: ?>
                                <script async src="https://telegram.org/js/telegram-widget.js?22" 
                                        data-telegram-login="<?= $tg_bot_username ?>" 
                                        data-size="large" 
                                        data-onauth="onTelegramAuth(user)" 
                                        data-request-access="write"></script>
                            <?php endif; ?>
                        </div>

                        <script type="text/javascript">
                        // Click nút đẹp → trigger click nút Telegram widget ẩn bên trong iframe
                        document.getElementById('tg-custom-btn').addEventListener('click', function() {
                            var iframe = document.querySelector('#tg-widget-wrap iframe');
                            if (iframe) {
                                // Mở popup Telegram trực tiếp
                                var botId = '<?= $tg_bot_id ?>';
                                var origin = encodeURIComponent(window.location.origin);
                                <?php if ($tg_method === 'widget_redirect'): ?>
                                var authUrl = encodeURIComponent('<?= base_url("model/telegram_auth.php") ?>');
                                window.open(
                                    'https://oauth.telegram.org/auth?bot_id=' + botId + '&origin=' + origin + '&request_access=write&return_to=' + authUrl,
                                    'telegram_oauth',
                                    'width=550,height=470,toolbar=no,menubar=no,scrollbars=no'
                                );
                                <?php else: ?>
                                // Callback mode: trigger iframe click
                                iframe.contentWindow.postMessage({event: 'auth_user'}, '*');
                                // Fallback: mở popup
                                window.open(
                                    'https://oauth.telegram.org/auth?bot_id=' + botId + '&origin=' + origin + '&request_access=write',
                                    'telegram_oauth',
                                    'width=550,height=470,toolbar=no,menubar=no,scrollbars=no'
                                );
                                <?php endif; ?>
                            } else {
                                // Widget chưa load xong, thử direct
                                var botId = '<?= $tg_bot_id ?>';
                                var origin = encodeURIComponent(window.location.origin);
                                <?php if ($tg_method === 'widget_redirect'): ?>
                                var authUrl = encodeURIComponent('<?= base_url("model/telegram_auth.php") ?>');
                                window.open(
                                    'https://oauth.telegram.org/auth?bot_id=' + botId + '&origin=' + origin + '&request_access=write&return_to=' + authUrl,
                                    'telegram_oauth',
                                    'width=550,height=470,toolbar=no,menubar=no,scrollbars=no'
                                );
                                <?php else: ?>
                                window.open(
                                    'https://oauth.telegram.org/auth?bot_id=' + botId + '&origin=' + origin + '&request_access=write',
                                    'telegram_oauth',
                                    'width=550,height=470,toolbar=no,menubar=no,scrollbars=no'
                                );
                                <?php endif; ?>
                            }
                        });

                        <?php if ($tg_method === 'widget_callback'): ?>
                        function onTelegramAuth(user) {
                            $.ajax({
                                url: '<?= base_url("model/telegram_auth.php") ?>',
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify(user),
                                beforeSend: function() {
                                    Swal.fire({title: 'Đang xác thực...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
                                },
                                success: function(resp) {
                                    try {
                                        var data = typeof resp === 'object' ? resp : JSON.parse(resp);
                                        if (data.status === 'success') {
                                            Swal.fire({title: 'Thành công', text: data.msg || 'Đăng nhập thành công!', icon: 'success', timer: 1500, showConfirmButton: false})
                                                .then(() => window.location.href = data.redirect || 'index.php');
                                        } else {
                                            Swal.fire('Thất bại', data.msg || 'Xác thực không hợp lệ', 'error');
                                        }
                                    } catch(e) {
                                        Swal.fire('Lỗi', 'Server trả về dữ liệu không hợp lệ', 'error');
                                    }
                                },
                                error: function() {
                                    Swal.fire('Lỗi Kết Nối', 'Không thể kết nối tới máy chủ', 'error');
                                }
                            });
                        }
                        <?php endif; ?>
                        </script>

                    <?php elseif ($tg_method === 'new_library'): ?>
                        <!-- Phương thức 3: Telegram Login Library MỚI -->
                        <script src="https://telegram.org/js/telegram-login.js"></script>
                        <button type="button" id="tg-login-btn"
                            class="btn btn-telegram w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                            </svg>
                            Đăng nhập bằng Telegram
                        </button>
                        <script type="text/javascript">
                        (function() {
                            var clientId = '<?= $tg_client_id ?>';
                            if (!clientId) {
                                document.getElementById('tg-login-btn').disabled = true;
                                document.getElementById('tg-login-btn').classList.add('opacity-50', 'cursor-not-allowed');
                                return;
                            }
                            Telegram.Login.init({ bot_id: clientId }, function(data) {
                                if (!data) return;
                                $.ajax({
                                    url: '<?= base_url("model/telegram_auth.php") ?>',
                                    method: 'POST',
                                    contentType: 'application/json',
                                    data: JSON.stringify({ method: 'new_library', id_token: data.id_token || null, user: data.user || data }),
                                    beforeSend: function() {
                                        Swal.fire({title: 'Đang xác thực...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
                                    },
                                    success: function(resp) {
                                        try {
                                            var result = typeof resp === 'object' ? resp : JSON.parse(resp);
                                            if (result.status === 'success') {
                                                Swal.fire({title: 'Thành công', text: result.msg, icon: 'success', timer: 1500, showConfirmButton: false})
                                                    .then(() => window.location.href = result.redirect || 'index.php');
                                            } else {
                                                Swal.fire('Thất bại', result.msg, 'error');
                                            }
                                        } catch(e) { Swal.fire('Lỗi', 'Dữ liệu không hợp lệ', 'error'); }
                                    },
                                    error: function() { Swal.fire('Lỗi', 'Không thể kết nối', 'error'); }
                                });
                            });
                            document.getElementById('tg-login-btn').addEventListener('click', function() { Telegram.Login.open(); });
                        })();
                        </script>
                    <?php endif; ?>
                </div>
                <?php endif; // end $tg_ready ?>
                <?php endif; // end enable_telegram_login ?>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">&copy; <?= date('Y') ?> Admin Panel &middot; Secured</p>
    </div>

    <script type="text/javascript">
        $("form").on("submit", function (e) {
            e.preventDefault();
            var btn = $('#Login');
            var originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...').prop('disabled', true);

            $.ajax({
                url: "../model/auth_login.php",
                method: "POST",
                data: {
                    username: $("#username").val(),
                    password: $("#password").val()
                },
                success: function (data) {
                    try {
                        var resp = typeof data === 'object' ? data : JSON.parse(data);
                        if (resp.status == 'success') {
                            Swal.fire({
                                title: 'Thành công',
                                text: resp.msg,
                                icon: 'success',
                                allowOutsideClick: false,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = 'index.php';
                            });
                        } else {
                            Swal.fire('Thất bại', resp.msg, 'error');
                        }
                    } catch (e) {
                        Swal.fire('Lỗi Hệ Thống', 'Server trả về dữ liệu không hợp lệ.', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire('Lỗi Kết Nối', 'Không thể kết nối tới máy chủ (' + status + ')', 'error');
                },
                complete: function () {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Enter key support
        $('#username, #password').on('keypress', function(e) {
            if (e.which === 13) { e.preventDefault(); $("form").submit(); }
        });
    </script>
</body>
</html>