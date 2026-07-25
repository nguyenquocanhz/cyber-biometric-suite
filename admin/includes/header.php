<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../config/function.php');

// Define Constant to block direct access to includes
define('IN_ADMIN', true);

// Check Login
if (empty($_SESSION['username'])) {
    header('Location: /admin/login.php');
    exit;
}

$user = $CMSNT->get_row("SELECT * FROM `users` WHERE `username` = '" . $_SESSION['username'] . "'");
if (!$user || $user['level'] != 'admin') {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel | ShopKCFF</title>
    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- AdminLTE 4 CSS -->
    <link class="adminlte-stylesheet" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">