<?php
include 'includes/header.php';

// Xác định đang xem Khăn giấy hay Phụ kiện
$type = isset($_GET['type']) && $_GET['type'] == 'phukien' ? 'phukien' : 'sanpham';
$table = $type == 'sanpham' ? 'sanpham' : 'phukien';
$title_type = $type == 'sanpham' ? 'Khăn Giấy' : 'Phụ Kiện';

// Khởi tạo thư mục ảnh nếu chưa có
$upload_dir = '../images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// XỬ LÝ POST: Thêm / Sửa / Xóa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'add' || $action == 'edit') {
        $ten = trim($_POST['ten']);
        $gia = floatval($_POST['gia']);
        $so_luong = intval($_POST['so_luong']);
        $mo_ta = trim($_POST['mo_ta']);
        
        // Xử lý upload ảnh
        $hinhanh = $_POST['old_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $filename = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $hinhanh = 'images/' . $filename; // Đường dẫn lưu DB
            }
        }
        
        if ($action == 'add') {
            $col_name = $type == 'sanpham' ? 'ten_sanpham' : 'ten_phukien';
            $stmt = $pdo->prepare("INSERT INTO $table ($col_name, gia, so_luong, hinhanh, mo_ta) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ten, $gia, $so_luong, $hinhanh, $mo_ta]);
        } else {
            $id = intval($_POST['id']);
            $col_name = $type == 'sanpham' ? 'ten_sanpham' : 'ten_phukien';
            $stmt = $pdo->prepare("UPDATE $table SET $col_name=?, gia=?, so_luong=?, hinhanh=?, mo_ta=? WHERE id=?");
            $stmt->execute([$ten, $gia, $so_luong, $hinhanh, $mo_ta, $id]);
        }
        echo "<script>alert('Lưu thành công!'); window.location.href='quanly_sanpham.php?type=$type';</script>";
        exit();
    }
    
    if ($action == 'delete') {
        $id = intval($_POST['id']);

        // Kiểm tra xem sản phẩm đã có trong đơn hàng chưa
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ? AND product_type = ?");
        $stmt_check->execute([$id, $type]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            echo "<script>alert('Không thể xóa do đã có khách hàng đặt mua. Bạn có thể chỉnh sửa lại số lượng kho thành 0 để khách không đặt được nữa!'); window.location.href='quanly_sanpham.php?type=$type';</script>";
            exit();
        }

        $pdo->exec("DELETE FROM $table WHERE id = $id");
        echo "<script>alert('Đã xóa thành công!'); window.location.href='quanly_sanpham.php?type=$type';</script>";
        exit();
    }
}

// Lấy danh sách
$items = $pdo->query("SELECT * FROM $table ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>



<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="color: #1f2937;">Quản Lý Sản Phẩm</h2>
    <button class="btn btn-primary" id="btnAddProduct"><i class="fas fa-plus"></i> Thêm mới</button>
</div>

<!-- Tabs -->
<div class="tabs">
    <a href="quanly_sanpham.php?type=sanpham" class="tab <?php echo $type=='sanpham'?'active':''; ?>">Khăn Giấy</a>
    <a href="quanly_sanpham.php?type=phukien" class="tab <?php echo $type=='phukien'?'active':''; ?>">Phụ Kiện</a>
</div>

<!-- Form Thêm/Sửa -->
<div class="modal-form-container" id="productForm">
    <h3 id="formTitle" style="margin-bottom: 15px;">Thêm <?php echo $title_type; ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId" value="">
        <input type="hidden" name="old_image" id="formOldImage" value="">
        
        <div class="form-group">
            <label>Tên <?php echo $title_type; ?></label>
            <input type="text" name="ten" id="formTen" class="form-control" required>
        </div>
        <div style="display: flex; gap: 15px;">
            <div class="form-group" style="flex: 1;">
                <label>Giá (VNĐ)</label>
                <input type="number" name="gia" id="formGia" class="form-control" required>
            </div>
            <div class="form-group" style="flex: 1;">
                <label>Số lượng kho</label>
                <input type="number" name="so_luong" id="formSoLuong" class="form-control" value="0" required>
            </div>
        </div>
        <div class="form-group">
            <label>Hình ảnh (Để trống nếu không muốn đổi)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <div id="imagePreview" style="margin-top: 10px;"></div>
        </div>
        <div class="form-group">
            <label>Mô tả</label>
            <textarea name="mo_ta" id="formMoTa" class="form-control" rows="3"></textarea>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Lưu lại</button>
            <button type="button" class="btn btn-secondary" id="btnCancelProduct">Hủy</button>
        </div>
    </form>
</div>

<!-- Bảng Danh Sách -->
<div class="table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Hình Ảnh</th>
                <th>Tên <?php echo $title_type; ?></th>
                <th>Giá</th>
                <th>Kho</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($items) > 0): ?>
                <?php foreach($items as $row): 
                    $name = $type == 'sanpham' ? $row['ten_sanpham'] : $row['ten_phukien'];
                ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <img src="../<?php echo htmlspecialchars($row['hinhanh']); ?>" alt="" style="width:50px; height:50px; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                    </td>
                    <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                    <td style="color:#ef4444; font-weight:600;"><?php echo number_format($row['gia'], 0, ',', '.'); ?>đ</td>
                    <td>
                        <?php if($row['so_luong'] <= 20): ?>
                            <span style="color:#ef4444; font-weight:bold;"><?php echo $row['so_luong']; ?> (Yêu cầu nhập thêm)</span>
                        <?php else: ?>
                            <span style="color:#1b8a44; font-weight:bold;"><?php echo $row['so_luong']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-secondary btn-edit-product" data-product="<?php echo htmlspecialchars(json_encode([
                            'id' => $row['id'],
                            'ten' => $name,
                            'gia' => $row['gia'],
                            'so_luong' => $row['so_luong'],
                            'mo_ta' => $row['mo_ta'],
                            'hinhanh' => $row['hinhanh']
                        ])); ?>">Sửa</button>
                        
                        <form method="POST" style="display:inline-block;" class="form-delete-confirm" data-confirm-msg="Bạn có chắc chắn muốn xóa không?">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn" style="background:#ef4444; color:#fff;">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">Chưa có dữ liệu</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="js/quanly_sanpham.js"></script>

<?php include 'includes/footer.php'; ?>
