<?php
include 'includes/header.php';

$message = '';
$error = '';

// Xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sodienthoai = trim($_POST['sodienthoai']);
    $email = trim($_POST['email']);
    $diachi = trim($_POST['diachi']);
    $map_link = trim($_POST['map_link']);

    // Xử lý upload ảnh bản đồ
    if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['map_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $target_file = '../images/map.jpg';
            move_uploaded_file($_FILES['map_image']['tmp_name'], $target_file);
        }
    }

    $sql = "UPDATE cauhinh_lienhe SET sodienthoai = ?, email = ?, diachi = ?, map_link = ? WHERE id = 1";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$sodienthoai, $email, $diachi, $map_link]);
        $message = "Cập nhật thông tin liên hệ thành công!";
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy dữ liệu hiện tại
$contact = $pdo->query("SELECT * FROM cauhinh_lienhe WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>


<div class="page-header">
    <h2>Quản lý Thông tin Liên hệ</h2>
</div>

<div class="form-container">
    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="sodienthoai">Số điện thoại</label>
            <input type="text" id="sodienthoai" name="sodienthoai" value="<?php echo isset($contact['sodienthoai']) ? htmlspecialchars($contact['sodienthoai']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email liên hệ</label>
            <input type="email" id="email" name="email" value="<?php echo isset($contact['email']) ? htmlspecialchars($contact['email']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="diachi">Địa chỉ cửa hàng</label>
            <input type="text" id="diachi" name="diachi" value="<?php echo isset($contact['diachi']) ? htmlspecialchars($contact['diachi']) : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="map_link">Đường link Google Maps</label>
            <textarea id="map_link" name="map_link" required><?php echo isset($contact['map_link']) ? htmlspecialchars($contact['map_link']) : ''; ?></textarea>
            <small style="color: #6b7280; margin-top: 5px; display: block;">Link này sẽ được mở khi khách hàng bấm vào banner/icon bản đồ.</small>
        </div>
        
        <div class="form-group">
            <label>Ảnh bản đồ hiện tại</label>
            <div style="margin-bottom: 10px;">
                <img src="../images/map.jpg?v=<?php echo time(); ?>" alt="Map" style="max-width: 300px; border-radius: 8px; border: 1px solid #ddd;">
            </div>
            <label for="map_image">Thay đổi ảnh bản đồ (tùy chọn)</label>
            <input type="file" id="map_image" name="map_image" accept="image/*" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%; box-sizing: border-box;">
            <small style="color: #6b7280; margin-top: 5px; display: block;">Chọn ảnh mới để thay thế ảnh bản đồ hiện tại.</small>
        </div>
        
        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Lưu thông tin</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
