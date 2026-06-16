<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $key = isset($_POST['key']) ? $_POST['key'] : '';

    if ($action === 'remove' && $key !== '') {
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
                'total_items' => count($_SESSION['cart'])
            ]);
            exit;
        }
    }
    
    // Dự phòng cho chức năng update số lượng sau này
    if ($action === 'update_qty' && $key !== '') {
        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
        if ($qty > 0 && isset($_SESSION['cart'][$key])) {
            
            // Lấy thông tin kho để kiểm tra
            require_once '../model/xl_data.php';
            $db = new xl_data();
            $pdo = $db->connection_database();
            $type_id = explode('_', $key);
            $type = $type_id[0];
            $id = intval($type_id[1]);
            
            $stock = 0;
            if ($type === 'sanpham') {
                $stmt = $pdo->prepare("SELECT so_luong FROM sanpham WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("SELECT so_luong FROM phukien WHERE id = ?");
            }
            $stmt->execute([$id]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                $stock = $res['so_luong'];
            }
            
            if ($qty > $stock) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Rất tiếc! Số lượng yêu cầu vượt quá số lượng tồn kho (' . $stock . ' sản phẩm).'
                ]);
                exit;
            }

            $_SESSION['cart'][$key]['quantity'] = $qty;
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật số lượng.',
                'total_items' => count($_SESSION['cart'])
            ]);
            exit;
        }
    }

    if ($action === 'apply_voucher') {
        $code = isset($_POST['code']) ? $_POST['code'] : '';
        $discount_percent = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
        
        $_SESSION['voucher'] = [
            'code' => $code,
            'discount_percent' => $discount_percent
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã áp dụng voucher.'
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
?>
