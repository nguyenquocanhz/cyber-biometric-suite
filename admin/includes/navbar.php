<?php
if (!defined('IN_ADMIN'))
    die('Direct access denied');
?>
<!-- Navbar -->
<nav class="app-header navbar navbar-expand bg-body shadow-sm">
    <div class="container-fluid">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-inline-block">
                <span class="nav-link font-bold text-dark fs-5">
                    <?php
                    $page = basename($_SERVER['PHP_SELF'], ".php");
                    if ($page == 'index') echo 'Dashboard Thống Kê';
                    else if ($page == 'cards') echo 'Quản lý Nạp thẻ';
                    else if ($page == 'users') echo 'Quản lý Thành viên';
                    else if ($page == 'settings') echo 'Cài đặt Hệ thống';
                    else if ($page == 'spam_logs') echo 'Nhật ký Spam';
                    else if ($page == 'logs') echo 'Nhật Ký Hệ Thống';
                    else echo ucfirst($page);
                    ?>
                </span>
            </li>
        </ul>
        
        <!-- Right navbar links -->
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a href="/" target="_blank" class="nav-link text-primary font-semibold flex items-center gap-1">
                    <i class="fas fa-external-link-alt"></i> Xem Website
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Main content container wrapper -->
<main class="app-main pt-3">
    <div class="container-fluid">