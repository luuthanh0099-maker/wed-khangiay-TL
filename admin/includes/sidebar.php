<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header" style="display: flex; align-items: center; justify-content: center; gap: 10px;">
        <img src="../images/logo.jpg" alt="Logo" style="width: 40px; height: 40px; border-radius: 50%;">
        <h2 style="margin: 0;">TL ADMIN</h2>
    </div>
    <div class="sidebar-menu">
        <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Tổng quan
        </a>
        <a href="quanly_donhang.php" class="<?php echo $current_page == 'quanly_donhang.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i> Đơn hàng
        </a>
        <a href="quanly_sanpham.php" class="<?php echo $current_page == 'quanly_sanpham.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Sản phẩm
        </a>
        <a href="quanly_khachhang.php" class="<?php echo $current_page == 'quanly_khachhang.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Khách hàng
        </a>
        <a href="thongke_doanhthu.php" class="<?php echo $current_page == 'thongke_doanhthu.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Thống kê
        </a>
        <a href="quanly_banner.php" class="<?php echo $current_page == 'quanly_banner.php' ? 'active' : ''; ?>">
            <i class="fas fa-image"></i> Banners
        </a>
        <a href="quanly_tintuc.php" class="<?php echo $current_page == 'quanly_tintuc.php' ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i> Tin tức
        </a>
        <a href="quanly_danhgia.php" class="<?php echo $current_page == 'quanly_danhgia.php' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Đánh giá
        </a>
        <a href="quanly_lienhe.php" class="<?php echo $current_page == 'quanly_lienhe.php' ? 'active' : ''; ?>">
            <i class="fas fa-address-book"></i> Quản lý liên hệ
        </a>
        <a href="../trangchu/index.php" target="_blank" style="margin-top: 30px; border-top: 1px solid #374151;">
            <i class="fas fa-globe"></i> Xem trang chủ
        </a>
    </div>
</div>
