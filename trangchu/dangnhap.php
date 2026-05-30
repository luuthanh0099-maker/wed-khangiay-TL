<?php
session_start();
require_once '../config/db.php';

// Nếu người dùng đã đăng nhập, chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success_msg = '';
$default_email = '';
$default_password = '';

// Nếu có dữ liệu từ trang đăng ký chuyển qua
if (isset($_SESSION['flash_email'])) {
    $default_email = $_SESSION['flash_email'];
    $default_password = $_SESSION['flash_password'];
    $success_msg = $_SESSION['flash_success'];
    
    // Xóa session flash để không hiển thị lại khi F5
    unset($_SESSION['flash_email']);
    unset($_SESSION['flash_password']);
    unset($_SESSION['flash_success']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Vui lòng nhập Email và Mật khẩu.";
    } else {
        // Tìm user trong Database
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Kiểm tra mật khẩu trực tiếp (Plain text)
            if ($password === $user['password']) {
                // Đăng nhập thành công -> Lưu Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header("Location: index.php");
                exit();
            } else {
                $error = "Mật khẩu không chính xác.";
            }
        } else {
            $error = "Tài khoản không tồn tại. Vui lòng đăng ký.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
</head>
<body class="bg-light">

    <!-- Header Section -->
    <header class="header">
        <div class="container header-container">
            <a href="index.php" class="logo">
                <img src="../images/logo.jpg" alt="TL" class="logo-img">
                <span class="logo-text">TL</span>
            </a>
            <nav class="navbar">
                <ul class="nav-links">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="danhsach_sanpham.php">Sản phẩm</a></li>
                    <li><a href="danhsach_phukien.php">Phụ kiện</a></li>
                    <li><a href="tintuc.php">Tin tức</a></li>
                    <li><a href="lienhe.php">Liên hệ</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <a href="giohang.php" class="action-icon cart-icon">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span class="cart-badge"><?php echo isset($_SESSION["cart"]) ? count($_SESSION["cart"]) : 0; ?></span>
                </a>
                                <div class="auth-buttons">
                    <?php if (isset($_SESSION['user_name'])): ?>
                        <div class="user-dropdown-container">
                            <span class="user-greeting">
                                Xin chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?> <i class="fas fa-chevron-down"></i>
                            </span>
                            <div class="user-dropdown-menu">
                                <a href="thongtin_canhan.php"><i class="fas fa-user"></i> Thông tin cá nhân</a>
                                <a href="theodoi_donhang.php"><i class="fas fa-truck-fast"></i> Xem quá trình giao hàng</a>
                                <a href="lichsu_muahang.php"><i class="fas fa-box"></i> Lịch sử mua hàng</a>
                                <a href="dangxuat.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="dangnhap.php" class="btn btn-outline">Đăng nhập</a>
                        <a href="dangky.php" class="btn btn-primary">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="auth-page-wrapper">
            <div class="auth-box">
                <h2 class="auth-title">Đăng nhập</h2>

                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if($success_msg): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>

                <form method="POST" action="dangnhap.php">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $default_email); ?>">
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required value="<?php echo htmlspecialchars($default_password); ?>">
                    </div>

                    <button type="submit" class="btn-auth">Đăng nhập</button>
                </form>

                <div class="auth-links">
                    Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'chantrang.php'; ?>
</body>
</html>
