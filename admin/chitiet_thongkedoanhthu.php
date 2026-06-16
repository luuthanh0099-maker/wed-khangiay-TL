<?php
require_once '../model/xl_data.php';
$db = new xl_data();
$pdo = $db->connection_database();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$valid_status = "('confirmed', 'shipping', 'completed', 'delivered')";

$response = ['status' => 'success', 'data' => [], 'title' => ''];

if ($action == 'get_year') {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $response['title'] = "Doanh Thu Các Tháng Năm $year";
    
    $months = [];
    for ($i = 1; $i <= 12; $i++) {
        $months[$i] = ['label' => "Tháng $i", 'revenue' => 0, 'value' => $i];
    }
    
    $query = "SELECT MONTH(created_at) as month_val, SUM(total) as revenue 
              FROM orders 
              WHERE status IN $valid_status AND YEAR(created_at) = $year 
              GROUP BY MONTH(created_at)";
    $res = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    if ($res) {
        foreach ($res as $row) {
            $months[$row['month_val']]['revenue'] = (float)$row['revenue'];
        }
    }
    
    $response['data'] = array_values($months);
}
elseif ($action == 'get_month') {
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $response['title'] = "Doanh Thu Các Tuần Trong Tháng $month/$year";
    
    $query = "SELECT YEARWEEK(created_at, 1) as week_val, 
                     MIN(DATE(created_at)) as start_date, 
                     MAX(DATE(created_at)) as end_date, 
                     SUM(total) as revenue 
              FROM orders 
              WHERE status IN $valid_status AND MONTH(created_at) = $month AND YEAR(created_at) = $year 
              GROUP BY YEARWEEK(created_at, 1)
              ORDER BY week_val ASC";
    $res = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    $weeks = [];
    $week_num = 1;
    if ($res) {
        foreach ($res as $row) {
            $sd = date('d/m', strtotime($row['start_date']));
            $ed = date('d/m', strtotime($row['end_date']));
            $weeks[] = [
                'label' => "Tuần $week_num ($sd - $ed)", 
                'revenue' => (float)$row['revenue'], 
                'value' => $row['week_val']
            ];
            $week_num++;
        }
    }
    $response['data'] = $weeks;
}
elseif ($action == 'get_week') {
    $week_val = isset($_GET['week_val']) ? preg_replace('/[^0-9]/', '', $_GET['week_val']) : '';
    
    if (empty($week_val)) {
        $query_week = "SELECT YEARWEEK(CURDATE(), 1) as cur_week";
        $week_val = $pdo->query($query_week)->fetch(PDO::FETCH_ASSOC)['cur_week'];
        $response['title'] = "Doanh Thu Các Ngày Trong Tuần Này";
    } else {
        $response['title'] = "Doanh Thu Các Ngày Trong Tuần";
    }
    
    $days = [
        'Monday' => ['label' => 'Thứ 2', 'revenue' => 0, 'date' => ''],
        'Tuesday' => ['label' => 'Thứ 3', 'revenue' => 0, 'date' => ''],
        'Wednesday' => ['label' => 'Thứ 4', 'revenue' => 0, 'date' => ''],
        'Thursday' => ['label' => 'Thứ 5', 'revenue' => 0, 'date' => ''],
        'Friday' => ['label' => 'Thứ 6', 'revenue' => 0, 'date' => ''],
        'Saturday' => ['label' => 'Thứ 7', 'revenue' => 0, 'date' => ''],
        'Sunday' => ['label' => 'Chủ nhật', 'revenue' => 0, 'date' => '']
    ];
    
    $query = "SELECT DATE(created_at) as date_val, DAYNAME(created_at) as day_name, SUM(total) as revenue 
              FROM orders 
              WHERE status IN $valid_status AND YEARWEEK(created_at, 1) = '$week_val' 
              GROUP BY DATE(created_at)";
    $res = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    if ($res) {
        foreach ($res as $row) {
            $day_name = $row['day_name'];
            if (isset($days[$day_name])) {
                $days[$day_name]['revenue'] = (float)$row['revenue'];
                $days[$day_name]['date'] = date('d/m/Y', strtotime($row['date_val']));
            }
        }
    }
    
    $final_data = [];
    foreach ($days as $k => $v) {
        $label = $v['label'];
        if (!empty($v['date'])) {
            $label .= " (" . $v['date'] . ")";
        }
        $final_data[] = [
            'label' => $label,
            'revenue' => $v['revenue']
        ];
    }
    
    $response['data'] = $final_data;
} else {
    $response['status'] = 'error';
    $response['message'] = 'Invalid action';
}

echo json_encode($response);
?>
