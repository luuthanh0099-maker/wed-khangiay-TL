<?php
include 'includes/header.php';

// Các trạng thái đơn hàng hợp lệ (không tính đơn hủy hay chờ xử lý - có thể linh hoạt điều chỉnh)
$valid_status = "('confirmed', 'shipping', 'completed', 'delivered')";

// 1. Doanh thu tuần này (Từ thứ 2 đến Chủ nhật)
$rev_week_res = $pdo->query("SELECT SUM(total) as revenue FROM orders WHERE status IN $valid_status AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$rev_week = $rev_week_res->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

// 2. Doanh thu tháng này
$rev_month_res = $pdo->query("SELECT SUM(total) as revenue FROM orders WHERE status IN $valid_status AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$rev_month = $rev_month_res->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

// 3. Doanh thu năm nay
$rev_year_res = $pdo->query("SELECT SUM(total) as revenue FROM orders WHERE status IN $valid_status AND YEAR(created_at) = YEAR(CURDATE())");
$rev_year = $rev_year_res->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

// 4. Top 5 sản phẩm bán chạy nhất
$sql_best_selling = "
    SELECT 
        oi.product_id, 
        oi.product_type, 
        SUM(oi.quantity) as total_sold,
        CASE 
            WHEN oi.product_type = 'sanpham' THEN (SELECT ten_sanpham FROM sanpham WHERE id = oi.product_id)
            WHEN oi.product_type = 'phukien' THEN (SELECT ten_phukien FROM phukien WHERE id = oi.product_id)
        END as product_name,
        CASE 
            WHEN oi.product_type = 'sanpham' THEN (SELECT hinhanh FROM sanpham WHERE id = oi.product_id)
            WHEN oi.product_type = 'phukien' THEN (SELECT hinhanh FROM phukien WHERE id = oi.product_id)
        END as product_image
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status IN $valid_status
    GROUP BY oi.product_id, oi.product_type
    ORDER BY total_sold DESC
    LIMIT 5
";
$best_selling = $pdo->query($sql_best_selling)->fetchAll(PDO::FETCH_ASSOC);

// 5. Top 5 sản phẩm bán ế nhất (Bao gồm cả những sản phẩm chưa bán được)
$sql_worst_selling = "
    SELECT 
        id, 
        ten_sanpham as product_name, 
        'sanpham' as product_type,
        hinhanh as product_image,
        IFNULL((
            SELECT SUM(oi.quantity) 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.id 
            WHERE oi.product_id = sanpham.id AND oi.product_type = 'sanpham' AND o.status IN $valid_status
        ), 0) as total_sold
    FROM sanpham
    UNION
    SELECT 
        id, 
        ten_phukien as product_name, 
        'phukien' as product_type,
        hinhanh as product_image,
        IFNULL((
            SELECT SUM(oi.quantity) 
            FROM order_items oi 
            JOIN orders o ON oi.order_id = o.id 
            WHERE oi.product_id = phukien.id AND oi.product_type = 'phukien' AND o.status IN $valid_status
        ), 0) as total_sold
    FROM phukien
    ORDER BY total_sold ASC
    LIMIT 5
";
$worst_selling = $pdo->query($sql_worst_selling)->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Thống Kê Doanh Thu & Báo Cáo</h2>
</div>

<!-- Dashboard Cards for Revenue -->
<div class="dashboard-cards">
    <div class="card" id="cardRevWeek" style="cursor: pointer;" title="Bấm để xem chi tiết các ngày trong tuần">
        <div class="card-info">
            <h3>Doanh Thu Tuần Này</h3>
            <p style="color: #3b82f6;"><?php echo number_format($rev_week, 0, ',', '.'); ?> đ</p>
        </div>
        <div class="card-icon icon-blue"><i class="fas fa-calendar-week"></i></div>
    </div>
    
    <div class="card" id="cardRevMonth" style="cursor: pointer;" title="Bấm để xem chi tiết các tuần trong tháng">
        <div class="card-info">
            <h3>Doanh Thu Tháng Này</h3>
            <p style="color: #10b981;"><?php echo number_format($rev_month, 0, ',', '.'); ?> đ</p>
        </div>
        <div class="card-icon icon-green"><i class="fas fa-calendar-alt"></i></div>
    </div>
    
    <div class="card" id="cardRevYear" style="cursor: pointer;" title="Bấm để xem chi tiết các tháng trong năm">
        <div class="card-info">
            <h3>Doanh Thu Năm Nay</h3>
            <p style="color: #f59e0b;"><?php echo number_format($rev_year, 0, ',', '.'); ?> đ</p>
        </div>
        <div class="card-icon icon-yellow"><i class="fas fa-chart-line"></i></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
    <!-- Best Selling Table -->
    <div class="table-container" style="margin-bottom: 0;">
        <div class="table-header" style="border-bottom: 2px solid #10b981; padding-bottom: 15px;">
            <div class="table-title" style="color: #10b981;"><i class="fas fa-fire"></i> Top 5 Bán Chạy Nhất</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Hình</th>
                    <th>Tên Sản Phẩm</th>
                    <th style="text-align: center;">Loại</th>
                    <th style="text-align: center;">Đã Bán</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($best_selling) > 0): ?>
                    <?php foreach($best_selling as $row): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo htmlspecialchars($row['product_image']); ?>" alt="Img" style="width:40px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['product_name'] ?? 'Sản phẩm không rõ'); ?></strong></td>
                        <td style="text-align: center;">
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: <?php echo $row['product_type'] == 'sanpham' ? '#e0f2fe; color: #0284c7;' : '#f3e8ff; color: #9333ea;'; ?>">
                                <?php echo $row['product_type'] == 'sanpham' ? 'Sản phẩm' : 'Phụ kiện'; ?>
                            </span>
                        </td>
                        <td style="text-align: center; color: #10b981; font-weight: bold; font-size: 16px;">
                            <?php echo $row['total_sold']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center;">Chưa có dữ liệu bán hàng hợp lệ</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Worst Selling Table -->
    <div class="table-container" style="margin-bottom: 0;">
        <div class="table-header" style="border-bottom: 2px solid #ef4444; padding-bottom: 15px;">
            <div class="table-title" style="color: #ef4444;"><i class="fas fa-snowflake"></i> Top 5 Bán Ế Nhất</div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Hình</th>
                    <th>Tên Sản Phẩm</th>
                    <th style="text-align: center;">Loại</th>
                    <th style="text-align: center;">Đã Bán</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($worst_selling) > 0): ?>
                    <?php foreach($worst_selling as $row): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo htmlspecialchars($row['product_image']); ?>" alt="Img" style="width:40px; height:40px; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong></td>
                        <td style="text-align: center;">
                            <span style="font-size: 11px; padding: 2px 6px; border-radius: 4px; background: <?php echo $row['product_type'] == 'sanpham' ? '#e0f2fe; color: #0284c7;' : '#f3e8ff; color: #9333ea;'; ?>">
                                <?php echo $row['product_type'] == 'sanpham' ? 'Sản phẩm' : 'Phụ kiện'; ?>
                            </span>
                        </td>
                        <td style="text-align: center; color: <?php echo $row['total_sold'] == 0 ? '#ef4444' : '#6b7280'; ?>; font-weight: bold; font-size: 16px;">
                            <?php echo $row['total_sold']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center;">Chưa có dữ liệu</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Chi tiết Doanh Thu -->
<div class="modal-overlay" id="drilldownModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; width:600px; max-width:90%; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.2); display:flex; flex-direction:column; max-height:80vh;">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; border-bottom:1px solid #eee;">
            <div style="display:flex; align-items:center; gap: 10px;">
                <button id="btnBackDrilldown" class="btn btn-outline" style="display:none; padding: 5px 10px; font-size: 12px;"><i class="fas fa-arrow-left"></i> Quay lại</button>
                <h3 id="drilldownTitle" style="margin:0; font-size: 18px; color:#1f2937;">Chi Tiết Doanh Thu</h3>
            </div>
            <i class="fas fa-times" id="closeDrilldownModal" style="cursor:pointer; font-size:20px; color:#6b7280;"></i>
        </div>
        <!-- Body -->
        <div style="padding: 20px; overflow-y:auto; flex-grow:1;">
            <table class="admin-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th style="text-align: right;">Doanh thu</th>
                    </tr>
                </thead>
                <tbody id="drilldownTableBody">
                    <!-- Dữ liệu AJAX render ở đây -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .hover-row:hover {
        background-color: #f3f4f6 !important;
    }
</style>

<script src="js/thongke_doanhthu.js"></script>
<?php include 'includes/footer.php'; ?>
