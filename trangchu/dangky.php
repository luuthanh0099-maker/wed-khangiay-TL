<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$pdo = $db->connection_database();

$error = '';
$success = '';

// Hàm kiểm tra email có tồn tại trên Mail Server hay không (SMTP Ping)
function verifyEmailExists($email) {
    $domain = substr(strrchr($email, "@"), 1);
    $mxhosts = array();
    // Lấy bản ghi MX của tên miền
    if(!@getmxrr($domain, $mxhosts) || empty($mxhosts)) {
        return false;
    }
    
    // Kết nối tới Mail Server đầu tiên
    $mx = $mxhosts[0];
    $connect = @fsockopen($mx, 25, $errno, $errstr, 5); // Timeout 5 giây
    if(!$connect) return false;
    
    stream_set_timeout($connect, 5);
    $out = fgets($connect, 1024); // Đọc dòng chào mừng 220
    
    // Gửi lệnh HELO
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    fputs($connect, "HELO $host\r\n");
    $out = fgets($connect, 1024);
    
    // Gửi lệnh MAIL FROM
    fputs($connect, "MAIL FROM: <noreply@$host>\r\n");
    $out = fgets($connect, 1024);
    
    // Gửi lệnh RCPT TO để kiểm tra email
    fputs($connect, "RCPT TO: <$email>\r\n");
    $out = fgets($connect, 1024);
    
    // Đóng kết nối
    fputs($connect, "QUIT\r\n");
    fclose($connect);
    
    // Nếu Mail Server trả về 250 (OK), nghĩa là email tồn tại
    if(strpos($out, "250") === 0) {
        return true;
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Lấy thông tin địa chỉ
    $province_name = trim($_POST['province_name'] ?? '');
    $ward_name = trim($_POST['ward_name'] ?? '');
    $house = trim($_POST['house'] ?? '');
    $full_address = '';
    if (!empty($province_name) && !empty($ward_name) && !empty($house)) {
        $full_address = "$house, $ward_name, $province_name";
    }

    if (empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($full_address)) {
        $error = "Vui lòng điền đầy đủ thông tin, bao gồm cả địa chỉ.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Số điện thoại không hợp lệ. Vui lòng nhập đúng 10 chữ số từ 0-9.";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // Kiểm tra email tồn tại thật hay không qua SMTP
        if (!verifyEmailExists($email)) {
            $error = "Địa chỉ email không tồn tại hoặc không thể nhận thư. Vui lòng nhập email hợp lệ (VD: email thật của bạn).";
        } else {
            // Kiểm tra email đã tồn tại chưa trong CSDL
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check->execute([$email]);
            $res_check = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($res_check) {
                $error = "Email này đã được sử dụng. Vui lòng dùng email khác hoặc Đăng nhập.";
            } else {
                // Đăng ký (Lưu mật khẩu trực tiếp dạng văn bản)
                $stmt_insert = $pdo->prepare("INSERT INTO users (name, email, phone, password, address) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt_insert->execute([$fullname, $email, $phone, $password, $full_address])) {
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
    <link rel="stylesheet" href="css/style.css?v=11">
</head>
<body class="bg-light">

    <!-- Phần Đầu Trang -->
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
                        <label>Tỉnh/Thành phố</label>
                        <select name="province" id="province" class="form-control" required>
                            <option value="">Chọn Tỉnh/Thành phố</option>
                        </select>
                        <input type="hidden" name="province_name" id="province_name">
                    </div>

                    <div class="form-group">
                        <label>Phường/Xã</label>
                        <select name="ward" id="ward" class="form-control" required disabled>
                            <option value="">Chọn Phường/Xã</option>
                        </select>
                        <input type="hidden" name="ward_name" id="ward_name">
                    </div>

                    <div class="form-group">
                        <label>Số nhà/Tên đường</label>
                        <input type="text" name="house" id="house" class="form-control" required placeholder="VD: Số 123 Đường Nguyễn Trãi" value="<?php echo isset($_POST['house']) ? htmlspecialchars($_POST['house']) : ''; ?>">
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province');
            const wardSelect = document.getElementById('ward');
            const provinceName = document.getElementById('province_name');
            const wardName = document.getElementById('ward_name');

            // Load provinces
            fetch('https://provinces.open-api.vn/api/p/')
                .then(res => res.json())
                .then(data => {
                    data.forEach(province => {
                        const option = document.createElement('option');
                        option.value = province.code;
                        option.textContent = province.name;
                        provinceSelect.appendChild(option);
                    });
                })
                .catch(err => console.error('Error loading provinces:', err));

            // On province change
            provinceSelect.addEventListener('change', function() {
                const provinceCode = this.value;
                if(this.selectedIndex > 0) {
                    provinceName.value = this.options[this.selectedIndex].text;
                } else {
                    provinceName.value = '';
                }
                
                // Reset ward select
                wardSelect.innerHTML = '<option value="">Đang tải...</option>';
                wardSelect.disabled = true;
                wardName.value = '';

                if (provinceCode) {
                    // Fetch province with depth=3 to get all wards
                    fetch(`https://provinces.open-api.vn/api/p/${provinceCode}?depth=3`)
                        .then(res => res.json())
                        .then(data => {
                            wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
                            if (data.districts) {
                                // Gộp tất cả wards từ tất cả các districts và nối thêm tên Quận/Huyện để tránh trùng lặp
                                const allWards = data.districts.flatMap(d => 
                                    d.wards.map(w => ({
                                        code: w.code,
                                        name: w.name + ' (' + d.name + ')'
                                    }))
                                );
                                
                                // Sort theo tên
                                allWards.sort((a, b) => a.name.localeCompare(b.name, 'vi'));

                                allWards.forEach(ward => {
                                    const option = document.createElement('option');
                                    option.value = ward.code;
                                    option.textContent = ward.name;
                                    wardSelect.appendChild(option);
                                });
                            }
                            wardSelect.disabled = false;
                        })
                        .catch(err => {
                            console.error('Error loading wards:', err);
                            wardSelect.innerHTML = '<option value="">Lỗi tải dữ liệu</option>';
                        });
                } else {
                    wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
                    wardSelect.disabled = true;
                }
            });

            // On ward change
            wardSelect.addEventListener('change', function() {
                if(this.selectedIndex > 0) {
                    wardName.value = this.options[this.selectedIndex].text;
                } else {
                    wardName.value = '';
                }
            });
        });
    </script>
</body>
</html>
