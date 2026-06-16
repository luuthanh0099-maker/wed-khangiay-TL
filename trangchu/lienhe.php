<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$contact_info = $db->readitem("SELECT * FROM cauhinh_lienhe WHERE id = 1");
$contact_info = !empty($contact_info) ? $contact_info[0] : null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - TL</title>
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
                    <li><a href="lienhe.php" class="active">Liên hệ</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <div class="action-icon search-icon" id="search-icon-container">
                    <!-- Khung Tìm Kiếm Dropdown -->
                    <form action="sanphamcantim.php" method="GET" class="search-form" style="margin: 0; width: 100%;">
                        <div class="search-dropdown" id="search-dropdown">
                            <input type="text" name="q" id="search-input" placeholder="bạn tìm gì ?" autocomplete="off" required>
                            <button type="submit" class="search-submit-btn">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                            <div class="search-results" id="search-results">
                                <!-- Kết quả AJAX sẽ hiện ở đây -->
                            </div>
                        </div>
                    </form>
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

    <main class="container">
        <!-- Banner Bản đồ -->
        <div class="map-banner" style="margin: 40px auto 0; text-align: center; max-width: 1000px;">
            <a href="<?php echo htmlspecialchars($contact_info['map_link'] ?? '#'); ?>" target="_blank" title="Xem cửa hàng trên Google Maps">
                <img src="../images/map.jpg?v=<?php echo filemtime('../images/map.jpg'); ?>" alt="Bản đồ cửa hàng" style="width: 100%; height: auto; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); border: 2px solid #fff; transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
            </a>
        </div>

        <div class="contact-page-container" style="margin-top: 40px;">
            <!-- Chữ Liên hệ để Click -->
            <h1 class="contact-trigger" id="contact-trigger">
                Liên hệ <i class="fas fa-chevron-down" id="trigger-icon"></i>
            </h1>
            
            <!-- Menu nhỏ xổ ra -->
            <div class="contact-menu" id="contact-menu">
                <div class="contact-icons">
                    <!-- Nút 1: Điện thoại -->
                    <img src="../images/dt.jpg" alt="Điện thoại" id="btn-dt" title="Gọi điện thoại" data-phone="<?php echo htmlspecialchars(str_replace(' ', '', $contact_info['sodienthoai'] ?? '0975720687')); ?>" data-phone-display="<?php echo htmlspecialchars($contact_info['sodienthoai'] ?? '0975 720 687'); ?>">
                    
                    <!-- Nút 2: Zalo -->
                    <img src="../images/zalo.jpg" alt="Zalo" id="btn-zalo" title="Quét mã Zalo">
                    
                    <!-- Nút 3: Gmail -->
                    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?php echo urlencode($contact_info['email'] ?? 'luuthanh0099@gmail.com'); ?>" target="_blank" title="Gửi Email qua Gmail">
                        <img src="../images/gmail.png" alt="Gmail">
                    </a>
                </div> 
                <!-- Khung hiển thị -->
                <div class="display-frame" id="display-frame">
                    Vui lòng chọn một phương thức liên hệ ở trên.
                </div>
            </div>
        </div>
    </main>

    <script src="js/main.js?v=1779356416"></script>
    <script src="js/lienhe.js?v=2"></script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
