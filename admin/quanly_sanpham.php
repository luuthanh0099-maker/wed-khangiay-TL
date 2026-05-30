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
            $stmt = $conn->prepare("INSERT INTO $table ($col_name, gia, so_luong, hinhanh, mo_ta) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdiss", $ten, $gia, $so_luong, $hinhanh, $mo_ta);
            $stmt->execute();
        } else {
            $id = intval($_POST['id']);
            $col_name = $type == 'sanpham' ? 'ten_sanpham' : 'ten_phukien';
            $stmt = $conn->prepare("UPDATE $table SET $col_name=?, gia=?, so_luong=?, hinhanh=?, mo_ta=? WHERE id=?");
            $stmt->bind_param("sdissi", $ten, $gia, $so_luong, $hinhanh, $mo_ta, $id);
            $stmt->execute();
        }
        echo "<script>alert('Lưu thành công!'); window.location.href='quanly_sanpham.php?type=$type';</script>";
        exit();
    }
    
    if ($action == 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM $table WHERE id = $id");
        echo "<script>alert('Đã xóa thành công!'); window.location.href='quanly_sanpham.php?type=$type';</script>";
        exit();
    }
}

// Lấy danh sách
$items = $conn->query("SELECT * FROM $table ORDER BY id DESC");
?>

<style>
/* Style riêng cho Tabs */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.tab {
    padding: 10px 20px;
    background: #e5e7eb;
    color: #4b5563;
    border-radius: 6px 6px 0 0;
    text-decoration: none;
    font-weight: 600;
}
.tab.active {
    background: #1b8a44;
    color: #fff;
}
/* Style cho Form Modal (Đơn giản hóa) */
.form-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    display: none; /* Ẩn đi mặc định */
}
.form-container.active {
    display: block;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="color: #1f2937;">Quản Lý Sản Phẩm</h2>
    <button class="btn btn-primary" onclick="showForm('add')"><i class="fas fa-plus"></i> Thêm mới</button>
</div>

<!-- Tabs -->
<div class="tabs">
    <a href="quanly_sanpham.php?type=sanpham" class="tab <?php echo $type=='sanpham'?'active':''; ?>">Khăn Giấy</a>
    <a href="quanly_sanpham.php?type=phukien" class="tab <?php echo $type=='phukien'?'active':''; ?>">Phụ Kiện</a>
</div>

<!-- Form Thêm/Sửa -->
<div class="form-container" id="productForm">
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
            <button type="button" class="btn btn-secondary" onclick="hideForm()">Hủy</button>
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
            <?php if($items->num_rows > 0): ?>
                <?php while($row = $items->fetch_assoc()): 
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
                        <button class="btn btn-secondary" onclick="editProduct(<?php echo htmlspecialchars(json_encode([
                            'id' => $row['id'],
                            'ten' => $name,
                            'gia' => $row['gia'],
                            'so_luong' => $row['so_luong'],
                            'mo_ta' => $row['mo_ta'],
                            'hinhanh' => $row['hinhanh']
                        ])); ?>)">Sửa</button>
                        
                        <form method="POST" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa không?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn" style="background:#ef4444; color:#fff;">Xóa</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">Chưa có dữ liệu</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function showForm(action) {
    document.getElementById('productForm').classList.add('active');
    document.getElementById('formAction').value = action;
    if(action === 'add') {
        document.getElementById('formTitle').innerText = 'Thêm mới';
        document.getElementById('formId').value = '';
        document.getElementById('formTen').value = '';
        document.getElementById('formGia').value = '';
        document.getElementById('formSoLuong').value = '0';
        document.getElementById('formMoTa').value = '';
        document.getElementById('formOldImage').value = '';
        document.getElementById('imagePreview').innerHTML = '';
    }
    // Cuộn lên trên form
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function hideForm() {
    document.getElementById('productForm').classList.remove('active');
}

function editProduct(data) {
    showForm('edit');
    document.getElementById('formTitle').innerText = 'Sửa ' + data.ten;
    document.getElementById('formId').value = data.id;
    document.getElementById('formTen').value = data.ten;
    document.getElementById('formGia').value = data.gia;
    document.getElementById('formSoLuong').value = data.so_luong;
    document.getElementById('formMoTa').value = data.mo_ta || '';
    document.getElementById('formOldImage').value = data.hinhanh || '';
    
    if(data.hinhanh) {
        document.getElementById('imagePreview').innerHTML = '<img src="../' + data.hinhanh + '" style="height:50px; border-radius:4px;"> <span style="font-size:12px;color:#666;">(Ảnh hiện tại)</span>';
    } else {
        document.getElementById('imagePreview').innerHTML = '';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
