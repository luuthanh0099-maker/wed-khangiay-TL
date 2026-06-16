<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$pdo = $db->connection_database();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$exactMatches = [];
$relatedMatches = [];

if ($q !== '') {
    // 1. TÌM KIẾM CHÍNH XÁC (EXACT MATCHES)
    $searchPattern = '%' . $q . '%';
    
    // Tìm trong sanpham
    $stmt1 = $pdo->prepare("SELECT id, ten_sanpham as name, hinhanh as img, gia as price, so_luong, 'sanpham' as type FROM sanpham WHERE ten_sanpham LIKE ?");
    $stmt1->execute([$searchPattern]);
    $exactSanpham = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    // Tìm trong phukien
    $stmt2 = $pdo->prepare("SELECT id, ten_phukien as name, hinhanh as img, gia as price, so_luong, 'phukien' as type FROM phukien WHERE ten_phukien LIKE ?");
    $stmt2->execute([$searchPattern]);
    $exactPhukien = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    $exactMatches = array_merge($exactSanpham, $exactPhukien);
    
    // Lấy ID để loại trừ khỏi phần liên quan
    $excludeSanphamIds = array_column($exactSanpham, 'id');
    $excludePhukienIds = array_column($exactPhukien, 'id');
    
    // 2. TÌM KIẾM LIÊN QUAN (RELATED MATCHES)
    // Tách từ khóa thành các từ đơn (dựa vào khoảng trắng)
    $words = preg_split('/\s+/', $q);
    $words = array_filter($words, function($word) {
        return mb_strlen($word, 'UTF-8') >= 2; // Chỉ lấy các từ có ít nhất 2 ký tự (bỏ qua các từ nối như "và", "là" nếu quá ngắn)
    });
    
    if (count($words) > 0) {
        // Tạo câu truy vấn động cho sản phẩm liên quan
        $conditions = [];
        $params = [];
        foreach ($words as $word) {
            $conditions[] = "ten_sanpham LIKE ?";
            $params[] = '%' . $word . '%';
        }
        
        $sqlRelatedSanpham = "SELECT id, ten_sanpham as name, hinhanh as img, gia as price, so_luong, 'sanpham' as type FROM sanpham WHERE (" . implode(" OR ", $conditions) . ")";
        if (count($excludeSanphamIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($excludeSanphamIds), '?'));
            $sqlRelatedSanpham .= " AND id NOT IN ($placeholders)";
            $params = array_merge($params, $excludeSanphamIds);
        }
        $sqlRelatedSanpham .= " LIMIT 4";
        
        $stmtRelated1 = $pdo->prepare($sqlRelatedSanpham);
        $stmtRelated1->execute($params);
        $relatedSanpham = $stmtRelated1->fetchAll(PDO::FETCH_ASSOC);
        
        // Tạo câu truy vấn động cho phụ kiện liên quan
        $conditionsPk = [];
        $paramsPk = [];
        foreach ($words as $word) {
            $conditionsPk[] = "ten_phukien LIKE ?";
            $paramsPk[] = '%' . $word . '%';
        }
        
        $sqlRelatedPhukien = "SELECT id, ten_phukien as name, hinhanh as img, gia as price, so_luong, 'phukien' as type FROM phukien WHERE (" . implode(" OR ", $conditionsPk) . ")";
        if (count($excludePhukienIds) > 0) {
            $placeholdersPk = implode(',', array_fill(0, count($excludePhukienIds), '?'));
            $sqlRelatedPhukien .= " AND id NOT IN ($placeholdersPk)";
            $paramsPk = array_merge($paramsPk, $excludePhukienIds);
        }
        $sqlRelatedPhukien .= " LIMIT 4";
        
        $stmtRelated2 = $pdo->prepare($sqlRelatedPhukien);
        $stmtRelated2->execute($paramsPk);
        $relatedPhukien = $stmtRelated2->fetchAll(PDO::FETCH_ASSOC);
        
        $relatedMatches = array_merge($relatedSanpham, $relatedPhukien);
    } else {
        // Nếu không có từ khóa nào đủ dài, lấy ngẫu nhiên vài sản phẩm làm liên quan
        $sqlRelatedSanpham = "SELECT id, ten_sanpham as name, hinhanh as img, gia as price, so_luong, 'sanpham' as type FROM sanpham";
        if (count($excludeSanphamIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($excludeSanphamIds), '?'));
            $sqlRelatedSanpham .= " WHERE id NOT IN ($placeholders)";
            $stmtRelated1 = $pdo->prepare($sqlRelatedSanpham . " ORDER BY RAND() LIMIT 4");
            $stmtRelated1->execute($excludeSanphamIds);
        } else {
            $stmtRelated1 = $pdo->prepare($sqlRelatedSanpham . " ORDER BY RAND() LIMIT 4");
            $stmtRelated1->execute();
        }
        $relatedSanpham = $stmtRelated1->fetchAll(PDO::FETCH_ASSOC);
        $relatedMatches = $relatedSanpham;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả tìm kiếm cho: <?php echo htmlspecialchars($q); ?> - TL</title>
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
                <div class="action-icon search-icon" id="search-icon-container">
                    <!-- Khung Tìm Kiếm Dropdown -->
                    <form action="sanphamcantim.php" method="GET" class="search-form" style="margin: 0; width: 100%;">
                        <div class="search-dropdown" id="search-dropdown">
                            <input type="text" name="q" id="search-input" placeholder="bạn tìm gì ?" value="<?php echo htmlspecialchars($q); ?>" autocomplete="off" required>
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

    <main>
        <!-- KẾT QUẢ TÌM KIẾM CHÍNH XÁC -->
        <section class="products-section container" style="margin-top: 40px;">
            <h2 class="section-title">Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($q); ?>"</h2>
            <div class="product-grid">
                <?php foreach($exactMatches as $item): ?>
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <img src="../<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-img" onerror="this.src='../images/logo.jpg'">
                        <span class="badge">Còn <?php echo $item['so_luong']; ?></span>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="product-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</div>
                        <div class="product-actions">
                            <?php $link = $item['type'] == 'sanpham' ? "chitiet_sanpham.php?id=".$item['id'] : "chitiet_phukien.php?id=".$item['id']; ?>
                            <a href="<?php echo $link; ?>" class="btn-action btn-view"><i class="fa-regular fa-eye"></i> Xem</a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn-action btn-cart" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo $item['type']; ?>')"><i class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                            <?php else: ?>
                                <button class="btn-action btn-cart" onclick="alert('Mời quý khách đăng nhập trước khi mua hàng')"><i class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($exactMatches)): ?>
                    <p style="text-align:center; color:#777; grid-column: 1 / -1; padding: 40px; background: #fff; border-radius: 8px;">Không tìm thấy sản phẩm nào khớp hoàn toàn với từ khóa.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- KẾT QUẢ TÌM KIẾM LIÊN QUAN -->
        <?php if(count($relatedMatches) > 0): ?>
        <section class="products-section container" style="margin-top: 20px;">
            <h2 class="section-title">Sản Phẩm Liên Quan</h2>
            <div class="product-grid">
                <?php foreach($relatedMatches as $item): ?>
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <img src="../<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-img" onerror="this.src='../images/logo.jpg'">
                        <span class="badge">Còn <?php echo $item['so_luong']; ?></span>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="product-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</div>
                        <div class="product-actions">
                            <?php $link = $item['type'] == 'sanpham' ? "chitiet_sanpham.php?id=".$item['id'] : "chitiet_phukien.php?id=".$item['id']; ?>
                            <a href="<?php echo $link; ?>" class="btn-action btn-view"><i class="fa-regular fa-eye"></i> Xem</a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn-action btn-cart" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo $item['type']; ?>')"><i class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                            <?php else: ?>
                                <button class="btn-action btn-cart" onclick="alert('Mời quý khách đăng nhập trước khi mua hàng')"><i class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <script src="js/main.js?v=1779356416"></script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
