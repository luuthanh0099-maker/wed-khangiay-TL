<?php
session_start();
require_once '../config/db.php';

// Fetch tintuc grouped by type
$tintucQuery = $conn->query("SELECT * FROM tintuc ORDER BY id DESC");
$khuyenmai = [];
$voucher = [];
$thongbao = [];

if ($tintucQuery && $tintucQuery->num_rows > 0) {
    while($row = $tintucQuery->fetch_assoc()) {
        if ($row['type'] == 'khuyenmai') {
            $khuyenmai[] = $row;
        } elseif ($row['type'] == 'voucher') {
            $voucher[] = $row;
        } elseif ($row['type'] == 'thongbao') {
            $thongbao[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin tức & Khuyến mãi - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
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
                    <li><a href="tintuc.php" class="active">Tin tức</a></li>
                    <li><a href="lienhe.php">Liên hệ</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                                <div class="action-icon search-icon" id="search-icon-container">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <!-- Khung Tìm Kiếm Dropdown -->
                    <div class="search-dropdown" id="search-dropdown">
                        <input type="text" id="search-input" placeholder="Tìm kiếm sản phẩm">
                        <div class="search-results" id="search-results">
                            <!-- Kết quả AJAX sẽ hiện ở đây -->
                        </div>
                    </div>
                </div>
                <a href="giohang.php" class="action-icon cart-icon">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span class="cart-badge"><?php echo isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0; ?></span>
                </a>
                                <div class="auth-buttons">
                    <?php if (isset($_SESSION['user_name'])): ?>
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
                    <?php else: ?>
                        <a href="dangnhap.php" class="btn btn-outline">Đăng nhập</a>
                        <a href="dangky.php" class="btn btn-primary">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="news-container container">
        
        <!-- 1. Chương trình khuyến mãi -->
        <section class="news-section">
            <h2 class="news-title">Chương trình khuyến mãi</h2>
            <?php foreach($khuyenmai as $km): ?>
            <div class="news-card">
                <h3><?php echo htmlspecialchars($km['title']); ?></h3>
                <p><?php echo htmlspecialchars($km['content']); ?></p>
            </div>
            <?php endforeach; ?>
            <?php if(empty($khuyenmai)): ?>
                <p>Chưa có chương trình khuyến mãi nào.</p>
            <?php endif; ?>
        </section>

        <!-- 2. Voucher khuyến mãi -->
        <section class="news-section">
            <h2 class="news-title">Voucher khuyến mãi</h2>
            <div class="voucher-grid">
                <?php foreach($voucher as $v): ?>
                <div class="voucher-card">
                    <div class="voucher-header">
                        <h3><?php echo htmlspecialchars($v['title']); ?></h3>
                        <span class="voucher-qty">Số lượng: <?php echo $v['quantity']; ?></span>
                    </div>
                    <p>Giảm: <?php echo $v['discount_percent']; ?>%</p>
                    <p>Hết hạn: <?php echo $v['expiry_date']; ?></p>
                    <div class="voucher-actions">
                        <button class="btn-copy">Sao chép mã</button>
                        <button class="btn-use">Sử dụng</button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($voucher)): ?>
                    <p style="grid-column: 1 / -1;">Chưa có voucher nào.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- 3. Thông báo -->
        <section class="news-section">
            <h2 class="news-title">Thông báo</h2>
            <div class="notice-card">
                <ul class="notice-list">
                    <?php foreach($thongbao as $tb): ?>
                        <li><?php echo htmlspecialchars($tb['content']); ?></li>
                    <?php endforeach; ?>
                    <?php if(empty($thongbao)): ?>
                        <li>Không có thông báo nào.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>

    </main>

    <script src="js/main.js?v=1779356416"></script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
