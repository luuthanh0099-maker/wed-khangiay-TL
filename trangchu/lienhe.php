<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - TL</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=3">
    <style>
        .contact-page-container {
            max-width: 600px;
            margin: 60px auto;
            text-align: center;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        .contact-trigger {
            font-size: 28px;
            font-weight: 700;
            color: #1b8a44;
            cursor: pointer;
            user-select: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin: 0;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        
        .contact-trigger:hover {
            background-color: #f0fdf4;
        }

        .contact-menu {
            margin-top: 30px;
            display: none; /* Ẩn đi mặc định */
            animation: fadeInDown 0.3s ease forwards;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .contact-icons {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 30px;
        }

        .contact-icons img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid #fff;
        }

        .contact-icons img:hover {
            transform: scale(1.15);
            box-shadow: 0 5px 15px rgba(27, 138, 68, 0.3);
            border-color: #1b8a44;
        }

        .display-frame {
            border: 2px dashed #1b8a44;
            background-color: #f8fafc;
            padding: 30px 20px;
            border-radius: 12px;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #4b5563;
        }

        .display-frame img {
            max-width: 200px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .phone-text {
            color: #1b8a44;
            font-size: 26px;
            font-weight: bold;
            text-decoration: none;
            margin-top: 5px;
            display: block;
        }
    </style>
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
                    <li><a href="lienhe.php" class="active">Liên hệ</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                                <div class="action-icon search-icon" id="search-icon-container">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <!-- Khung Tìm Kiếm Dropdown -->
                    <div class="search-dropdown" id="search-dropdown">
                        <input type="text" id="search-input" placeholder="Tìm kiếm sản phẩm">
                        <div class="search-results" id="search-results">
                            <!-- Kết quả AJAX sẽ hiện ở đây -->
                        </div>
                    </div>
                </div>
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
        <div class="contact-page-container">
            <!-- Chữ Liên hệ để Click -->
            <h1 class="contact-trigger" id="contact-trigger">
                Liên hệ <i class="fas fa-chevron-down" id="trigger-icon"></i>
            </h1>
            
            <!-- Menu nhỏ xổ ra -->
            <div class="contact-menu" id="contact-menu">
                <div class="contact-icons">
                    <!-- Nút 1: Điện thoại -->
                    <img src="../images/dt.jpg" alt="Điện thoại" id="btn-dt" title="Gọi điện thoại">
                    
                    <!-- Nút 2: Zalo -->
                    <img src="../images/zalo.jpg" alt="Zalo" id="btn-zalo" title="Quét mã Zalo">
                    
                    <!-- Nút 3: Gmail -->
                    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=luuthanh0099@gmail.com" target="_blank" title="Gửi Email qua Gmail">
                        <img src="../images/gmail.png" alt="Gmail">
                    </a>
                </div> 
                <!-- Khung hiển thị -->
                <div class="display-frame" id="display-frame">
                    Vui lòng chọn một phương thức liên hệ ở trên.
                </div>
            </div>
        </div>
    </main>

    <script>
        // Xử lý Click vào chữ "Liên hệ"
        document.getElementById('contact-trigger').addEventListener('click', function() {
            const menu = document.getElementById('contact-menu');
            const icon = document.getElementById('trigger-icon');
            
            if (menu.style.display === 'none' || menu.style.display === '') {
                menu.style.display = 'block';
                icon.className = 'fas fa-chevron-up';
            } else {
                menu.style.display = 'none';
                icon.className = 'fas fa-chevron-down';
            }
        });

        // Nút 1: Hiển thị Số điện thoại
        document.getElementById('btn-dt').addEventListener('click', function() {
            const display = document.getElementById('display-frame');
            display.innerHTML = 'Số điện thoại :<br><a href="tel:0975720687" class="phone-text">0975720687</a>';
        });

        // Nút 2: Hiển thị QR Zalo
        document.getElementById('btn-zalo').addEventListener('click', function() {
            const display = document.getElementById('display-frame');
            display.innerHTML = '<img src="../images/qrzalo.jpg" alt="QR Zalo"><br><strong>Hãy quét mã để liên hệ</strong>';
        });
    </script>
    <?php include 'chantrang.php'; ?>
</body>
</html>
