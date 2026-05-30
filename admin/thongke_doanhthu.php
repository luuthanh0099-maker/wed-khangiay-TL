<?php
include 'includes/header.php';

// Các trạng thái đơn hàng hợp lệ (không tính đơn hủy hay chờ xử lý - có thể linh hoạt điều chỉnh)
$valid_status = "('confirmed', 'shipping', 'completed', 'delivered')";

// 1. Doanh thu tuần này (Từ thứ 2 đến Chủ nhật)
$rev_week_res = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status IN $valid_status AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$rev_week = $rev_week_res->fetch_assoc()['revenue'] ?? 0;

// 2. Doanh thu tháng này
$rev_month_res = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status IN $valid_status AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$rev_month = $rev_month_res->fetch_assoc()['revenue'] ?? 0;

// 3. Doanh thu năm nay
$rev_year_res = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status IN $valid_status AND YEAR(created_at) = YEAR(CURDATE())");
$rev_year = $rev_year_res->fetch_assoc()['revenue'] ?? 0;

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
$best_selling = $conn->query($sql_best_selling);

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
$worst_selling = $conn->query($sql_worst_selling);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="color: #1f2937;">Thống Kê Doanh Thu & Báo Cáo</h2>
</div>

<!-- Dashboard Cards for Revenue -->
<div class="dashboard-cards">
    <div class="card">
        <div class="card-info">
            <h3>Doanh Thu Tuần Này</h3>
            <p style="color: #3b82f6;"><?php echo number_format($rev_week, 0, ',', '.'); ?> đ</p>
        </div>
        <div class="card-icon icon-blue"><i class="fas fa-calendar-week"></i></div>
    </div>
    
    <div class="card">
        <div class="card-info">
            <h3>Doanh Thu Tháng Này</h3>
            <p style="color: #10b981;"><?php echo number_format($rev_month, 0, ',', '.'); ?> đ</p>
        </div>
        <div class="card-icon icon-green"><i class="fas fa-calendar-alt"></i></div>
    </div>
    
    <div class="card">
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
                <?php if($best_selling && $best_selling->num_rows > 0): ?>
                    <?php while($row = $best_selling->fetch_assoc()): ?>
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
                    <?php endwhile; ?>
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
                <?php if($worst_selling && $worst_selling->num_rows > 0): ?>
                    <?php while($row = $worst_selling->fetch_assoc()): ?>
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align: center;">Chưa có dữ liệu</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
