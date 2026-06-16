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

// Xử lý Hủy đơn hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cancel_order') {
    $order_id = intval($_POST['order_id']);
    
    // Kiểm tra xem đơn hàng có thuộc về user hiện tại và có trạng thái pending không
    $check_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $check_stmt->execute([$order_id, $user_id]);
    $check_res = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($check_res) {
        // Cập nhật trạng thái
        $pdo->exec("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
        
        // Hoàn trả số lượng
        $items_stmt = $pdo->prepare("SELECT product_id, quantity, product_type FROM order_items WHERE order_id = ?");
        $items_stmt->execute([$order_id]);
        $items_res = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items_res as $item) {
            $pid = intval($item['product_id']);
            $qty = intval($item['quantity']);
            if ($item['product_type'] == 'sanpham') {
                $pdo->exec("UPDATE sanpham SET so_luong = so_luong + $qty WHERE id = $pid");
            } else {
                $pdo->exec("UPDATE phukien SET so_luong = so_luong + $qty WHERE id = $pid");
            }
        }
        $msg = "Hủy đơn hàng thành công!";
    } else {
        $msg = "Không thể hủy đơn hàng này.";
    }
}

// Xử lý API gọi dữ liệu chi tiết đơn hàng (Dùng chung file này hoặc fetch data bằng PHP embed)
// Để đơn giản, ta sẽ query tất cả các items và load sẵn vào HTML data attributes, hoặc fetch qua Ajax.
// Trong trường hợp này, vì lượng đơn thường không quá khổng lồ, query data cho Modal qua PHP array là nhanh nhất.

// Lấy danh sách đơn hàng
$orders = [];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $row) {
    $order_id = $row['id'];
    
    // Lấy order items
    $items = [];
    $item_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $item_stmt->execute([$order_id]);
    $item_res = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($item_res as $i) {
        // Lấy tên sản phẩm
        $pid = intval($i['product_id']);
        $name = "Sản phẩm không tồn tại";
        if ($i['product_type'] == 'sanpham') {
            $name_res = $db->readitem("SELECT ten_sanpham FROM sanpham WHERE id = $pid");
            if (!empty($name_res)) {
                $name = $name_res[0]['ten_sanpham'];
            }
        } else {
            $name_res = $db->readitem("SELECT ten_phukien FROM phukien WHERE id = $pid");
            if (!empty($name_res)) {
                $name = $name_res[0]['ten_phukien'];
            }
        }
        $i['product_name'] = $name;
        $items[] = $i;
    }
    
    $row['items'] = $items;
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử mua hàng - TL</title>
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

    <main class="history-container">
        <h2 class="history-title">Lịch sử đơn hàng</h2>
        
        <?php if(isset($msg)): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;"><?php echo $msg; ?></div>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
            <?php foreach($orders as $o): ?>
                <div class="order-card <?php echo ($o['status'] == 'cancelled') ? 'cancelled' : ''; ?>">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Đơn hàng #<?php echo $o['id']; ?></span>
                            <span class="order-date" style="margin-left: 10px;"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></span>
                        </div>
                        <div>
                            <span class="order-status status-<?php echo $o['status']; ?>">
                                <?php 
                                    if($o['status']=='pending') echo 'Chờ xử lý';
                                    elseif($o['status']=='confirmed') echo 'Đã xác nhận';
                                    elseif($o['status']=='shipping') echo 'Đang giao';
                                    elseif($o['status']=='delivered') echo 'Đã giao';
                                    elseif($o['status']=='cancelled') echo 'Đã hủy';
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="order-total">
                            Tổng thanh toán: <strong><?php echo number_format($o['total'], 0, ',', '.'); ?> đ</strong>
                        </div>
                        <div class="order-actions">
                            <button class="btn btn-detail" onclick="openDetailModal(<?php echo htmlspecialchars(json_encode($o)); ?>)"><i class="fas fa-eye"></i> Chi tiết</button>
                            <?php if($o['status'] == 'pending'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này không?');">
                                <input type="hidden" name="action" value="cancel_order">
                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                <button type="submit" class="btn btn-cancel"><i class="fas fa-times"></i> Hủy đơn hàng</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; background: #fff; border-radius: 8px;">
                <i class="fa-solid fa-box-open" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
                <p style="color: #666;">Bạn chưa có đơn hàng nào.</p>
                <a href="index.php" style="display: inline-block; margin-top: 10px; padding: 10px 20px; background: #1b8a44; color: #fff; text-decoration: none; border-radius: 4px;">Mua sắm ngay</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Chi Tiết -->
    <div class="modal-overlay" id="detailModalOverlay" onclick="closeDetailModal()"></div>
    <div class="modal-content" id="detailModal">
        <div class="modal-header">
            <h3>Chi tiết đơn hàng <span id="modalOrderId" style="color: #1b8a44;"></span></h3>
            <i class="fas fa-times modal-close" onclick="closeDetailModal()"></i>
        </div>
        <div class="modal-body">
            <div class="detail-section">
                <h4>Thông tin khách hàng</h4>
                <div class="detail-row"><span>Họ và tên:</span> <strong id="modalName"><?php echo htmlspecialchars($user_info['name']); ?></strong></div>
                <div class="detail-row"><span>Email:</span> <strong id="modalEmail"><?php echo htmlspecialchars($user_info['email']); ?></strong></div>
                <div class="detail-row"><span>Số điện thoại:</span> <strong id="modalPhone"></strong></div>
                <div class="detail-row"><span>Địa chỉ nhận hàng:</span> <strong id="modalAddress" style="text-align: right; max-width: 70%;"></strong></div>
                <div class="detail-row"><span>Ngày đặt:</span> <strong id="modalDate"></strong></div>
            </div>

            <div class="detail-section">
                <h4>Sản phẩm đã mua</h4>
                <ul class="detail-product-list" id="modalProductList">
                    <!-- Sản phẩm sẽ được hiển thị ở đây qua JS -->
                </ul>
                <div style="text-align: right; margin-top: 15px; font-size: 16px;">
                    Tổng thanh toán: <strong id="modalTotal" style="color: #ee4d2d; font-size: 22px;"></strong>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn" style="background: #e0e0e0; color: #333;" onclick="closeDetailModal()">Đóng</button>
            </div>
        </div>
    </div>

    <script src="js/main.js?v=1779356416"></script>
    <script src="js/lichsu_muahang.js"></script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
