<?php
include 'includes/header.php';

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['new_status'];
    
    // Lấy trạng thái hiện tại
    $check = $conn->query("SELECT status FROM orders WHERE id = $order_id");
    if ($check->num_rows > 0) {
        $current_status = $check->fetch_assoc()['status'];
        
        if ($current_status == 'delivered' || $current_status == 'cancelled') {
            echo "<script>alert('Không thể thay đổi trạng thái đơn hàng đã hoàn thành hoặc đã hủy!'); window.location.href='quanly_donhang.php';</script>";
            exit();
        }
        
        // Cập nhật trạng thái
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        $stmt->execute();
        
        // Nếu chuyển sang hủy (từ trạng thái khác hủy), hoàn trả kho
        if ($new_status == 'cancelled' && $current_status != 'cancelled') {
            $items_res = $conn->query("SELECT product_id, quantity, product_type FROM order_items WHERE order_id = $order_id");
            while ($item = $items_res->fetch_assoc()) {
                $pid = $item['product_id'];
                $qty = $item['quantity'];
                if ($item['product_type'] == 'sanpham') {
                    $conn->query("UPDATE sanpham SET so_luong = so_luong + $qty WHERE id = $pid");
                } else {
                    $conn->query("UPDATE phukien SET so_luong = so_luong + $qty WHERE id = $pid");
                }
            }
        }
        
        // Nếu chuyển từ hủy sang trạng thái khác, trừ kho lại
        if ($current_status == 'cancelled' && $new_status != 'cancelled') {
            $items_res = $conn->query("SELECT product_id, quantity, product_type FROM order_items WHERE order_id = $order_id");
            while ($item = $items_res->fetch_assoc()) {
                $pid = $item['product_id'];
                $qty = $item['quantity'];
                if ($item['product_type'] == 'sanpham') {
                    $conn->query("UPDATE sanpham SET so_luong = GREATEST(0, so_luong - $qty) WHERE id = $pid");
                } else {
                    $conn->query("UPDATE phukien SET so_luong = GREATEST(0, so_luong - $qty) WHERE id = $pid");
                }
            }
        }
        
        echo "<script>alert('Cập nhật trạng thái thành công!'); window.location.href='quanly_donhang.php';</script>";
        exit();
    }
}

// Lấy danh sách đơn hàng
$orders = $conn->query("SELECT orders.*, users.name as customer_name, users.email FROM orders LEFT JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Quản Lý Đơn Hàng</h2>
</div>

<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã Đơn</th>
                <th>Khách Hàng</th>
                <th>Địa Chỉ</th>
                <th>Tổng Tiền</th>
                <th>Ngày Đặt</th>
                <th>Trạng Thái</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if($orders->num_rows > 0): ?>
                <?php while($row = $orders->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                    <td>
                        <?php echo htmlspecialchars($row['customer_name'] ?? 'Khách'); ?><br>
                        <small style="color: #6b7280;"><?php echo htmlspecialchars($row['phone']); ?></small>
                    </td>
                    <td>
                        <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($row['shipping_address']); ?>">
                            <?php echo htmlspecialchars($row['shipping_address']); ?>
                        </div>
                    </td>
                    <td style="color:#ef4444; font-weight: 600;">
                        <?php echo number_format($row['total'], 0, ',', '.'); ?>đ
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <?php 
                            $status_class = '';
                            switch($row['status']) {
                                case 'pending': $status_class = 'status-pending'; break;
                                case 'confirmed': $status_class = 'status-confirmed'; break;
                                case 'shipping': $status_class = 'status-shipping'; break;
                                case 'delivered': $status_class = 'status-completed'; break;
                                case 'cancelled': $status_class = 'status-cancelled'; break;
                            }
                        ?>
                        <form method="POST" style="display: flex; align-items: center; gap: 5px;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                            <select name="new_status" class="form-select <?php echo $status_class; ?>" style="font-weight: 600;" onchange="this.form.submit()" <?php echo ($row['status'] == 'delivered' || $row['status'] == 'cancelled') ? 'disabled' : ''; ?>>
                                <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                <option value="confirmed" <?php echo $row['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                <option value="shipping" <?php echo $row['status'] == 'shipping' ? 'selected' : ''; ?>>Đang giao</option>
                                <option value="delivered" <?php echo $row['status'] == 'delivered' ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="cancelled" <?php echo $row['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <button class="btn btn-secondary" onclick="viewDetails(<?php echo $row['id']; ?>)">Chi tiết</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center;">Chưa có đơn hàng nào</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Script xem chi tiết đơn hàng -->
<script>
function viewDetails(id) {
    window.location.href = 'chitiet_donhang.php?id=' + id;
}
</script>

<?php include 'includes/footer.php'; ?>
