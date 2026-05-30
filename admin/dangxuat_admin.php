<?php
session_start();
// Chỉ xóa session của admin, giữ nguyên session của user nếu có
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);

header("Location: dangnhap_admin.php");
exit();
