<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wed_khangiay";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
// Set charset to utf8mb4 for Vietnamese support
$conn->set_charset("utf8mb4");
?>
