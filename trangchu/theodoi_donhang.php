<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin khách hàng từ DB
$stmt_user = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();

// Lấy danh sách đơn hàng
$orders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi quá trình giao hàng - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
    <style>
        .tracking-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .tracking-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        .order-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            padding: 30px;
            border-left: 5px solid #1b8a44;
        }
        .order-card.cancelled {
            border-left-color: #ee4d2d;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .order-id {
            font-weight: bold;
            font-size: 18px;
            color: #1b8a44;
        }
        .order-date {
            color: #666;
            font-size: 14px;
        }
        
        /* Progress Bar Styles */
        .track {
            position: relative;
            background-color: #ddd;
            height: 7px;
            display: flex;
            margin-bottom: 20px;
            margin-top: 40px;
            border-radius: 5px;
        }
        .track .step {
            flex-grow: 1;
            width: 25%;
            margin-top: -18px;
            text-align: center;
            position: relative;
        }
        .track .step::before {
            height: 7px;
            position: absolute;
            content: "";
            width: 100%;
            left: 0;
            top: 18px;
            background-color: #ddd;
            z-index: 0;
        }
        .track .step.active::before {
            background: #1b8a44;
        }
        .track .step:first-child::before {
            border-top-left-radius: 5px;
            border-bottom-left-radius: 5px;
        }
        .track .step:last-child::before {
            border-top-right-radius: 5px;
            border-bottom-right-radius: 5px;
        }
        .track .icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            position: relative;
            border-radius: 100%;
            background: #ddd;
            color: #fff;
            z-index: 1;
        }
        .track .step.active .icon {
            background: #1b8a44;
        }
        .track .text {
            display: block;
            margin-top: 7px;
            color: #999;
            font-size: 14px;
        }
        .track .step.active .text {
            font-weight: 600;
            color: #333;
        }
        
        .cancelled-track {
            text-align: center;
            color: #ee4d2d;
            font-size: 18px;
            font-weight: bold;
            padding: 20px 0;
            background: #fdf2f2;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .cancelled-track i {
            margin-right: 8px;
            font-size: 24px;
        }
        
        .delivery-estimate {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 8px;
            color: #2e7d32;
            font-weight: 500;
            text-align: center;
            margin-top: 30px;
            border: 1px solid #c8e6c9;
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
                            Xin chào, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Khách hàng'); ?> <i class="fas fa-chevron-down"></i>
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

    <main class="tracking-container">
        <h2 class="tracking-title">Quá trình giao hàng</h2>
        
        <?php if (count($orders) > 0): ?>
            <?php foreach($orders as $o): 
                $status = $o['status'];
                $is_cancelled = ($status == 'cancelled');
                
                // Tính ngày dự kiến giao hàng (Thêm 3 ngày từ ngày đặt hàng)
                $order_date = strtotime($o['created_at']);
                $delivery_date = date('d/m/Y', strtotime('+3 days', $order_date));
                
                // Xác định số bước (1: Đặt hàng, 2: Xác nhận, 3: Đang giao, 4: Hoàn thành)
                $step = 0;
                if ($status == 'pending') $step = 1;
                if ($status == 'confirmed') $step = 2;
                if ($status == 'shipping') $step = 3;
                if ($status == 'completed' || $status == 'delivered') $step = 4;
            ?>
                <div class="order-card <?php echo $is_cancelled ? 'cancelled' : ''; ?>">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Đơn hàng #<?php echo $o['id']; ?></span>
                        </div>
                        <div>
                            <span class="order-date"><i class="far fa-clock"></i> Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($is_cancelled): ?>
                        <div class="cancelled-track">
                            <i class="fas fa-times-circle"></i> Đơn hàng này đã bị hủy
                        </div>
                    <?php else: ?>
                        <!-- Progress Tracking -->
                        <div class="track">
                            <div class="step <?php echo ($step >= 1) ? 'active' : ''; ?>">
                                <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                                <span class="text">Đã Đặt Hàng</span>
                            </div>
                            <div class="step <?php echo ($step >= 2) ? 'active' : ''; ?>">
                                <span class="icon"><i class="fas fa-check"></i></span>
                                <span class="text">Đã Xác Nhận</span>
                            </div>
                            <div class="step <?php echo ($step >= 3) ? 'active' : ''; ?>">
                                <span class="icon"><i class="fas fa-truck"></i></span>
                                <span class="text">Đang Giao Hàng</span>
                            </div>
                            <div class="step <?php echo ($step >= 4) ? 'active' : ''; ?>">
                                <span class="icon"><i class="fas fa-box-open"></i></span>
                                <span class="text">Hoàn Thành</span>
                            </div>
                        </div>
                        
                        <?php if ($step < 4): ?>
                        <div class="delivery-estimate">
                            <i class="fas fa-calendar-check"></i> Dự kiến giao hàng vào: <strong><?php echo $delivery_date; ?></strong>
                        </div>
                        <?php else: ?>
                        <div class="delivery-estimate" style="background: #e3f2fd; color: #1565c0; border-color: #bbdefb;">
                            <i class="fas fa-check-circle"></i> Đơn hàng đã được giao thành công!
                        </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; background: #fff; border-radius: 8px;">
                <i class="fa-solid fa-truck" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
                <p style="color: #666;">Bạn chưa có đơn hàng nào đang trong quá trình giao.</p>
                <a href="index.php" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #1b8a44; color: #fff; text-decoration: none; border-radius: 4px;">Mua sắm ngay</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'chantrang.php'; ?>
</body>
</html>
