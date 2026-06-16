<?php
if (!isset($db)) {
    require_once '../model/xl_data.php';
    $db = new xl_data();
}
$contact_info = $db->readitem("SELECT * FROM cauhinh_lienhe WHERE id = 1");
$contact_info = !empty($contact_info) ? $contact_info[0] : null;
?>
<footer class="site-footer">
    <div class="container footer-top">
        <!-- Cột 1: Logo & Text -->
        <div class="footer-col brand-col">
            <a href="index.php" class="footer-logo">
                <img src="../images/logo.jpg" alt="TL" onerror="this.src='../images/logo.png'">
                <span class="footer-logo-text">TL Tissue</span>
            </a>
            <p class="slogan">“Khăn giấy chất lượng cho mọi gia đình”</p>
        </div>

        <!-- Cột 2: Menu liên kết nhanh -->
        <div class="footer-col links-col">
            <h3 class="footer-heading">Liên Kết Nhanh</h3>
            <ul class="quick-links">
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="danhsach_sanpham.php">Sản phẩm</a></li>
                <li><a href="danhsach_phukien.php">Phụ kiện</a></li>
            </ul>
        </div>

        <!-- Cột 3: Resources / Tin tức -->
        <div class="footer-col links-col">
            <h3 class="footer-heading">Thông Tin</h3>
            <ul class="quick-links">
                <li><a href="tintuc.php">Tin tức & Khuyến mãi</a></li>
                <li><a href="lienhe.php">Liên hệ với chúng tôi</a></li>
                <li><a href="#">Giờ mở cửa: 7h - 22h</a></li>
            </ul>
        </div>

        <!-- Cột 4: Chính sách -->
        <div class="footer-col links-col">
            <h3 class="footer-heading">Chính Sách & Hỗ Trợ</h3>
            <ul class="quick-links">
                <li><a href="#">Hỗ trợ đổi trả trong 7 ngày</a></li>
                <li><a href="#">Giao hàng nhanh chóng</a></li>
                <li><a href="#">Thanh toán an toàn</a></li>
            </ul>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <div class="footer-bottom-left">
                <p>&copy; 2026 TL Tissue.</p>
            </div>
            <div class="footer-bottom-right">
                <div class="contact-box">
                    <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="contact-text">
                        <span>Gọi cho chúng tôi:</span>
                        <strong><?php echo htmlspecialchars($contact_info['sodienthoai'] ?? '0975 720 687'); ?></strong>
                    </div>
                </div>
                <div class="contact-box">
                    <a href="<?php echo htmlspecialchars($contact_info['map_link'] ?? '#'); ?>" target="_blank" class="contact-icon" style="color: inherit;"><i class="fas fa-map-marker-alt"></i></a>
                    <div class="contact-text">
                        <span>Địa chỉ cửa hàng:</span>
                        <strong><?php echo htmlspecialchars($contact_info['diachi'] ?? '504/58 Phường An Lạc A, Bình Tân'); ?></strong>
                    </div>
                </div>
                <div class="contact-box">
                    <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                    <div class="contact-text">
                        <span>Email liên hệ:</span>
                        <strong><?php echo htmlspecialchars($contact_info['email'] ?? 'luuthanh0099@gmail.com'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Contact Icons -->
<div class="floating-contact-container">
    <a href="lienhe.php?contact=dt" class="floating-icon icon-phone" title="Gọi điện thoại">
        <img src="../images/dt.jpg" alt="Phone">
    </a>
    <a href="lienhe.php?contact=zalo" class="floating-icon icon-zalo" title="Chat Zalo">
        <img src="../images/zalo.jpg" alt="Zalo">
    </a>
</div>
