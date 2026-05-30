<?php
include 'includes/header.php';

// Xóa user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM users WHERE id = $id");
    echo "<script>alert('Đã xóa khách hàng thành công!'); window.location.href='quanly_khachhang.php';</script>";
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Quản Lý Khách Hàng</h2>
</div>

<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Họ Tên</th>
                <th>Email</th>
                <th>Số Điện Thoại</th>
                <th>Ngày Đăng Ký</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if($users->num_rows > 0): ?>
                <?php while($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['fullname'] ?? $row['name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không? (Hành động này sẽ xóa các dữ liệu liên quan)');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn" style="background:#ef4444; color:#fff;">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">Chưa có khách hàng nào</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
