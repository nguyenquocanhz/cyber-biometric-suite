<?php
if (!defined('IN_ADMIN'))
    die('Direct access denied');
?>
<!-- Sidebar -->
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <!-- Sidebar Brand -->
    <div class="sidebar-brand">
        <!-- Brand Link -->
        <a href="/admin/" class="brand-link text-decoration-none">
            <span class="brand-text font-weight-light text-primary font-bold">ShopKCFF Admin</span>
        </a>
    </div>
    
    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="/admin/" class="nav-link <?= $_SERVER['SCRIPT_NAME'] == '/admin/index.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt text-primary"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-header">VẬN HÀNH</li>
                
                <li class="nav-item">
                    <a href="/admin/users.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'users.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users text-info"></i>
                        <p>Thành viên</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/cards.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'cards.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-money-bill-wave text-success"></i>
                        <p>Nạp thẻ</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/orders.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'orders.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-shopping-cart text-warning"></i>
                        <p>Đơn hàng</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/admin/giftcodes.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'giftcodes.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-gift text-danger"></i>
                        <p>Giftcode</p>
                    </a>
                </li>
                
                <li class="nav-header">NỘI DUNG</li>
                
                <!-- Dropdown: Sản phẩm & Game -->
                <?php
                $game_active = (strpos($_SERVER['SCRIPT_NAME'], 'games.php') !== false || strpos($_SERVER['SCRIPT_NAME'], 'categories.php') !== false);
                ?>
                <li class="nav-item <?= $game_active ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $game_active ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-layer-group text-purple-400"></i>
                        <p>Sản phẩm & Game</p>
                        <i class="nav-arrow bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/admin/games.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'games.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Quản lý Games</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="/admin/posts.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'posts.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-newspaper text-orange-400"></i>
                        <p>Tin tức</p>
                    </a>
                </li>
                
                <!-- Dropdown: Giao diện -->
                <?php
                $ui_active = (strpos($_SERVER['SCRIPT_NAME'], 'sliders.php') !== false || strpos($_SERVER['SCRIPT_NAME'], 'ads.php') !== false || strpos($_SERVER['SCRIPT_NAME'], 'images.php') !== false);
                ?>
                <li class="nav-item <?= $ui_active ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $ui_active ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-images text-warning"></i>
                        <p>Giao diện</p>
                        <i class="nav-arrow bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/admin/sliders.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'sliders.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon text-xs"></i>
                                <p>Sliders</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/ads.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'ads.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon text-xs"></i>
                                <p>Quảng cáo</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/images.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'images.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon text-xs"></i>
                                <p>Hình ảnh</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="/admin/comments.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'comments.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-comments text-cyan-400"></i>
                        <p>Bình luận</p>
                    </a>
                </li>
                
                <li class="nav-header">HỆ THỐNG</li>
                
                <!-- Dropdown: Cấu hình -->
                <?php
                $config_active = (strpos($_SERVER['SCRIPT_NAME'], 'settings.php') !== false || strpos($_SERVER['SCRIPT_NAME'], 'card-fees.php') !== false || strpos($_SERVER['SCRIPT_NAME'], 'telcos.php') !== false || strpos($_SERVER['SCRIPT_NAME'], 'packages.php') !== false);
                ?>
                <li class="nav-item <?= $config_active ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $config_active ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cogs text-light"></i>
                        <p>Cấu hình</p>
                        <i class="nav-arrow bi bi-chevron-right"></i>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="/admin/settings.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'settings.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon text-xs"></i>
                                <p>Cài đặt chung</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/card-fees.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'card-fees.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon text-xs"></i>
                                <p>Chiết khấu thẻ</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/telcos.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'telcos.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon text-xs"></i>
                                <p>Nhà mạng</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/packages.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'packages.php') !== false ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon text-xs"></i>
                                <p>Gói nạp</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="/admin/spam_logs.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], 'spam_logs.php') !== false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-shield-alt text-danger"></i>
                        <p>Spam Logs</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="/admin/logs.php" class="nav-link <?= strpos($_SERVER['SCRIPT_NAME'], '/logs.php') !== false && strpos($_SERVER['SCRIPT_NAME'], 'spam_logs') === false ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-scroll text-info"></i>
                        <p>System Logs</p>
                    </a>
                </li>
                
                <li class="nav-item mt-4 border-top border-secondary pt-2">
                    <a href="/admin/logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Đăng xuất</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>