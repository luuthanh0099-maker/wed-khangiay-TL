<?php
session_start();
require_once '../config/db.php';

// Tính tổng tiền giỏ hàng
$totalCartValue = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $totalCartValue += $item['price'] * $item['quantity'];
    }
}

// Xử lý Voucher
$discount = 0;
$voucherCode = '';
if (isset($_SESSION['voucher'])) {
    $voucherCode = $_SESSION['voucher']['code'];
    $discountPercent = $_SESSION['voucher']['discount_percent'];
    $discount = $totalCartValue * ($discountPercent / 100);
}

$finalPrice = max(0, $totalCartValue - $discount);

$freeShippingThreshold = 100000;
$remainingForFreeShipping = $freeShippingThreshold - $totalCartValue;

// Lấy danh sách Voucher từ CSDL
$vouchersQuery = $conn->query("SELECT * FROM tintuc WHERE type = 'voucher' ORDER BY id DESC");
$vouchers = [];
if ($vouchersQuery && $vouchersQuery->num_rows > 0) {
    while($row = $vouchersQuery->fetch_assoc()) {
        $vouchers[] = $row;
    }
}

// Lấy thông tin user nếu đã đăng nhập
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
}

// Xử lý submit form thanh toán
$payment_error = '';
$payment_success = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'checkout') {
    $address = trim($_POST['address']);
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';
    $user_id = $_SESSION['user_id'] ?? 0;

    if (empty($address)) {
        $payment_error = "Vui lòng nhập địa chỉ nhận hàng.";
    } elseif ($user_id == 0 || empty($_SESSION['cart'])) {
        $payment_error = "Giỏ hàng trống hoặc bạn chưa đăng nhập.";
    } else {
        $_SESSION['address'] = $address;
        
        $phone = $user['phone'] ?? '';
        $status = 'pending';
        
        // 1. Insert into orders
        $stmt = $conn->prepare("INSERT INTO orders (user_id, voucher_code, total, shipping_address, phone, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsss", $user_id, $voucherCode, $finalPrice, $address, $phone, $status);
        
        if ($stmt->execute()) {
            $order_id = $conn->insert_id;
            
            // 2. Insert order_items and update stock
            foreach ($_SESSION['cart'] as $cart_key => $item) {
                $qty = $item['quantity'];
                $price = $item['price'];
                $type = ($item['type'] == 'sanpham') ? 'sanpham' : 'phukien';
                
                $parts = explode('_', $cart_key);
                $product_id = intval(end($parts));
                
                $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, product_type) VALUES (?, ?, ?, ?, ?)");
                $stmt_item->bind_param("iiids", $order_id, $product_id, $qty, $price, $type);
                $stmt_item->execute();
                
                // Deduct stock
                if ($type == 'sanpham') {
                    $conn->query("UPDATE sanpham SET so_luong = GREATEST(0, so_luong - $qty) WHERE id = $product_id");
                } else {
                    $conn->query("UPDATE phukien SET so_luong = GREATEST(0, so_luong - $qty) WHERE id = $product_id");
                }
            }
            
            // 3. Clear cart
            unset($_SESSION['cart']);
            unset($_SESSION['voucher']);
            
            if ($payment_method === 'online') {
                header("Location: thanhtoan_online.php?id=" . $order_id);
                exit();
            } else {
                // Thanh toán khi nhận hàng
                $payment_success = "Sản phẩm đã được đặt thành công";
            }
        } else {
            $payment_error = "Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
    <style>
        /* CSS riêng cho Giỏ hàng */
        body {
            background-color: #f5f5f5; /* Nền xám nhạt giúp nổi bật các khối trắng */
        }
        .cart-page-wrapper {
            padding-bottom: 150px; /* Dành khoảng trống cho sticky footer */
        }
        .cart-container {
            max-width: 1000px;
            margin: 30px auto;
        }
        .cart-header-title {
            font-size: 22px;
            color: #222;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .free-shipping-bar {
            background-color: #f2fcf5;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .free-shipping-bar i {
            font-size: 18px;
        }
        .cart-shop-section {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,.05);
            margin-bottom: 15px;
        }
        .cart-shop-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .badge-mall {
            background: #ee4d2d;
            color: #fff;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 12px;
            font-weight: 500;
        }
        .shop-name {
            font-weight: 500;
            font-size: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .cart-item-row {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .cart-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #1b8a44; /* Màu xanh lá thương hiệu */
        }
        .cart-item-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border: 1px solid #e5e7eb;
        }
        .cart-item-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .cart-item-name {
            font-size: 15px;
            color: #333;
            text-decoration: none;
            display: block;
        }
        .cart-item-variant {
            color: #757575;
            font-size: 13px;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            width: max-content;
        }
        .cart-item-price {
            font-size: 16px;
            color: #1b8a44;
            font-weight: 500;
            width: 130px;
            text-align: right;
        }
        .qty-selector {
            display: flex;
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 0 20px;
        }
        .qty-btn {
            width: 32px;
            height: 32px;
            background: #fff;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #333;
        }
        .qty-btn:hover { background: #f9fafb; }
        .qty-input {
            width: 45px;
            height: 32px;
            border: none;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            text-align: center;
            font-size: 15px;
            outline: none;
        }
        /* Ẩn mũi tên lên xuống mặc định của input type number */
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .qty-input[type=number] {
            -moz-appearance: textfield;
        }
        .cart-item-actions {
            color: #757575;
            cursor: pointer;
            font-size: 18px;
            margin-left: 10px;
            transition: color 0.2s;
        }
        .cart-item-actions:hover { color: #ee4d2d; }

        /* Sticky Footer */
        .cart-footer-sticky {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 100;
            border-top: 1px solid #e5e7eb;
        }
        .cart-footer-content {
            max-width: 1000px;
            margin: 0 auto;
        }
        .voucher-row {
            padding: 15px 20px;
            border-bottom: 1px dashed #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }
        .voucher-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #333;
        }
        .voucher-label i {
            color: #fbbc05;
            font-size: 18px;
        }
        .voucher-action {
            color: #555;
            cursor: pointer;
        }
        .checkout-row {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .checkout-left {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 15px;
            color: #333;
            cursor: pointer;
        }
        .checkout-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .total-price-label {
            font-size: 16px;
            color: #333;
        }
        .total-price-value {
            font-size: 24px;
            color: #1b8a44;
            font-weight: 500;
        }
        .btn-checkout {
            background: #1b8a44;
            color: #fff;
            border: none;
            padding: 12px 45px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-checkout:hover { background: #146c35; }
    </style>
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
                    <li><a href="danhsach_phukien.php">Phụ kiện</a></li>
                    <li><a href="tintuc.php">Tin tức</a></li>
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

    <div class="cart-page-wrapper">
        <div class="cart-container">
            <h1 class="cart-header-title">Giỏ hàng (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</h1>

            <!-- Thanh thông báo Miễn phí vận chuyển -->
            <?php if ($remainingForFreeShipping > 0): ?>
            <div class="free-shipping-bar">
                <i class="fa-solid fa-truck-fast"></i>
                Mua thêm <strong><?php echo number_format($remainingForFreeShipping, 0, ',', '.'); ?> đ</strong> để được Miễn phí vận chuyển!
            </div>
            <?php else: ?>
            <div class="free-shipping-bar" style="background-color: #e6f4ea; border-color: #ceead6; color: #137333;">
                <i class="fa-solid fa-circle-check"></i>
                Tuyệt vời! Bạn đã đạt mốc <strong>Miễn phí vận chuyển</strong>!
            </div>
            <?php endif; ?>

            <!-- Shop Section -->
            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                <div class="cart-shop-section">
                    <?php foreach($_SESSION['cart'] as $key => $item): ?>
                    <!-- Dòng sản phẩm -->
                    <div class="cart-item-row" data-price="<?php echo $item['price']; ?>" data-qty="<?php echo $item['quantity']; ?>">
                        <input type="checkbox" class="cart-checkbox item-checkbox" data-key="<?php echo $key; ?>" checked onchange="calculateTotal()">
                        <img src="../<?php echo htmlspecialchars($item['img']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img" onerror="this.src='../images/logo.jpg'">
                        
                        <div class="cart-item-details">
                            <a href="#" class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></a>
                            <div class="cart-item-variant">
                                Loại: <?php echo $item['type'] == 'sanpham' ? 'Khăn giấy' : 'Phụ kiện'; ?>
                            </div>
                        </div>

                        <div class="cart-item-price"><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</div>

                        <div class="qty-selector">
                            <button class="qty-btn" onclick="updateQty('<?php echo $key; ?>', <?php echo $item['quantity'] - 1; ?>)"><i class="fas fa-minus"></i></button>
                            <input type="number" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" onchange="updateQty('<?php echo $key; ?>', parseInt(this.value) || 1)">
                            <button class="qty-btn" onclick="updateQty('<?php echo $key; ?>', <?php echo $item['quantity'] + 1; ?>)"><i class="fas fa-plus"></i></button>
                        </div>

                        <div class="cart-item-actions" onclick="removeFromCart('<?php echo $key; ?>')">
                            <i class="fa-solid fa-trash-can"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 20px; background: #fff; border-radius: 4px; box-shadow: 0 1px 1px 0 rgba(0,0,0,.05); margin-bottom: 20px;">
                    <i class="fa-solid fa-cart-arrow-down" style="font-size: 60px; color: #ccc; margin-bottom: 20px;"></i>
                    <h2 style="color: #555; font-size: 18px; margin-bottom: 20px;">Giỏ hàng của bạn còn trống</h2>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="#" onclick="alert('Mời quý khách đăng nhập trước khi mua hàng'); return false;" class="btn-checkout" style="display: inline-block; text-decoration: none;">MUA SẮM NGAY</a>
                    <?php else: ?>
                        <a href="index.php" class="btn-checkout" style="display: inline-block; text-decoration: none;">MUA SẮM NGAY</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sticky Footer Checkout -->
        <div class="cart-footer-sticky">
            <div class="cart-footer-content">
                <div class="voucher-row" onclick="openVoucherModal()" style="cursor: pointer;">
                    <div class="voucher-label">
                        <i class="fa-solid fa-ticket" style="color: #ee4d2d;"></i> Thêm Shop Voucher
                    </div>
                    <div class="voucher-action">
                        <span id="voucherActionText">
                            <?php if ($voucherCode): ?>
                                <span style="color: #ee4d2d; font-weight: bold;">- <?php echo number_format($discount, 0, ',', '.'); ?> đ</span>
                            <?php else: ?>
                                Chọn Voucher
                            <?php endif; ?>
                        </span>
                        <i class="fas fa-chevron-right" style="font-size:12px; margin-left:5px;"></i>
                    </div>
                </div>
                
                <div class="checkout-row">
                    <label class="checkout-left">
                        <input type="checkbox" class="cart-checkbox" id="checkAll" checked onchange="toggleAllCheckboxes()">
                        Tất cả
                    </label>

                    <div class="checkout-right">
                        <div class="total-price-label">Tổng thanh toán:</div>
                        <div class="total-price-value" id="finalPriceDisplay"><?php echo number_format($finalPrice, 0, ',', '.'); ?> đ</div>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <button class="btn-checkout" onclick="alert('Mời quý khách đăng nhập trước khi mua hàng'); return false;">Mua hàng</button>
                        <?php else: ?>
                            <button class="btn-checkout" onclick="openPaymentModal()">Mua hàng</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script chung cho menu thả xuống -->
    <script src="js/main.js?v=1779356416"></script>
    <script>
        let appliedVoucherPercent = <?php echo isset($discountPercent) ? $discountPercent : 0; ?>;

        function calculateTotal() {
            let total = 0;
            const itemRows = document.querySelectorAll('.cart-item-row');
            
            itemRows.forEach(row => {
                const checkbox = row.querySelector('.item-checkbox');
                if (checkbox && checkbox.checked) {
                    const price = parseFloat(row.getAttribute('data-price')) || 0;
                    const qty = parseInt(row.getAttribute('data-qty')) || 0;
                    total += price * qty;
                }
            });

            // Calculate discount
            const discount = total * (appliedVoucherPercent / 100);
            let finalPrice = Math.max(0, total - discount);

            // Update UI
            document.getElementById('finalPriceDisplay').innerText = new Intl.NumberFormat('vi-VN').format(finalPrice) + ' đ';
            
            // Check if all are checked to update 'checkAll' state
            const allCheckboxes = document.querySelectorAll('.item-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.item-checkbox:checked');
            const checkAllBtn = document.getElementById('checkAll');
            if (checkAllBtn) {
                checkAllBtn.checked = (allCheckboxes.length > 0 && allCheckboxes.length === checkedCheckboxes.length);
            }

            // Cập nhật giao diện thanh Voucher
            const voucherRow = document.querySelector('.voucher-row');
            const voucherActionText = document.getElementById('voucherActionText');
            if (voucherRow) {
                if (checkedCheckboxes.length === 0) {
                    voucherRow.style.opacity = '0.5';
                    voucherRow.style.cursor = 'not-allowed';
                    if (voucherActionText) voucherActionText.innerHTML = 'Chọn Voucher';
                    
                    // Reset voucher khi không có sản phẩm nào được chọn
                    if (appliedVoucherPercent > 0) {
                        appliedVoucherPercent = 0;
                        const formData = new FormData();
                        formData.append('action', 'apply_voucher');
                        formData.append('code', '');
                        formData.append('discount', 0);
                        fetch('xuly_capnhatgiohang.php', { method: 'POST', body: formData });
                    }
                } else {
                    voucherRow.style.opacity = '1';
                    voucherRow.style.cursor = 'pointer';
                    if (voucherActionText) {
                        if (appliedVoucherPercent > 0) {
                            voucherActionText.innerHTML = '<span style="color: #ee4d2d; font-weight: bold;">- ' + new Intl.NumberFormat('vi-VN').format(discount) + ' đ</span>';
                        } else {
                            voucherActionText.innerHTML = 'Chọn Voucher';
                        }
                    }
                }
            }

            // Save states
            saveCheckboxStates();
        }

        function toggleAllCheckboxes() {
            const checkAllBtn = document.getElementById('checkAll');
            const isChecked = checkAllBtn.checked;
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            
            itemCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            
            calculateTotal();
        }

        function saveCheckboxStates() {
            const states = {};
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                states[cb.getAttribute('data-key')] = cb.checked;
            });
            sessionStorage.setItem('cartCheckboxStates', JSON.stringify(states));
        }

        function loadCheckboxStates() {
            const statesStr = sessionStorage.getItem('cartCheckboxStates');
            if (statesStr) {
                const states = JSON.parse(statesStr);
                document.querySelectorAll('.item-checkbox').forEach(cb => {
                    const key = cb.getAttribute('data-key');
                    if (states.hasOwnProperty(key)) {
                        cb.checked = states[key];
                    }
                });
            }
        }

        function openPaymentModal() {
            const checkedItems = document.querySelectorAll('.item-checkbox:checked');
            if (checkedItems.length === 0) {
                alert("Vui lòng chọn ít nhất 1 sản phẩm để thanh toán.");
                return;
            }
            document.getElementById('paymentModalOverlay').style.display = 'block';
            document.getElementById('paymentModal').style.display = 'block';
        }

        function closePaymentModal() {
            document.getElementById('paymentModalOverlay').style.display = 'none';
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Khởi tạo khi load trang
        document.addEventListener('DOMContentLoaded', () => {
            loadCheckboxStates();
            calculateTotal();
            
            <?php if (!empty($payment_error) || !empty($payment_success)): ?>
            document.getElementById('paymentModalOverlay').style.display = 'block';
            document.getElementById('paymentModal').style.display = 'block';
            <?php endif; ?>
            
            <?php if (!empty($payment_success)): ?>
            setTimeout(function() {
                window.location.href = 'lichsu_muahang.php';
            }, 500);
            <?php endif; ?>
        });
    </script>
    <!-- VOUCHER MODAL -->
    <div class="modal-overlay" id="voucherModalOverlay" onclick="closeVoucherModal()" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;"></div>
    <div class="voucher-modal" id="voucherModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; width:400px; max-width:90%; border-radius:8px; z-index:1000; padding:20px; box-shadow:0 5px 15px rgba(0,0,0,0.3);">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; text-align: center; color: #333;">🛒 Chọn Shop Voucher</h3>
        <div class="voucher-list" style="max-height:300px; overflow-y:auto; padding-right:5px; margin-top: 15px;">
            <?php foreach($vouchers as $v): ?>
            <div class="voucher-item" style="border:1px solid #ee4d2d; border-radius:6px; padding:15px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; background: #fffcfb;">
                <div style="flex-grow: 1; border-right: 1px dashed #ee4d2d; padding-right: 15px; margin-right: 15px;">
                    <strong style="color:#ee4d2d; font-size: 18px; display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($v['title']); ?></strong>
                    <div style="font-size:13px; color:#666;">Giảm <?php echo $v['discount_percent']; ?>% - HSD: <?php echo date('d/m/Y', strtotime($v['expiry_date'])); ?></div>
                </div>
                <button class="btn-checkout" style="padding:8px 20px; font-size:14px; white-space: nowrap; border-radius: 4px;" onclick="applyVoucher('<?php echo htmlspecialchars($v['title']); ?>', <?php echo $v['discount_percent']; ?>)">Dùng</button>
            </div>
            <?php endforeach; ?>
            <?php if(empty($vouchers)): ?>
                <p style="text-align:center; color:#888;">Hiện chưa có voucher nào.</p>
            <?php endif; ?>
        </div>
        <div style="text-align:right; margin-top:20px;">
            <button style="padding:10px 25px; border:none; background:#f5f5f5; color:#555; cursor:pointer; border-radius:4px; font-weight: bold;" onclick="closeVoucherModal()">Đóng</button>
        </div>
    </div>

    <!-- PAYMENT MODAL -->
    <div class="modal-overlay" id="paymentModalOverlay" onclick="closePaymentModal()" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;"></div>
    <div class="payment-modal" id="paymentModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); background:#fff; width:500px; max-width:90%; border-radius:8px; z-index:1000; padding:25px; box-shadow:0 5px 15px rgba(0,0,0,0.3); text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333; font-size: 18px;">Thông tin khách hàng</h3>
            <i class="fas fa-times" onclick="closePaymentModal()" style="cursor: pointer; font-size: 20px; color: #888;"></i>
        </div>
        
        <?php if(!empty($payment_error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px;"><?php echo $payment_error; ?></div>
        <?php endif; ?>
        <?php if(!empty($payment_success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px;"><?php echo $payment_success; ?></div>
        <?php endif; ?>

        <?php if (isset($user)): ?>
        <form method="POST" action="giohang.php">
            <input type="hidden" name="action" value="checkout">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-size: 14px;">Họ và tên</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f5f5f5; cursor: not-allowed; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-size: 14px;">Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f5f5f5; cursor: not-allowed; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-size: 14px;">Số điện thoại</label>
                <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f5f5f5; cursor: not-allowed; box-sizing: border-box;">
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; color: #555; font-size: 14px;">Địa chỉ nhận hàng <span style="color: red;">*</span></label>
                <textarea name="address" class="form-control" required rows="3" placeholder="Nhập địa chỉ nhận hàng của bạn..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical;"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="submit" name="payment_method" value="cod" class="btn-checkout" style="width: 100%; padding: 12px; background-color: #1b8a44; color: #fff; border: none; border-radius: 4px; font-size: 16px; font-weight: 500; cursor: pointer;">Thanh toán khi nhận hàng</button>
                <button type="submit" name="payment_method" value="online" class="btn-checkout" style="width: 100%; padding: 12px; background-color: #007bff; color: #fff; border: none; border-radius: 4px; font-size: 16px; font-weight: 500; cursor: pointer;">Thanh toán online</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php include 'chantrang.php'; ?>
</body>
</html>
