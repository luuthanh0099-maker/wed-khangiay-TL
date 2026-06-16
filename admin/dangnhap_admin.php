<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$pdo = $db->connection_database();

// Nếu đã đăng nhập admin thì cho vào trang chủ admin
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ Email và Mật khẩu.";
    } else {
        $stmt = $pdo->prepare("SELECT id, email FROM admin WHERE email = ? AND password = ?");
        $stmt->execute([$email, $password]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Email hoặc Mật khẩu không chính xác!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị - TL Tissue</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dangnhap.css">
</head>
<body>
    <div class="login-box">
        <img src="../images/logo.jpg" alt="TL" onerror="this.src='../images/logo.png'">
        <div class="login-title">Hệ Thống Quản Trị</div>
        
        <?php if($error): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email Quản Lý</label>
                <input type="email" name="email" class="form-control" placeholder="admin@gmail.com" required>
            </div>
            <div class="form-group">
                <label>Mật Khẩu</label>
                <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
            </div>
            <button type="submit" class="btn-login">Đăng Nhập</button>
        </form>
        
        <a href="../trangchu/index.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại trang chủ bán hàng</a>
    </div>
</body>
</html>
