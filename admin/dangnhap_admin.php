<?php
session_start();
require_once '../config/db.php';

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
        $stmt = $conn->prepare("SELECT id, email FROM admin WHERE email = ? AND password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows == 1) {
            $admin = $res->fetch_assoc();
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
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #111827; /* Nền xám đậm chuyên nghiệp */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #fff;
        }
        .login-box {
            background-color: #1f2937;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-box img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 20px;
            background: #fff;
            padding: 5px;
        }
        .login-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #1b8a44;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #9ca3af;
        }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #374151;
            background-color: #374151;
            color: #fff;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #1b8a44;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #1b8a44;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            background-color: #146c35;
        }
        .alert {
            padding: 12px;
            background-color: #ef4444;
            color: #fff;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            transition: 0.3s;
        }
        .back-link:hover {
            color: #1b8a44;
        }
    </style>
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
