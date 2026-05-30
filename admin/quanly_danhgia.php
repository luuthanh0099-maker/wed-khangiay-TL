<?php
include 'includes/header.php';

// Xóa đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = intval($_POST['id']);
    $conn->query("DELETE FROM reviews WHERE id = $id");
    echo "<script>alert('Đã xóa đánh giá thành công!'); window.location.href='quanly_danhgia.php';</script>";
    exit();
}

$sql = "
    SELECT r.*, u.name as user_name, 
        CASE 
            WHEN r.item_type = 'sanpham' THEN (SELECT ten_sanpham FROM sanpham WHERE id = r.item_id)
            WHEN r.item_type = 'phukien' THEN (SELECT ten_phukien FROM phukien WHERE id = r.item_id)
        END as item_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";
$reviews = $conn->query($sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Quản Lý Đánh Giá</h2>
</div>

<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Khách Hàng</th>
                <th>Sản Phẩm / Phụ Kiện</th>
                <th>Đánh Giá</th>
                <th>Nội Dung</th>
                <th>Ngày Đăng</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if($reviews && $reviews->num_rows > 0): ?>
                <?php while($row = $reviews->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['user_name']); ?></strong></td>
                    <td>
                        <span style="display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-bottom: 5px; background: <?php echo $row['item_type'] == 'sanpham' ? '#e0f2fe; color: #0284c7;' : '#f3e8ff; color: #9333ea;'; ?>">
                            <?php echo $row['item_type'] == 'sanpham' ? 'Sản phẩm' : 'Phụ kiện'; ?>
                        </span><br>
                        <?php echo htmlspecialchars($row['item_name']); ?>
                    </td>
                    <td>
                        <div style="color: #f59e0b; font-size: 14px;">
                            <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $row['rating']) echo '<i class="fas fa-star"></i>';
                                else echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                    </td>
                    <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars($row['comment']); ?>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đánh giá này không?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn" style="background:#ef4444; color:#fff; padding: 6px 12px; font-size: 13px;">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">Chưa có đánh giá nào</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
