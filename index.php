<?php
require_once 'config/init.php';
require_once 'modules/header.php';

if (isLoggedIn() && $_SESSION['role'] === 'teacher') {
    header("Location: modules/lichday/");
    exit();
}
echo getHeader("");

?>



<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Giảng viên</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>


    <div class="container mt-4">
        <?php if (isLoggedIn()): ?>
            <h2>Chào mừng đến với Hệ thống Quản lý Giảng viên</h2>
            <p>Vui lòng chọn chức năng từ menu phía trên.</p>
        <?php else: ?>
            <div class="jumbotron">
                <h1 class="display-4">Chào mừng đến với Hệ thống Quản lý Giảng viên</h1>
                <p class="lead">Vui lòng đăng nhập để sử dụng hệ thống.</p>
                <hr class="my-4">
                <a class="btn btn-primary btn-lg" href="auth/login.php">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>