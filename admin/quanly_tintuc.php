<?php
include 'includes/header.php';

// Xử lý Xóa
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tintuc WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>alert('Đã xóa thành công!'); window.location.href='quanly_tintuc.php';</script>";
    exit();
}

// Xử lý Thêm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $type = $_POST['type'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $discount_percent = !empty($_POST['discount_percent']) ? intval($_POST['discount_percent']) : null;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : null;

    $stmt = $conn->prepare("INSERT INTO tintuc (type, title, content, discount_percent, expiry_date, quantity) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisi", $type, $title, $content, $discount_percent, $expiry_date, $quantity);
    $stmt->execute();
    echo "<script>alert('Đã thêm tin tức/khuyến mãi mới!'); window.location.href='quanly_tintuc.php';</script>";
    exit();
}

$tintucList = $conn->query("SELECT * FROM tintuc ORDER BY type, id DESC");
?>

<style>
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-weight: 500; margin-bottom: 5px; color: #374151; }
.form-control { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; }
.voucher-fields, .khuyenmai-fields { display: none; }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Quản Lý Tin Tức & Voucher</h2>
</div>

<!-- Form Thêm mới -->
<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px; font-size: 16px;">Thêm mới</h3>
    <form action="" method="POST">
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label>Loại bản tin</label>
            <select name="type" id="type" class="form-control" onchange="toggleFields()">
                <option value="khuyenmai">Chương trình khuyến mãi</option>
                <option value="voucher">Voucher khuyến mãi</option>
                <option value="thongbao">Thông báo</option>
            </select>
        </div>
        
        <div class="form-group khuyenmai-fields voucher-fields">
            <label>Tiêu đề (hoặc Mã Voucher)</label>
            <input type="text" name="title" class="form-control" placeholder="VD: Mua 2 tặng 1 hoặc KM10">
        </div>

        <div class="form-group khuyenmai-fields thongbao-fields">
            <label>Nội dung</label>
            <textarea name="content" rows="3" class="form-control" placeholder="Chi tiết chương trình hoặc nội dung thông báo..."></textarea>
        </div>

        <div style="display: flex; gap: 15px;">
            <div class="form-group voucher-fields" style="flex: 1;">
                <label>Phần trăm giảm giá (%)</label>
                <input type="number" name="discount_percent" class="form-control" placeholder="VD: 10">
            </div>

            <div class="form-group voucher-fields" style="flex: 1;">
                <label>Số lượng Voucher</label>
                <input type="number" name="quantity" class="form-control" placeholder="VD: 100">
            </div>

            <div class="form-group voucher-fields" style="flex: 1;">
                <label>Ngày hết hạn</label>
                <input type="datetime-local" name="expiry_date" class="form-control">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Thêm Bản Tin</button>
    </form>
</div>

<!-- Danh sách -->
<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Phân Loại</th>
                <th>Tiêu đề / Nội dung chính</th>
                <th>Chi tiết (KM/Voucher)</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if($tintucList->num_rows > 0): ?>
                <?php while($row = $tintucList->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <?php 
                            $type_color = '';
                            $type_name = '';
                            if($row['type'] == 'khuyenmai') { $type_color = '#2563eb'; $type_name = 'Khuyến mãi'; }
                            if($row['type'] == 'voucher') { $type_color = '#16a34a'; $type_name = 'Voucher'; }
                            if($row['type'] == 'thongbao') { $type_color = '#d97706'; $type_name = 'Thông báo'; }
                        ?>
                        <span style="color: <?php echo $type_color; ?>; font-weight: bold;"><?php echo strtoupper($type_name); ?></span>
                    </td>
                    <td>
                        <strong style="display:block; margin-bottom:5px;"><?php echo htmlspecialchars($row['title']); ?></strong>
                        <div style="color: #4b5563; font-size: 13px;"><?php echo htmlspecialchars($row['content']); ?></div>
                    </td>
                    <td style="font-size: 13px;">
                        <?php if($row['type'] == 'voucher'): ?>
                            <strong>Giảm:</strong> <?php echo $row['discount_percent']; ?>%<br>
                            <strong>Số lượng:</strong> <?php echo $row['quantity']; ?><br>
                            <strong>HSD:</strong> <?php echo date('d/m/Y H:i', strtotime($row['expiry_date'])); ?>
                        <?php else: ?>
                            <span style="color: #9ca3af;">Không có</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn" style="background:#ef4444; color:#fff;" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">Chưa có dữ liệu.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function toggleFields() {
    var type = document.getElementById('type').value;
    document.querySelectorAll('.voucher-fields, .khuyenmai-fields, .thongbao-fields').forEach(el => el.style.display = 'none');
    if(type === 'khuyenmai') {
        document.querySelectorAll('.khuyenmai-fields').forEach(el => el.style.display = 'block');
    } else if(type === 'voucher') {
        document.querySelectorAll('.voucher-fields').forEach(el => el.style.display = 'block');
    } else if(type === 'thongbao') {
        document.querySelectorAll('.thongbao-fields').forEach(el => el.style.display = 'block');
    }
}
window.onload = toggleFields;
</script>

<?php include 'includes/footer.php'; ?>
