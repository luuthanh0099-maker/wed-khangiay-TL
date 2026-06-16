<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$pdo = $db->connection_database();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin cá nhân
$stmt = $pdo->prepare("SELECT name, email, phone, role, status, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('Không tìm thấy thông tin người dùng.'); window.location.href='index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=11">
</head>
<body class="bg-light">
    <!-- Phần Đầu Trang -->
    <header class="header">
        <div class="container header-container">
            <a href="index.php" class="logo">
                <img src="../images/logo.jpg" alt="TL" class="logo-img">
                <span class="logo-text">TL</span>
            </a>
            <nav class="navbar">
                <ul class="nav-links">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="danhsach_sanpham.php">Sản phẩm</a></li>
                    <li><a href="danhsach_phukien.php">Phụ kiện</a></li>
                    <li><a href="tintuc.php">Tin tức</a></li>
                    <li><a href="lienhe.php">Liên hệ</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="giohang.php" class="action-icon cart-icon">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span class="cart-badge"><?php echo isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0; ?></span>
                </a>
                <div class="auth-buttons">
                    <div class="user-dropdown-container">
                        <span class="user-greeting">
                            Xin chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-chevron-down"></i>
                        </span>
                        <div class="user-dropdown-menu">
                            <a href="thongtin_canhan.php"><i class="fas fa-user"></i> Thông tin cá nhân</a>
                                <a href="theodoi_donhang.php"><i class="fas fa-truck-fast"></i> Xem quá trình giao hàng</a>
                            <a href="lichsu_muahang.php"><i class="fas fa-box"></i> Lịch sử mua hàng</a>
                            <a href="dangxuat.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="profile-role">
                    <?php 
                        if ($user['role'] == 'admin') echo 'Quản trị viên';
                        elseif ($user['role'] == 'staff') echo 'Nhân viên';
                        else echo 'Khách hàng';
                    ?>
                </div>
            </div>

            <div class="info-group">
                <div class="info-label"><i class="fa-regular fa-envelope"></i> Email đăng nhập</div>
                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>

            <div class="info-group">
                <div class="info-label"><i class="fa-solid fa-phone"></i> Số điện thoại</div>
                <div class="info-value"><?php echo htmlspecialchars($user['phone']); ?></div>
            </div>

            <div class="info-group">
                <div class="info-label"><i class="fa-regular fa-calendar-check"></i> Ngày đăng ký thành viên</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
            </div>

            <div class="info-group" style="border-bottom: none;">
                <div class="info-label"><i class="fa-solid fa-shield-halved"></i> Trạng thái tài khoản</div>
                <div class="info-value" style="color: <?php echo $user['status'] == 'active' ? '#1b8a44' : 'red'; ?>;">
                    <?php echo $user['status'] == 'active' ? 'Đang hoạt động' : 'Đã khóa'; ?>
                </div>
            </div>
            
            <a href="index.php" class="btn-edit">Quay về trang chủ</a>
        </div>
    </main>
    
    <script src="js/main.js?v=1779356416"></script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
