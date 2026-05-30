<?php
session_start();
require_once '../config/db.php';
$conn->set_charset("utf8mb4");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $type = isset($_POST['type']) ? $_POST['type'] : '';

    if ($id <= 0 || !in_array($type, ['sanpham', 'phukien'])) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }

    // Lấy thông tin sản phẩm từ CSDL
    if ($type === 'sanpham') {
        $stmt = $conn->prepare("SELECT ten_sanpham as name, gia as price, hinhanh as img, so_luong FROM sanpham WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT ten_phukien as name, gia as price, hinhanh as img, so_luong FROM phukien WHERE id = ?");
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        
        // Khởi tạo giỏ hàng nếu chưa có
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $cart_key = $type . '_' . $id;
        $current_qty_in_cart = isset($_SESSION['cart'][$cart_key]) ? $_SESSION['cart'][$cart_key]['quantity'] : 0;
        
        if ($current_qty_in_cart + 1 > $item['so_luong']) {
            echo json_encode(['success' => false, 'message' => 'Rất tiếc! Số lượng sản phẩm trong kho không đủ.']);
            exit;
        }

        // Nếu sản phẩm đã có trong giỏ, tăng số lượng
        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['quantity'] += 1;
        } else {
            // Thêm mới vào giỏ
            $_SESSION['cart'][$cart_key] = [
                'id' => $id,
                'type' => $type,
                'name' => $item['name'],
                'price' => $item['price'],
                'img' => $item['img'],
                'quantity' => 1
            ];
        }

        // Đếm tổng số loại sản phẩm trong giỏ (hoặc tổng số lượng tùy logic, ở đây đếm số loại sản phẩm)
        $total_items = count($_SESSION['cart']);

        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng thành công!',
            'total_items' => $total_items
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
}
?>
