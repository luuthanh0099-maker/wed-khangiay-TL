<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$pdo = $db->connection_database();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: dangnhap.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy order_id từ URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Nếu không có id trên URL, có thể kiểm tra xem có POST không
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        header("Location: giohang.php");
        exit();
    }
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['order_id']) ? intval($_POST['order_id']) : 0);

// Xác nhận thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'confirm_payment') {
    // Đổi trạng thái đơn hàng thành confirmed
    $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ? AND user_id = ? AND status = 'pending'");
    
    if ($stmt->execute([$order_id, $user_id])) {
        echo "<script>alert('Xác nhận thanh toán thành công! Chúng tôi sẽ kiểm tra và giao hàng sớm nhất.'); window.location.href='lichsu_muahang.php';</script>";
        exit();
    } else {
        $error = "Có lỗi xảy ra, vui lòng thử lại!";
    }
}

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<script>alert('Không tìm thấy đơn hàng hoặc đơn hàng không hợp lệ!'); window.location.href='index.php';</script>";
    exit();
}

// Nếu đơn hàng không còn ở trạng thái pending thì báo lỗi
if ($order['status'] != 'pending') {
    echo "<script>alert('Đơn hàng này đã được thanh toán hoặc đã hủy.'); window.location.href='lichsu_muahang.php';</script>";
    exit();
}

// Thông tin VietQR
$bank_id = "sacombank";
$account_no = "060292388211";
$account_name = "LUU TUAN THANH";
$amount = intval($order['total']);
$add_info = "THANH TOAN DON HANG " . $order_id;
$add_info_url = rawurlencode($add_info);
$account_name_url = rawurlencode($account_name);

$qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-compact2.png?amount={$amount}&addInfo={$add_info_url}&accountName={$account_name_url}";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán Online - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=11">
</head>
<body>
    <header class="header">
        <div class="container header-container">
            <a href="index.php" class="logo">
                <img src="../images/logo.jpg" alt="TL" class="logo-img">
                <span class="logo-text">TL</span>
            </a>
            <div style="font-size: 18px; font-weight: bold; color: #1b8a44; margin-left: auto;">CỔNG THANH TOÁN</div>
        </div>
    </header>

    <div class="payment-container">
        <div class="payment-info">
            <h2><i class="fa-solid fa-building-columns"></i> Thông tin chuyển khoản</h2>
            
            <div class="info-row">
                <span class="label">Ngân hàng:</span>
                <span class="value">SACOMBANK</span>
            </div>
            <div class="info-row">
                <span class="label">Số tài khoản:</span>
                <span class="value" style="font-size: 18px; letter-spacing: 1px; color: #004b87;">060292388211</span>
            </div>
            <div class="info-row">
                <span class="label">Người nhận:</span>
                <span class="value">LUU TUAN THANH</span>
            </div>
            <div class="info-row">
                <span class="label">Tổng thanh toán:</span>
                <span class="value amount"><?php echo number_format($amount, 0, ',', '.'); ?> đ</span>
            </div>
            <div class="info-row">
                <span class="label">Nội dung / Diễn giải:</span>
                <span class="value" style="color: #00796b;"><?php echo htmlspecialchars($add_info); ?></span>
            </div>

            <?php if(isset($error)): ?>
                <div style="color: red; margin-top: 10px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="confirm_payment">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <button type="submit" class="btn-confirm"><i class="fa-solid fa-check-circle"></i> Tôi đã chuyển khoản thành công</button>
            </form>
            <a href="lichsu_muahang.php" class="btn-cancel">Thanh toán sau</a>
        </div>
        
        <div class="payment-qr">
            <img src="<?php echo $qr_url; ?>" alt="VietQR" class="qr-img">
            <p class="qr-instruction">
                <i class="fa-solid fa-mobile-screen"></i><br>
                Mở <strong>App Ngân hàng</strong> bất kỳ và quét mã QR để thanh toán nhanh. 
            </p>
        </div>
    </div>
    <?php include 'chantrang.php'; ?>
</body>
</html>
