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

// Xử lý Hủy đơn hàng
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'cancel_order') {
    $order_id = intval($_POST['order_id']);
    
    // Kiểm tra xem đơn hàng có thuộc về user hiện tại và có trạng thái pending không
    $check_stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    
    if ($check_res->num_rows > 0) {
        // Cập nhật trạng thái
        $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $order_id");
        
        // Hoàn trả số lượng
        $items_stmt = $conn->prepare("SELECT product_id, quantity, product_type FROM order_items WHERE order_id = ?");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_res = $items_stmt->get_result();
        
        while ($item = $items_res->fetch_assoc()) {
            $pid = intval($item['product_id']);
            $qty = intval($item['quantity']);
            if ($item['product_type'] == 'sanpham') {
                $conn->query("UPDATE sanpham SET so_luong = so_luong + $qty WHERE id = $pid");
            } else {
                $conn->query("UPDATE phukien SET so_luong = so_luong + $qty WHERE id = $pid");
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
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $order_id = $row['id'];
    
    // Lấy order items
    $items = [];
    $item_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $item_stmt->bind_param("i", $order_id);
    $item_stmt->execute();
    $item_res = $item_stmt->get_result();
    while ($i = $item_res->fetch_assoc()) {
        // Lấy tên sản phẩm
        $pid = intval($i['product_id']);
        $name = "Sản phẩm không tồn tại";
        if ($i['product_type'] == 'sanpham') {
            $name_res = $conn->query("SELECT ten_sanpham FROM sanpham WHERE id = $pid");
            if ($name_res && $name_res->num_rows > 0) {
                $name = $name_res->fetch_assoc()['ten_sanpham'];
            }
        } else {
            $name_res = $conn->query("SELECT ten_phukien FROM phukien WHERE id = $pid");
            if ($name_res && $name_res->num_rows > 0) {
                $name = $name_res->fetch_assoc()['ten_phukien'];
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
    <link rel="stylesheet" href="css/style.css?v=3">
    <style>
        .history-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .history-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        .order-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            padding: 20px;
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
            margin-bottom: 15px;
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
        .order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-confirmed { background: #d4edda; color: #155724; }
        
        .order-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-total {
            font-size: 18px;
        }
        .order-total strong {
            color: #ee4d2d;
            font-size: 20px;
        }
        .order-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-detail {
            background: #e0f2f1;
            color: #00796b;
        }
        .btn-detail:hover { background: #b2dfdb; }
        
        .btn-cancel {
            background: #ffebee;
            color: #c62828;
        }
        .btn-cancel:hover { background: #ffcdd2; }

        /* Modal Styles */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000;
        }
        .modal-content {
            display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: #fff; width: 600px; max-width: 90%; border-radius: 8px; z-index: 1001;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            max-height: 90vh; overflow-y: auto;
        }
        .modal-header {
            padding: 15px 20px; border-bottom: 1px solid #eee;
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 18px; color: #333; }
        .modal-close { cursor: pointer; font-size: 20px; color: #888; }
        .modal-body { padding: 20px; }
        .detail-section { margin-bottom: 20px; }
        .detail-section h4 { margin: 0 0 10px 0; color: #1b8a44; border-bottom: 1px dashed #ccc; padding-bottom: 5px; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .detail-product-list { list-style: none; padding: 0; margin: 0; }
        .detail-product-list li {
            display: flex; justify-content: space-between;
            padding: 10px; border: 1px solid #eee; border-radius: 4px; margin-bottom: 8px;
            background: #fafafa;
        }
        .prod-name { font-weight: 500; color: #333; }
        .prod-qty { color: #666; font-size: 13px; }
        .prod-price { color: #ee4d2d; font-weight: 500; }
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
                    <!-- Products will be injected here via JS -->
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
    <script>
        function openDetailModal(orderData) {
            document.getElementById('modalOrderId').innerText = '#' + orderData.id;
            document.getElementById('modalPhone').innerText = orderData.phone;
            document.getElementById('modalAddress').innerText = orderData.shipping_address;
            
            // Format Date
            let dateObj = new Date(orderData.created_at);
            let formattedDate = ('0' + dateObj.getDate()).slice(-2) + '/' + ('0' + (dateObj.getMonth()+1)).slice(-2) + '/' + dateObj.getFullYear() + ' ' + ('0' + dateObj.getHours()).slice(-2) + ':' + ('0' + dateObj.getMinutes()).slice(-2);
            document.getElementById('modalDate').innerText = formattedDate;
            
            // Format Total
            document.getElementById('modalTotal').innerText = new Intl.NumberFormat('vi-VN').format(orderData.total) + ' đ';

            // Populate Products
            let productListHtml = '';
            if (orderData.items && orderData.items.length > 0) {
                orderData.items.forEach(item => {
                    let priceFormatted = new Intl.NumberFormat('vi-VN').format(item.price);
                    productListHtml += `
                        <li>
                            <div class="prod-name">${item.product_name} <br><span class="prod-qty">x${item.quantity}</span></div>
                            <div class="prod-price">${priceFormatted} đ</div>
                        </li>
                    `;
                });
            } else {
                productListHtml = '<li><div class="prod-name" style="color:#ee4d2d;">Không tìm thấy chi tiết sản phẩm (Lỗi lưu trữ)</div></li>';
            }
            document.getElementById('modalProductList').innerHTML = productListHtml;

            // Show Modal
            document.getElementById('detailModalOverlay').style.display = 'block';
            document.getElementById('detailModal').style.display = 'block';
        }

        function closeDetailModal() {
            document.getElementById('detailModalOverlay').style.display = 'none';
            document.getElementById('detailModal').style.display = 'none';
        }
    </script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
