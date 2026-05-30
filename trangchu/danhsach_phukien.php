<?php
session_start();
require_once '../config/db.php';

// Lấy danh sách toàn bộ phụ kiện
$phukienQuery = $conn->query("SELECT * FROM phukien ORDER BY id DESC");
$phukienList = [];
if ($phukienQuery && $phukienQuery->num_rows > 0) {
    while($row = $phukienQuery->fetch_assoc()) {
        $phukienList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phụ Kiện - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
</head>
<body>

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
                    <li><a href="danhsach_phukien.php" class="active">Phụ kiện</a></li>
                    <li><a href="tintuc.php">Tin tức</a></li>
                    <li><a href="contact.php">Liên hệ</a></li>
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

    <main>
        <section class="products-section container">
            <h2 class="section-title">Tất Cả Phụ Kiện</h2>
            <div class="product-grid">
                <?php foreach($phukienList as $pk): ?>
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <img src="../<?php echo htmlspecialchars($pk['hinhanh']); ?>" alt="<?php echo htmlspecialchars($pk['ten_phukien']); ?>" class="product-img" onerror="this.src='../images/logo.jpg'">
                        <span class="badge">Còn <?php echo $pk['so_luong']; ?></span>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($pk['ten_phukien']); ?></h3>
                        <div class="product-price"><?php echo number_format($pk['gia'], 0, ',', '.'); ?>đ</div>
                        <div class="product-actions">
                            <a href="chitiet_phukien.php?id=<?php echo $pk['id']; ?>" class="btn-action btn-view"><i class="fa-regular fa-eye"></i> Xem</a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn-action btn-cart" onclick="addToCart(<?php echo $pk['id']; ?>, 'phukien')"><i class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                            <?php else: ?>
                                <button class="btn-action btn-cart" onclick="alert('Mời quý khách đăng nhập trước khi mua hàng')"><i class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($phukienList)): ?>
                    <p style="text-align:center; color:#777; grid-column: 1 / -1;">Chưa có phụ kiện nào.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="js/main.js?v=1779356416"></script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
