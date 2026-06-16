<?php
session_start();
require_once '../model/xl_data.php';

$db = new xl_data();
$pdo = $db->connection_database();

if (isset($_GET['q'])) {
    $q = $_GET['q'];
    // Tìm kiếm các sản phẩm chứa từ khóa ở bất kỳ vị trí nào
    $search = '%' . $q . '%'; 

    $results = [];

    // 1. Tìm trong sanpham
    $stmt1 = $pdo->prepare("SELECT id, ten_sanpham as name, hinhanh as img, gia as price, 'sanpham' as type FROM sanpham WHERE ten_sanpham LIKE ? LIMIT 5");
    $stmt1->execute([$search]);
    $res1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    $results = array_merge($results, $res1);

    // 2. Tìm trong phukien
    $stmt2 = $pdo->prepare("SELECT id, ten_phukien as name, hinhanh as img, gia as price, 'phukien' as type FROM phukien WHERE ten_phukien LIKE ? LIMIT 5");
    $stmt2->execute([$search]);
    $res2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $results = array_merge($results, $res2);

    // Trả về HTML
    if (count($results) > 0) {
        foreach($results as $item) {
            $link = $item['type'] == 'sanpham' ? "chitiet_sanpham.php?id=".$item['id'] : "chitiet_phukien.php?id=".$item['id'];
            $price = number_format($item['price'], 0, ',', '.') . 'đ';
            echo "<a href='{$link}' class='search-result-item'>";
            echo "<img src='../{$item['img']}' alt='img'>";
            echo "<div class='search-result-info'>";
            echo "<h4>" . htmlspecialchars($item['name']) . "</h4>";
            echo "<span class='price'>{$price}</span>";
            echo "</div></a>";
        }
    } else {
        echo "<div class='search-no-result'>Không tìm thấy sản phẩm nào bắt đầu bằng chữ '" . htmlspecialchars($q) . "'.</div>";
    }
}
?>
