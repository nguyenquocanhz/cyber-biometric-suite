<?php
require_once(__DIR__ . '/includes/header.php');
require_once(__DIR__ . '/includes/sidebar.php');
require_once(__DIR__ . '/includes/navbar.php');

if (isset($_POST['btnSaveOption'])) {
    // Helper to UPSERT option
    function saveOption($CMSNT, $name, $value)
    {
        if ($CMSNT->get_row("SELECT * FROM `options` WHERE `name` = '$name' ")) {
            $CMSNT->update("options", ['value' => $value], " `name` = '$name' ");
        } else {
            $CMSNT->insert("options", ['name' => $name, 'value' => $value]);
        }
    }

    // Handle checkbox fields explicitly
    $checkbox_fields = ['enable_turnstile_captcha', 'enable_recaptcha_v2', 'enable_image_captcha', 'enable_math_captcha', 'enable_puzzle_captcha', 'enable_proxy_block', 'enable_telegram_login'];
    foreach ($checkbox_fields as $field) {
        $value = isset($_POST[$field]) ? '1' : '0';
        saveOption($CMSNT, $field, $value);
    }

    // Handle other fields
    foreach ($_POST as $key => $value) {
        if ($key === 'btnSaveOption' || in_array($key, $checkbox_fields))
            continue;
        saveOption($CMSNT, $key, $value);
    }

    admin_log("Vừa cập nhật cài đặt hệ thống");

    echo '<script>Swal.fire("Thành công", "Lưu cài đặt thành công!", "success");</script>';
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 bg-white border-bottom">
        <h5 class="card-title mb-0 font-bold text-dark">
            <i class="fas fa-cogs mr-2 text-primary"></i>Cài đặt Hệ thống
        </h5>
    </div>

    <div class="card-body p-4">
        <form action="" method="POST">

            <!-- General Settings -->
            <div class="mb-4 pb-3 border-bottom">
                <h6 class="font-bold text-primary text-uppercase mb-3">
                    <i class="fas fa-info-circle mr-1"></i> Thông tin chung
                </h6>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Tên Website</label>
                        <input type="text" name="tenweb" value="<?= $CMSNT->site('tenweb') ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Mô tả Website</label>
                        <input type="text" name="mota" value="<?= $CMSNT->site('mota') ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Từ khóa (SEO)</label>
                        <input type="text" name="tukhoa" value="<?= $CMSNT->site('tukhoa') ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Logo URL</label>
                        <input type="text" name="logo" value="<?= $CMSNT->site('logo') ?>" class="form-control">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Favicon URL</label>
                        <input type="text" name="favicon" value="<?= $CMSNT->site('favicon') ?>" placeholder="/favicon.ico" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="mb-4 pb-3 border-bottom">
                <h6 class="font-bold text-purple text-uppercase mb-3">
                    <i class="fas fa-bell mr-1"></i> Thông báo
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-semibold text-dark">Nội dung thông báo nạp thẻ (Hiển thị trang chủ)</label>
                        <textarea name="thongbao" rows="4" class="form-control"><?= $CMSNT->site('thongbao') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Card Gateway Settings -->
            <div class="mb-4 pb-3 border-bottom">
                <h6 class="font-bold text-success text-uppercase mb-3">
                    <i class="fas fa-wallet mr-1"></i> Cấu hình Cổng gạch thẻ
                </h6>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Chế độ gạch thẻ</label>
                        <select name="card_approval_mode" class="form-select">
                            <option value="manual" <?= $CMSNT->site('card_approval_mode') == 'manual' ? 'selected' : '' ?>>Nạp chậm (Duyệt thủ công)</option>
                            <option value="auto" <?= $CMSNT->site('card_approval_mode') == 'auto' ? 'selected' : '' ?>>Nạp tự động (Đẩy qua API)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Cổng gạch thẻ hoạt động</label>
                        <select name="active_card_gateway" class="form-select">
                            <option value="doithe1s" <?= $CMSNT->site('active_card_gateway') == 'doithe1s' ? 'selected' : '' ?>>DoiThe1s.vn</option>
                            <option value="thesieure" <?= $CMSNT->site('active_card_gateway') == 'thesieure' ? 'selected' : '' ?>>TheSieuRe.com</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Partner ID (Mã đối tác)</label>
                        <input type="text" name="partner_id" value="<?= $CMSNT->site('partner_id') ?>" placeholder="Nhập Partner ID của bạn" class="form-control font-mono">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label font-semibold text-dark">Partner Key (Khóa đối tác)</label>
                        <input type="text" name="partner_key" value="<?= $CMSNT->site('partner_key') ?>" placeholder="Nhập Partner Key của bạn" class="form-control font-mono">
                    </div>
                </div>
            </div>

            <!-- Security / Captcha Settings -->
            <div class="mb-4 pb-3 border-bottom">
                <h6 class="font-bold text-danger text-uppercase mb-3">
                    <i class="fas fa-shield-alt mr-1"></i> Bảo mật / Captcha
                </h6>
                <div class="row g-3">

                    <!-- Cloudflare Turnstile -->
                    <div class="col-12">
                        <div class="card bg-light border-0 p-3 mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enable_turnstile" name="enable_turnstile_captcha" value="1" <?= $CMSNT->site('enable_turnstile_captcha') == 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label font-bold text-dark" for="enable_turnstile">
                                        <i class="fab fa-cloudflare text-warning mr-1"></i> Cloudflare Turnstile
                                    </label>
                                </div>
                                <span class="badge bg-primary text-white">Recommended</span>
                            </div>
                            <p class="text-xs text-muted mb-3">Captcha miễn phí, nhanh và thân thiện với người dùng từ Cloudflare.</p>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label font-semibold text-dark small mb-1">Site Key</label>
                                    <input type="text" name="turnstile_site_key" value="<?= $CMSNT->site('turnstile_site_key') ?: '0x4AAAAAABHTj-O0ZB-5Ejdh' ?>" class="form-control form-control-sm font-mono">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label font-semibold text-dark small mb-1">Secret Key</label>
                                    <input type="text" name="turnstile_secret_key" value="<?= $CMSNT->site('turnstile_secret_key') ?>" class="form-control form-control-sm font-mono">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Image Captcha (GD Library) -->
                    <div class="col-12 col-md-6">
                        <div class="card bg-light border-0 p-3 h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable_image_captcha" name="enable_image_captcha" value="1" <?= $CMSNT->site('enable_image_captcha') == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label font-bold text-dark" for="enable_image_captcha">
                                    <i class="fas fa-image text-success mr-1"></i> Captcha ảnh (Local GD)
                                </label>
                            </div>
                            <p class="text-xs text-muted mb-0">Captcha dạng mã chữ và số ngẫu nhiên được vẽ trực tiếp trên máy chủ bằng thư viện GD.</p>
                        </div>
                    </div>

                    <!-- Math Captcha -->
                    <div class="col-12 col-md-6">
                        <div class="card bg-light border-0 p-3 h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable_math_captcha" name="enable_math_captcha" value="1" <?= $CMSNT->site('enable_math_captcha') == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label font-bold text-dark" for="enable_math_captcha">
                                    <i class="fas fa-calculator text-primary mr-1"></i> Captcha tính toán (Math Challenge)
                                </label>
                            </div>
                            <p class="text-xs text-muted mb-0">Yêu cầu giải quyết phép toán số học ngẫu nhiên (ví dụ: 5 + 3 = ?). Cực nhẹ và bảo mật.</p>
                        </div>
                    </div>

                    <!-- Puzzle Captcha -->
                    <div class="col-12 col-md-6">
                        <div class="card bg-light border-0 p-3 h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable_puzzle_captcha" name="enable_puzzle_captcha" value="1" <?= $CMSNT->site('enable_puzzle_captcha') == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label font-bold text-dark" for="enable_puzzle_captcha">
                                    <i class="fas fa-puzzle-piece text-purple mr-1"></i> Captcha kéo mảnh ghép
                                </label>
                            </div>
                            <p class="text-xs text-muted mb-0">Kéo mảnh ghép vào đúng vị trí trống trên hình ảnh. Trải nghiệm người dùng tốt, chống bot tuyệt đối.</p>
                        </div>
                    </div>

                    <!-- Proxy Block -->
                    <div class="col-12 col-md-6">
                        <div class="card bg-light border-0 p-3 h-100">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="enable_proxy_block" name="enable_proxy_block" value="1" <?= $CMSNT->site('enable_proxy_block') == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label font-bold text-dark" for="enable_proxy_block">
                                    <i class="fas fa-shield-alt text-danger mr-1"></i> Chặn kết nối Proxy / VPN
                                </label>
                            </div>
                            <p class="text-xs text-muted mb-0">Ngăn chặn spam nạp thẻ bằng cách chặn các kết nối Proxy, VPN hoặc hosting.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Telegram Integration Settings -->
            <div class="mb-4 pb-3 border-bottom">
                <h6 class="font-bold text-primary text-uppercase mb-3">
                    <i class="fab fa-telegram mr-1"></i> Tích hợp Telegram
                </h6>
                <div class="card border border-primary-subtle p-3 bg-primary-subtle bg-opacity-10 mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="enable_telegram_login" name="enable_telegram_login" value="1" <?= $CMSNT->site('enable_telegram_login') == 1 ? 'checked' : '' ?>>
                            <label class="form-check-label font-bold text-dark" for="enable_telegram_login">
                                Kích hoạt tích hợp Telegram
                            </label>
                        </div>
                        <a href="https://core.telegram.org/bots/telegram-login" target="_blank" class="small text-primary text-decoration-none">
                            <i class="fas fa-external-link-alt mr-1"></i>Tài liệu API
                        </a>
                    </div>
                    <p class="text-xs text-muted mb-3">Cho phép đăng nhập nhanh bằng tài khoản Telegram ngoài trang chủ admin, đồng thời tự động gửi thông báo trực tiếp về Telegram Chat ID khi có thẻ nạp chậm mới.</p>

                    <!-- Login Method Selector -->
                    <div class="mb-3 p-3 bg-white rounded border border-light-subtle">
                        <label class="form-label font-bold text-dark small"><i class="fas fa-exchange-alt text-primary mr-1"></i>Phương thức đăng nhập Telegram</label>
                        <select name="telegram_login_method" id="telegram_login_method" class="form-select form-select-sm">
                            <option value="widget_redirect" <?= $CMSNT->site('telegram_login_method') == 'widget_redirect' || !$CMSNT->site('telegram_login_method') ? 'selected' : '' ?>>
                                🔗 Legacy Widget — Redirect URL (data-auth-url)
                            </option>
                            <option value="widget_callback" <?= $CMSNT->site('telegram_login_method') == 'widget_callback' ? 'selected' : '' ?>>
                                ⚡ Legacy Widget — JS Callback (data-onauth)
                            </option>
                            <option value="new_library" <?= $CMSNT->site('telegram_login_method') == 'new_library' ? 'selected' : '' ?>>
                                🚀 Telegram Login Library MỚI (telegram-login.js + OIDC)
                            </option>
                        </select>
                        <div class="mt-2 text-xs text-muted" id="method_help">
                            <p id="help_widget_redirect" class="<?= ($CMSNT->site('telegram_login_method') == 'widget_redirect' || !$CMSNT->site('telegram_login_method')) ? '' : 'd-none' ?>">
                                <i class="fas fa-info-circle text-primary"></i> Nhúng nút bằng <code>telegram-widget.js</code>, khi user xác thực xong sẽ redirect tới <code>data-auth-url</code> kèm hash. Cần <strong>Bot Username</strong> + <strong>Bot Token</strong>.
                            </p>
                            <p id="help_widget_callback" class="d-none <?= $CMSNT->site('telegram_login_method') == 'widget_callback' ? 'd-block' : '' ?>">
                                <i class="fas fa-info-circle text-success"></i> Nhúng nút bằng <code>telegram-widget.js</code>, khi user xác thực xong sẽ gọi hàm JS callback (<code>data-onauth</code>) rồi gửi AJAX lên server verify. Cần <strong>Bot Username</strong> + <strong>Bot Token</strong>.
                            </p>
                            <p id="help_new_library" class="d-none <?= $CMSNT->site('telegram_login_method') == 'new_library' ? 'd-block' : '' ?>">
                                <i class="fas fa-info-circle text-warning"></i> Dùng thư viện mới <code>telegram-login.js</code> với <code>Telegram.Login.init()</code> + <code>Telegram.Login.open()</code>. Hỗ trợ OIDC, popup đăng nhập hiện đại. Cần <strong>Client ID</strong> từ BotFather.
                            </p>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small font-semibold text-dark">Bot Username <span class="text-muted">(Không có @)</span></label>
                            <input type="text" name="telegram_bot_username" value="<?= $CMSNT->site('telegram_bot_username') ?>" placeholder="ShopKCFF_Bot" class="form-control form-control-sm font-mono">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small font-semibold text-dark">Bot Token <span class="text-muted">(Dùng verify & gửi chat)</span></label>
                            <input type="text" name="telegram_bot_token" value="<?= $CMSNT->site('telegram_bot_token') ?>" placeholder="123456789:ABCdefGh..." class="form-control form-control-sm font-mono">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small font-semibold text-dark">Client ID <span class="text-muted">(Chỉ cho Thư viện mới)</span></label>
                            <input type="text" name="telegram_client_id" value="<?= $CMSNT->site('telegram_client_id') ?>" class="form-control form-control-sm font-mono">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small font-semibold text-dark">Chat ID nhận thông báo</label>
                            <input type="text" name="telegram_chat_id" value="<?= $CMSNT->site('telegram_chat_id') ?>" placeholder="-100123..." class="form-control form-control-sm font-mono">
                        </div>
                    </div>
                </div>
            </div>

            <!-- REST API Config -->
            <div class="mb-4 pb-3 border-bottom">
                <h6 class="font-bold text-dark text-uppercase mb-3">
                    <i class="fas fa-key mr-1"></i> REST API Cổng Nạp Thẻ
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-semibold text-dark">Hệ thống API Key (Dùng để xác thực kết nối)</label>
                        <input type="text" name="site_api_key" value="<?= $CMSNT->site('site_api_key') ?: 'shopkcff_secret_api_key_123' ?>" class="form-control font-mono mb-3">
                        <div class="card bg-dark text-light p-3">
                            <div class="text-warning font-mono small mb-1">// Endpoint tài liệu tích hợp:</div>
                            <div class="font-mono text-xs">- Gửi thẻ (POST): <span class="text-success"><?= base_url('api/napthe.php?action=submit&api_key=...') ?></span></div>
                            <div class="font-mono text-xs">- Kiểm tra (GET/POST): <span class="text-success"><?= base_url('api/napthe.php?action=check&api_key=...&request_id=...') ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" name="btnSaveOption" class="btn btn-primary btn-lg px-5 shadow-sm">
                    <i class="fas fa-save mr-2"></i> Lưu Cài Đặt
                </button>
            </div>

        </form>
    </div>
</div>

<script>
document.getElementById('telegram_login_method')?.addEventListener('change', function() {
    document.getElementById('help_widget_redirect').classList.add('d-none');
    document.getElementById('help_widget_callback').classList.add('d-none');
    document.getElementById('help_new_library').classList.add('d-none');
    document.getElementById('help_' + this.value).classList.remove('d-none');
});
</script>

<?php
require_once(__DIR__ . '/includes/footer.php');
?>