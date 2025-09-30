<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function checkAuth()
{
    if (!isLoggedIn()) {
        header("Location: /KiemThu/auth/login.php");
        exit();
    }
}

function checkTeacherAccess()
{
    if ($_SESSION['role'] === 'teacher') {
        $current_path = $_SERVER['REQUEST_URI'];

        // Kiểm tra và xử lý đường dẫn lặp
        $current_path = preg_replace('~/+~', '/', $current_path);
        $current_path = parse_url($current_path, PHP_URL_PATH);

        // Danh sách đường dẫn cho phép
        $teacher_paths = [
            '/KiemThu/auth/logout.php',
            '/KiemThu/auth/login.php',
            '/KiemThu/modules/lichday/',
            '/KiemThu/modules/lichday/index.php',
            '/KiemThu/modules/lichday/them.php',
            '/KiemThu/modules/lichday/lichday_dinhky.php'
        ];

        // Kiểm tra quyền truy cập
        foreach ($teacher_paths as $path) {
            if (strpos($current_path, $path) === 0) {
                return;
            }
        }

        // Chuyển hướng về trang lịch dạy nếu không có quyền
        header("Location: /KiemThu/modules/lichday/");
        exit();
    }
}

// Thêm vào đầu mỗi file cần bảo vệ
function checkAccess()
{
    if (!isLoggedIn()) {
        checkAuth();
        return;
    }
    checkTeacherAccess();
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isTeacher()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function getCurrentTeacher()
{
    return $_SESSION['ma_gv'] ?? null;
}
