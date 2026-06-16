<?php
include 'includes/header.php';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['banner_image'])) {
    $title = $_POST['title'] ?? '';
    
    $targetDir = "../images/";
    // Ensure directory exists
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($_FILES["banner_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
    if (in_array($fileType, $allowTypes)) {
        if (move_uploaded_file($_FILES["banner_image"]["tmp_name"], $targetFilePath)) {
            // Insert into DB
            $dbPath = "images/" . $fileName; // Đường dẫn tương đối từ index.php
            $stmt = $pdo->prepare("INSERT INTO banners (image, title) VALUES (?, ?)");
            if ($stmt->execute([$dbPath, $title])) {
                $statusMsg = "Banner đã được tải lên thành công.";
            } else {
                $statusMsg = "Lỗi khi lưu vào cơ sở dữ liệu.";
            }
        } else {
            $statusMsg = "Xin lỗi, đã có lỗi xảy ra khi tải file của bạn lên.";
        }
    } else {
        $statusMsg = "Xin lỗi, chỉ cho phép các định dạng JPG, JPEG, PNG, GIF, WEBP.";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Lấy đường dẫn ảnh để xoá file vật lý
    $stmt = $pdo->prepare("SELECT image FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $imgPath = "../" . $row['image'];
        if (file_exists($imgPath) && !empty($row['image'])) {
            unlink($imgPath);
        }
        $delStmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        $delStmt->execute([$id]);
        $statusMsg = "Đã xóa banner thành công.";
    }
}

// Fetch banners
$banners = $pdo->query("SELECT * FROM banners ORDER BY FIELD(id, 4, 2, 3, 1) DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Quản Lý Banner</h2>
</div>

<?php if(!empty($statusMsg)): ?>
    <div style="padding: 15px; background: #d1fae5; color: #065f46; border-left: 4px solid #1b8a44; margin-bottom: 20px; border-radius: 4px;">
        <?php echo $statusMsg; ?>
    </div>
<?php endif; ?>

<!-- Form Thêm Banner -->
<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px; font-size: 16px;">Tải Banner Mới</h3>
    <form action="" method="post" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Tiêu đề (tuỳ chọn - dùng cho thuộc tính alt của ảnh):</label>
            <input type="text" name="title" placeholder="VD: Khuyến mãi mùa hè" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Chọn ảnh Banner:</label>
            <input type="file" name="banner_image" accept="image/*" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px;">
        </div>
        <button type="submit" class="btn btn-primary">Tải Ảnh Lên</button>
    </form>
</div>

<!-- Danh sách Banner -->
<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Hình ảnh</th>
                <th>Tiêu đề</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($banners) > 0): ?>
                <?php foreach($banners as $row): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <img src="../<?php echo htmlspecialchars($row['image']); ?>" style="max-width: 200px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);" alt="Banner">
                    </td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-delete-confirm" style="background:#ef4444; color:#fff;" data-confirm-msg="Bạn có chắc chắn muốn xóa banner này?">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center;">Chưa có banner nào.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
