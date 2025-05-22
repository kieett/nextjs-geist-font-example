<?php
session_start();

// Kiểm tra đăng nhập và quyền admin
function checkAdmin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ../login.php');
        exit();
    }
}

// Kiểm tra quyền admin cho tất cả các trang trong thư mục admin
checkAdmin();
?>
