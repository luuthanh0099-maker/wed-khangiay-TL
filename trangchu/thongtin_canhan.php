<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin cá nhân
$stmt = $conn->prepare("SELECT name, email, phone, role, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo "<script>alert('Không tìm thấy thông tin người dùng.'); window.location.href='index.php';</script>";
    exit();
}

$user = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
    <style>
        .profile-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: #e2f0e6;
            color: #1b8a44;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px auto;
        }
        .profile-name {
            font-size: 22px;
            color: #333;
            font-weight: 600;
        }
        .profile-role {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 13px;
            margin-top: 5px;
        }
        .info-group {
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        .info-label {
            font-size: 14px;
            color: #777;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        .btn-edit {
            display: block;
            width: 100%;
            text-align: center;
            background: #f0f0f0;
            color: #333;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 30px;
            transition: 0.2s;
        }
        .btn-edit:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Header Section -->
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
