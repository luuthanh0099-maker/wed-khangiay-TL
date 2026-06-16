<?php
include 'includes/header.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    echo "<script>alert('ID đơn hàng không hợp lệ!'); window.location.href='quanly_donhang.php';</script>";
    exit();
}

// Lấy thông tin đơn hàng và khách hàng
$stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='quanly_donhang.php';</script>";
    exit();
}

// Lấy danh sách sản phẩm trong đơn hàng
$items_stmt = $pdo->prepare("
    SELECT oi.*, 
           CASE 
               WHEN oi.product_type = 'sanpham' THEN (SELECT ten_sanpham FROM sanpham WHERE id = oi.product_id)
               WHEN oi.product_type = 'phukien' THEN (SELECT ten_phukien FROM phukien WHERE id = oi.product_id)
           END as product_name,
           CASE 
               WHEN oi.product_type = 'sanpham' THEN (SELECT hinhanh FROM sanpham WHERE id = oi.product_id)
               WHEN oi.product_type = 'phukien' THEN (SELECT hinhanh FROM phukien WHERE id = oi.product_id)
           END as product_image
    FROM order_items oi 
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order_id]);
$items_result = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$order_items = [];
$subtotal = 0;
foreach($items_result as $item) {
    $order_items[] = $item;
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Chi Tiết Đơn Hàng #<?php echo $order['id']; ?></h2>
    <a href="quanly_donhang.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
    <!-- Thông tin khách hàng & Giao hàng -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; color: #374151;">Thông Tin Khách Hàng</h3>
        <p style="margin-bottom: 10px;"><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Khách'); ?></p>
        <p style="margin-bottom: 10px;"><strong>Email:</strong> <?php echo htmlspecialchars($order['email'] ?? 'Không có'); ?></p>
        <p style="margin-bottom: 10px;"><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
        <p style="margin-bottom: 10px;"><strong>Địa chỉ giao hàng:</strong><br> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        
        <h3 style="margin-top: 25px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; color: #374151;">Trạng Thái & Thanh Toán</h3>
        <p style="margin-bottom: 10px;"><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
        <p style="margin-bottom: 10px;">
            <strong>Trạng thái:</strong> 
            <?php 
                $status_text = '';
                switch($order['status']) {
                    case 'pending': $status_text = '<span style="color:#eab308; font-weight:600;">Chờ xử lý</span>'; break;
                    case 'confirmed': $status_text = '<span style="color:#3b82f6; font-weight:600;">Đã xác nhận</span>'; break;
                    case 'shipping': $status_text = '<span style="color:#8b5cf6; font-weight:600;">Đang giao</span>'; break;
                    case 'delivered': 
                    case 'completed': $status_text = '<span style="color:#10b981; font-weight:600;">Hoàn thành</span>'; break;
                    case 'cancelled': $status_text = '<span style="color:#ef4444; font-weight:600;">Đã hủy</span>'; break;
                }
                echo $status_text;
            ?>
        </p>
        <?php if (!empty($order['voucher_code'])): ?>
        <p style="margin-bottom: 10px;"><strong>Mã giảm giá áp dụng:</strong> <span style="color:#ee4d2d; font-weight:bold;"><?php echo htmlspecialchars($order['voucher_code']); ?></span></p>
        <div style="background-color: #fdf2f2; color: #c53030; padding: 10px; border-radius: 4px; margin-bottom: 10px; font-size: 14px; border-left: 4px solid #c53030;">
            <i class="fas fa-check-circle"></i> Đã áp dụng mã giảm giá và trừ tiền vào đơn hàng của khách.
        </div>
        <?php endif; ?>
        <p style="margin-bottom: 10px;">
            <strong>Miễn phí ship:</strong> 
            <?php echo ($subtotal >= 100000) ? '<span style="color:#10b981; font-weight:600;"><i class="fas fa-check-circle"></i> Có (Đơn >= 100k)</span>' : '<span style="color:#ef4444; font-weight:600;"><i class="fas fa-times-circle"></i> Không (Đơn < 100k)</span>'; ?>
        </p>
        <p style="margin-top: 15px; font-size: 18px;"><strong>Tổng Tiền:</strong> <span style="color:#ef4444; font-weight:bold;"><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</span></p>
    </div>

    <!-- Danh sách sản phẩm -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; color: #374151;">Sản Phẩm Đã Đặt</h3>
        <div class="table-container" style="box-shadow: none; border: 1px solid #eee;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Hình Ảnh</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Loại</th>
                        <th>Số Lượng</th>
                        <th>Đơn Giá</th>
                        <th>Thành Tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(count($order_items) > 0): 
                        foreach($order_items as $item): 
                            $item_total = $item['price'] * $item['quantity'];
                    ?>
                        <tr>
                            <td>
                                <img src="../<?php echo htmlspecialchars($item['product_image']); ?>" alt="Img" style="width: 50px; height: 50px; object-fit: contain; border-radius: 4px; border: 1px solid #ddd;">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <?php if (!empty($item['color'])): ?>
                                    <br><small style="color: #666;">Màu: <?php echo htmlspecialchars($item['color']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 12px; padding: 3px 8px; border-radius: 4px; background: <?php echo $item['product_type'] == 'sanpham' ? '#e0f2fe; color: #0284c7;' : '#f3e8ff; color: #9333ea;'; ?>">
                                    <?php echo $item['product_type'] == 'sanpham' ? 'Sản phẩm' : 'Phụ kiện'; ?>
                                </span>
                            </td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $item['quantity']; ?></td>
                            <td><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                            <td style="font-weight: 600; color: #1f2937;"><?php echo number_format($item_total, 0, ',', '.'); ?>đ</td>
                        </tr>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                        <tr><td colspan="6" style="text-align:center;">Không có dữ liệu sản phẩm</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Tính toán lại nếu có chênh lệch do voucher -->
        <?php if ($subtotal != $order['total']): ?>
        <div style="margin-top: 15px; text-align: right; font-size: 15px; color: #555;">
            Tạm tính: <strong><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</strong><br>
            Giảm giá: <strong>-<?php echo number_format($subtotal - $order['total'], 0, ',', '.'); ?>đ</strong>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
