<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$pdo = $db->connection_database();

// Kiểm tra quyền Admin (sử dụng session admin_id)
if (!isset($_SESSION['admin_id'])) {
    header("Location: dangnhap_admin.php");
    exit();
}

$admin_email = $_SESSION['admin_email'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TL Tissue</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin_style.css?v=3">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">Hệ Thống Quản Trị</div>
            <div class="topbar-user">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_email); ?></span>
                <a href="dangxuat_admin.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất Admin</a>
            </div>
        </div>
        
        <div class="content-wrapper">
