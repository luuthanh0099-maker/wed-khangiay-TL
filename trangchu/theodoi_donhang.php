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

// Lấy thông tin khách hàng từ DB
$stmt_user = $pdo->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theo dõi quá trình giao hàng - TL</title>
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
                        <!-- Theo Dõi Tiến Độ -->
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
