<?php
session_start();
require_once '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = null;

// Xử lý submit đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['submit_review'])) {
    $content = trim($_POST['content']);
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $user_id = $_SESSION['user_id'];
    
    if (!empty($content)) {
        $stmt = $conn->prepare("INSERT INTO reviews (user_id, item_id, item_type, rating, comment) VALUES (?, ?, 'sanpham', ?, ?)");
        $stmt->bind_param("iiis", $user_id, $id, $rating, $content);
        $stmt->execute();
        header("Location: chitiet_sanpham.php?id=" . $id);
        exit();
    }
}

// Lấy danh sách đánh giá
$reviews = [];
if ($id > 0) {
    $stmt_reviews = $conn->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.item_id = ? AND r.item_type = 'sanpham' ORDER BY r.created_at DESC");
    $stmt_reviews->bind_param("i", $id);
    $stmt_reviews->execute();
    $res_reviews = $stmt_reviews->get_result();
    while ($row = $res_reviews->fetch_assoc()) {
        $reviews[] = $row;
    }
}


if ($id > 0) {
    // Truy vấn sản phẩm theo ID
    $stmt = $conn->prepare("SELECT * FROM sanpham WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        die("Không tìm thấy sản phẩm.");
    }
} else {
    die("ID sản phẩm không hợp lệ.");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['ten_sanpham']); ?> - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=4">
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
                    <li><a href="danhsach_sanpham.php" class="active">Sản phẩm</a></li>
                    <li><a href="danhsach_phukien.php">Phụ kiện</a></li>
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

    <main class="container">
        <!-- Chi tiết sản phẩm -->
        <div class="product-detail-wrapper">
            <!-- Hình ảnh -->
            <div class="product-detail-image">
                <img src="../<?php echo htmlspecialchars($product['hinhanh']); ?>" alt="<?php echo htmlspecialchars($product['ten_sanpham']); ?>" onerror="this.src='../images/logo.jpg'">
            </div>
            
            <!-- Thông tin chi tiết -->
            <div class="product-detail-info">
                <h1 class="detail-name"><?php echo htmlspecialchars($product['ten_sanpham']); ?></h1>
                <p class="detail-desc"><?php echo nl2br(htmlspecialchars($product['mo_ta'])); ?></p>
                <div class="detail-price"><?php echo number_format($product['gia'], 0, ',', '.'); ?> đ</div>
                <div class="detail-stock">Kho: <?php echo $product['so_luong']; ?> sản phẩm</div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="btn-add-cart-large"><i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ</button>
                <?php else: ?>
                    <button class="btn-add-cart-large" onclick="alert('Mời quý khách đăng nhập trước khi mua hàng')"><i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Phần bình luận -->
        <div class="product-comments-section">
            <h2>Bình luận và đánh giá</h2>
            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Form comment (Nếu đã đăng nhập) -->
                <form action="" method="POST" class="comment-form">
                    <div class="rating-input">
                        <label>Đánh giá của bạn:</label>
                        <div class="stars">
                            <input type="radio" name="rating" id="star5" value="5" checked><label for="star5" class="fas fa-star"></label>
                            <input type="radio" name="rating" id="star4" value="4"><label for="star4" class="fas fa-star"></label>
                            <input type="radio" name="rating" id="star3" value="3"><label for="star3" class="fas fa-star"></label>
                            <input type="radio" name="rating" id="star2" value="2"><label for="star2" class="fas fa-star"></label>
                            <input type="radio" name="rating" id="star1" value="1"><label for="star1" class="fas fa-star"></label>
                        </div>
                    </div>
                    <textarea name="content" placeholder="Viết bình luận của bạn..." required></textarea>
                    <button type="submit" name="submit_review" class="btn-submit-comment">Gửi đánh giá</button>
                </form>
            <?php else: ?>
                <!-- Cảnh báo (Nếu chưa đăng nhập) -->
                <div class="alert-login">
                    Vui lòng <a href="dangnhap.php">đăng nhập</a> để bình luận và đánh giá.
                </div>
            <?php endif; ?>

            <!-- Danh sách đánh giá -->
            <div class="reviews-list">
                <?php if (empty($reviews)): ?>
                    <p class="no-reviews">Chưa có đánh giá nào cho sản phẩm này. Hãy là người đầu tiên đánh giá!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <span class="review-author"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                <div class="review-stars">
                                    <?php 
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $review['rating']) {
                                            echo '<i class="fas fa-star filled"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <span class="review-date"><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="review-content">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="js/main.js?v=1779356416"></script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
