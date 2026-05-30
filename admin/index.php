<?php
include 'includes/header.php';

// Tổng doanh thu (Chỉ tính đơn đã thanh toán hoặc hoàn thành)
$rev_res = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status IN ('confirmed', 'shipping', 'delivered')");
$revenue = $rev_res->fetch_assoc()['revenue'] ?? 0;

// Tổng số đơn hàng
$ord_res = $conn->query("SELECT COUNT(id) as total_orders FROM orders");
$total_orders = $ord_res->fetch_assoc()['total_orders'] ?? 0;

// Tổng số khách hàng
$usr_res = $conn->query("SELECT COUNT(id) as total_users FROM users WHERE role = 'user'");
$total_users = $usr_res->fetch_assoc()['total_users'] ?? 0;

// Cảnh báo hết hàng (Sản phẩm & Phụ kiện <= 20)
$stock_res = $conn->query("SELECT ((SELECT COUNT(id) FROM sanpham WHERE so_luong <= 20) + (SELECT COUNT(id) FROM phukien WHERE so_luong <= 20)) as low_stock");
$low_stock = $stock_res->fetch_assoc()['low_stock'] ?? 0;

// Lấy 5 đơn hàng mới nhất
$recent_orders = $conn->query("SELECT orders.*, users.name as customer_name FROM orders LEFT JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC LIMIT 5");
?>

<h2 style="margin-bottom: 25px; color: #1f2937;">Tổng Quan Hệ Thống</h2>

<!-- Dashboard Cards -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-info">
            <h3>Tổng Doanh Thu</h3>
            <p><?php echo number_format($revenue, 0, ',', '.'); ?> đ</p>
        </div>
        <div class="card-icon icon-blue"><i class="fas fa-money-bill-wave"></i></div>
    </div>
    
    <div class="card">
        <div class="card-info">
            <h3>Đơn Hàng</h3>
            <p><?php echo $total_orders; ?></p>
        </div>
        <div class="card-icon icon-green"><i class="fas fa-shopping-cart"></i></div>
    </div>
    
    <div class="card">
        <div class="card-info">
            <h3>Khách Hàng</h3>
            <p><?php echo $total_users; ?></p>
        </div>
        <div class="card-icon icon-yellow"><i class="fas fa-users"></i></div>
    </div>
    
    <div class="card" onclick="window.location.href='quanly_sanpham.php';" style="cursor: pointer;">
        <div class="card-info">
            <h3>Cảnh Báo Kho</h3>
            <p><?php echo $low_stock; ?> mục</p>
        </div>
        <div class="card-icon icon-red"><i class="fas fa-exclamation-triangle"></i></div>
    </div>
</div>

<!-- Đơn hàng mới nhất -->
<div class="table-container">
    <div class="table-header">
        <div class="table-title">Đơn hàng mới nhất</div>
        <a href="quanly_donhang.php" class="btn btn-secondary">Xem tất cả</a>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã Đơn</th>
                <th>Khách Hàng</th>
                <th>Tổng Tiền</th>
                <th>Thời Gian</th>
                <th>Trạng Thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if($recent_orders->num_rows > 0): ?>
                <?php while($row = $recent_orders->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                    <td><?php echo htmlspecialchars($row['customer_name'] ?? 'Khách'); ?></td>
                    <td style="color:#ef4444; font-weight: 600;"><?php echo number_format($row['total'], 0, ',', '.'); ?>đ</td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                    <td>
                        <?php 
                            $status_class = '';
                            $status_text = '';
                            switch($row['status']) {
                                case 'pending': $status_class = 'status-pending'; $status_text = 'Chờ xử lý'; break;
                                case 'confirmed': $status_class = 'status-confirmed'; $status_text = 'Đã xác nhận'; break;
                                case 'shipping': $status_class = 'status-shipping'; $status_text = 'Đang giao'; break;
                                case 'delivered': $status_class = 'status-completed'; $status_text = 'Hoàn thành'; break;
                                case 'cancelled': $status_class = 'status-cancelled'; $status_text = 'Đã hủy'; break;
                            }
                        ?>
                        <span class="status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">Chưa có đơn hàng nào</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
