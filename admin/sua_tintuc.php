<?php
include 'includes/header.php';

if (!isset($_GET['id'])) {
    echo "<script>window.location.href='quanly_tintuc.php';</script>";
    exit();
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM tintuc WHERE id = ?");
$stmt->execute([$id]);
$tintuc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tintuc) {
    echo "<script>alert('Không tìm thấy dữ liệu!'); window.location.href='quanly_tintuc.php';</script>";
    exit();
}

// Xử lý Cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $type = $_POST['type'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $discount_percent = !empty($_POST['discount_percent']) ? intval($_POST['discount_percent']) : null;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $quantity = !empty($_POST['quantity']) ? intval($_POST['quantity']) : null;

    $stmt_update = $pdo->prepare("UPDATE tintuc SET type = ?, title = ?, content = ?, discount_percent = ?, expiry_date = ?, quantity = ? WHERE id = ?");
    
    if ($stmt_update->execute([$type, $title, $content, $discount_percent, $expiry_date, $quantity, $id])) {
        echo "<script>alert('Đã cập nhật thành công!'); window.location.href='quanly_tintuc.php';</script>";
        exit();
    } else {
        $error = "Có lỗi xảy ra khi cập nhật.";
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Sửa Tin Tức / Voucher #<?php echo $id; ?></h2>
    <a href="quanly_tintuc.php" class="btn btn-outline">Quay Lại</a>
</div>

<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <?php if(isset($error)): ?>
        <div style="color: red; margin-bottom: 15px;"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="" method="POST">
        <input type="hidden" name="action" value="update">
        
        <div class="form-group">
            <label>Loại bản tin</label>
            <select name="type" id="type" class="form-control" onchange="toggleFields()">
                <option value="khuyenmai" <?php echo $tintuc['type'] == 'khuyenmai' ? 'selected' : ''; ?>>Chương trình khuyến mãi</option>
                <option value="voucher" <?php echo $tintuc['type'] == 'voucher' ? 'selected' : ''; ?>>Voucher khuyến mãi</option>
                <option value="thongbao" <?php echo $tintuc['type'] == 'thongbao' ? 'selected' : ''; ?>>Thông báo</option>
            </select>
        </div>
        
        <div class="form-group" id="field_title">
            <label>Tiêu đề (hoặc Mã Voucher)</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($tintuc['title']); ?>" placeholder="VD: Mua 2 tặng 1 hoặc KM10">
        </div>

        <div class="form-group" id="field_content">
            <label>Nội dung</label>
            <textarea name="content" rows="3" class="form-control" placeholder="Chi tiết chương trình hoặc nội dung thông báo..."><?php echo htmlspecialchars($tintuc['content']); ?></textarea>
        </div>

        <div style="display: flex; gap: 15px;" id="field_voucher_details">
            <div class="form-group" style="flex: 1;">
                <label>Phần trăm giảm giá (%)</label>
                <input type="number" name="discount_percent" class="form-control" value="<?php echo htmlspecialchars($tintuc['discount_percent']); ?>" placeholder="VD: 10">
            </div>

            <div class="form-group" style="flex: 1;">
                <label>Số lượng Voucher</label>
                <input type="number" name="quantity" class="form-control" value="<?php echo htmlspecialchars($tintuc['quantity']); ?>" placeholder="VD: 100">
            </div>

            <div class="form-group" style="flex: 1;">
                <label>Ngày hết hạn</label>
                <?php 
                    $expiry_val = '';
                    if ($tintuc['expiry_date']) {
                        $expiry_val = date('Y-m-d\TH:i', strtotime($tintuc['expiry_date']));
                    }
                ?>
                <input type="datetime-local" name="expiry_date" class="form-control" value="<?php echo $expiry_val; ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Cập Nhật</button>
    </form>
</div>

<script>
function toggleFields() {
    var type = document.getElementById('type').value;
    var fieldTitle = document.getElementById('field_title');
    var fieldContent = document.getElementById('field_content');
    var fieldVoucherDetails = document.getElementById('field_voucher_details');
    
    fieldTitle.style.display = 'none';
    fieldContent.style.display = 'none';
    fieldVoucherDetails.style.display = 'none';
    
    if (type === 'khuyenmai') {
        fieldTitle.style.display = 'block';
        fieldContent.style.display = 'block';
    } else if (type === 'voucher') {
        fieldTitle.style.display = 'block';
        fieldVoucherDetails.style.display = 'flex';
    } else if (type === 'thongbao') {
        fieldContent.style.display = 'block';
    }
}

document.addEventListener('DOMContentLoaded', toggleFields);
</script>

<?php include 'includes/footer.php'; ?>
