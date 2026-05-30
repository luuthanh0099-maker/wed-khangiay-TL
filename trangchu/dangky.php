<?php
session_start();
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "Vui lòng điền đầy đủ thông tin.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ. Vui lòng nhập đúng 10 chữ số từ 0-9.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $error = "Email này đã được sử dụng. Vui lòng dùng email khác hoặc Đăng nhập.";
        } else {
            // Đăng ký (Lưu mật khẩu trực tiếp dạng văn bản)
            $stmt_insert = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt_insert->bind_param("ssss", $fullname, $email, $phone, $password);
            
            if ($stmt_insert->execute()) {
                $_SESSION['flash_success'] = "Đăng ký tài khoản thành công! Hệ thống đã tự điền thông tin, bạn chỉ cần bấm Đăng nhập.";
                $_SESSION['flash_email'] = $email;
                $_SESSION['flash_password'] = $password;
                header("Location: dangnhap.php");
                exit();
            } else {
                $error = "Đã xảy ra lỗi hệ thống, vui lòng thử lại sau.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - TL</title>
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
                <h2 class="auth-title">Đăng ký tài khoản</h2>

                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="dangky.php">
                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" name="fullname" class="form-control" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control" required 
                               maxlength="10" 
                               minlength="10"
                               pattern="[0-9]{10}" 
                               title="Vui lòng nhập đúng 10 chữ số từ 0-9" 
                               oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Xác nhận mật khẩu</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn-auth">Đăng ký</button>
                </form>

                <div class="auth-links">
                    Đã có tài khoản? <a href="dangnhap.php">Đăng nhập</a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'chantrang.php'; ?>
</body>
</html>
