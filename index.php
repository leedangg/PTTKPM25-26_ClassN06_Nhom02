<?php
require_once 'config/init.php';
// Bắt đầu session đã được thực hiện trong init.php (giả định)

// Kiểm tra và chuyển hướng riêng cho Giảng viên (Teacher Role)
if (isLoggedIn() && $_SESSION['role'] === 'teacher') {
    header("Location: modules/lichday/");
    exit();
}

require_once 'modules/header.php'; // Giả định file này chứa hàm getHeader()

// Lấy thông tin người dùng để cá nhân hóa lời chào
$user_info = [
    'role' => $_SESSION['role'] ?? 'Guest',
    'ho_ten' => $_SESSION['ho_ten'] ?? 'Khách',
    'avatar' => $_SESSION['avatar'] ?? 'default.jpg' // Giả định lưu avatar trong session
];

// Hàm chuyển đổi role sang tiếng Việt
function getRoleName($role)
{
    switch ($role) {
        case 'admin':
            return 'Quản trị viên';
        case 'accountant':
            return 'Kế toán';
        case 'teacher':
            return 'Giảng viên';
        default:
            return 'Người dùng';
    }
}

echo getHeader(""); // Hiển thị Header, truyền tiêu đề rỗng cho trang chủ
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ | Hệ thống Quản lý Giảng viên</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        /* ------------------- Global Styles ------------------- */
        body {
            font-family: 'Inter', sans-serif;
            /* Thay đổi font sang Inter hiện đại hơn */

            min-height: 100vh;
            ;
        }

        .container {
            max-width: 1200px;
        }

        /* ------------------- Welcome Section (Logged In) ------------------- */
        .welcome-section {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            /* Màu xanh dương mạnh mẽ */
            border-radius: 24px;
            padding: 50px 40px;
            color: white;
            box-shadow: 0 20px 40px rgba(0, 86, 179, 0.4);
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
            display: flex;
            align-items: center;
        }

        .welcome-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.7);
            margin-right: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .welcome-content h2 {
            font-size: 38px;
            font-weight: 800;
            margin-bottom: 5px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        .welcome-content p {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
            margin-bottom: 0;
        }

        .welcome-content .user-role {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 5px;
        }

        /* ------------------- Feature Cards (Logged In) ------------------- */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 35px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            border-bottom: 5px solid transparent;
            animation: fadeInUp 0.8s ease-out;
            animation-fill-mode: both;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0, 123, 255, 0.3);
            border-color: #007bff;
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: #e6f0ff;
            /* Nền icon nhẹ hơn */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #007bff;
            transition: all 0.4s ease;
        }

        .feature-card:hover .feature-icon {
            background: #007bff;
            color: white;
            transform: scale(1.1);
        }

        .feature-card h4 {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 14px;
            color: #718096;
        }

        /* ------------------- Stats Section ------------------- */
        .stats-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeInUp 0.8s ease-out 0.5s;
            animation-fill-mode: both;
        }

        .stats-section h3 {
            color: #007bff;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            border-radius: 15px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 123, 255, 0.15);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #007bff;
            transition: color 0.3s;
        }

        .stat-item:hover .stat-number {
            color: #0056b3;
        }

        .stat-label {
            font-size: 14px;
            font-weight: 600;
            color: #6c757d;
            margin-top: 5px;
        }

        /* ------------------- Hero Section (Not Logged In) ------------------- */
        .hero-section {
            /* Giữ nguyên phong cách Hero Section cũ vì nó đã rất đẹp và nổi bật */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 24px;
            padding: 80px 40px;
            color: white;
            box-shadow: 0 30px 80px rgba(102, 126, 234, 0.4);
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .hero-icon {
            /* Giữ nguyên hiệu ứng */
            width: 120px;
            height: 120px;
            margin: 0 auto 30px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        .btn-login {
            /* Điều chỉnh nhẹ để nổi bật hơn */
            padding: 14px 40px;
            font-size: 17px;
            font-weight: 700;
            border-radius: 50px;
            background: white;
            color: #667eea;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ------------------- Info Cards (Not Logged In) ------------------- */
        .info-card {
            background: white;
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            border-left: 5px solid transparent;
            animation: fadeInUp 0.8s ease-out;
            animation-fill-mode: both;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
            border-left-color: #667eea;
        }

        .info-card-icon {
            font-size: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        /* ------------------- Footer ------------------- */
        .app-footer {
            margin-top: 50px;
            padding: 20px 0;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }

        /* ------------------- Animations (Giữ nguyên) ------------------- */
        @keyframes fadeInUp {
            /* ... (đã có trong code gốc) */
        }

        @keyframes pulse {
            /* ... (đã có trong code gốc) */
        }

        @keyframes rotate {
            /* ... (đã có trong code gốc) */
        }

        @keyframes bounce {
            /* ... (đã có trong code gốc) */
        }

        @media (max-width: 768px) {

            /* ... (CSS responsive đã có) */
            .welcome-section {
                flex-direction: column;
                text-align: center;
            }

            .welcome-avatar {
                margin: 0 auto 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <?php if (isLoggedIn()): ?>
            <div class="welcome-section">
                <img src="assets/img/admin.webp?= htmlspecialchars($user_info['avatar']) ?>" alt="User Avatar"
                    class="welcome-avatar">
                <div class="welcome-content">
                    <h2>Xin chào, <?= htmlspecialchars($user_info['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>!</h2>
                    <p>Bạn đang đăng nhập với vai trò: <span class="user-role"><?= getRoleName($user_info['role']) ?></span>
                    </p>
                    <p>Hãy bắt đầu công việc quản lý của bạn!</p>
                </div>
            </div>

            <div class="features-grid">
                <a href="modules/giaovien/index.php" class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h4>Quản lý Giảng viên</h4>
                    <p>Theo dõi và quản lý thông tin giảng viên một cách dễ dàng và hiệu quả.</p>
                </a>

                <a href="modules/buoiday/index.php" class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4>Lịch Giảng dạy</h4>
                    <p>Sắp xếp và quản lý lịch giảng dạy khoa học, tránh xung đột thời gian.</p>
                </a>



                <a href="modules/baocao/tienday_truong.php" class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4>Báo cáo & Thống kê</h4>
                    <p>Phân tích dữ liệu và tạo báo cáo chi tiết về hoạt động giảng dạy.</p>
                </a>
            </div>

            <div class="stats-section">
                <h3 style="text-align: center; color: #2d3748; margin-bottom: 10px; font-weight: 700;">
                    <i class="fas fa-chart-bar" style="color: #007bff;"></i> Thống kê tổng quan Hệ thống
                </h3>
                <p style="text-align: center; color: #718096; margin-bottom: 0;">
                    Theo dõi các số liệu .
                </p>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">150+</div>
                        <div class="stat-label">Giảng viên</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">320</div>
                        <div class="stat-label">Lớp học hiện tại</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">1,200</div>
                        <div class="stat-label">Tiết dạy/tháng</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Tỷ lệ hoàn thành</div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="hero-section">
                <div class="hero-content">
                    <div class="hero-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h1>HỆ THỐNG QUẢN LÝ GIẢNG VIÊN</h1>
                    <p class="lead">Giải pháp toàn diện, thông minh cho việc quản lý lịch dạy, thanh toán và điều hành hoạt
                        động giảng dạy.</p>
                    <div class="divider"></div>
                    <a class="btn btn-login" href="auth/login.php">
                        <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP NGAY
                    </a>
                </div>
            </div>

            <div class="info-cards">
                <div class="info-card">
                    <div class="info-card-icon">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <h5>Giao diện trực quan</h5>
                    <p>Thiết kế hiện đại, dễ sử dụng, giúp bạn làm quen và vận hành hệ thống nhanh chóng.</p>
                </div>

                <div class="info-card">
                    <div class="info-card-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Tiết kiệm thời gian</h5>
                    <p>Tự động hóa các quy trình phức tạp, giảm thiểu tối đa công việc giấy tờ thủ công.</p>
                </div>

                <div class="info-card">
                    <div class="info-card-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h5>Báo cáo chính xác</h5>
                    <p>Cung cấp các báo cáo thống kê chi tiết, giúp đưa ra quyết định quản lý kịp thời.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>



    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Javascript cho hiệu ứng cuộn mượt và animation (giữ nguyên logic gốc)
        document.addEventListener('DOMContentLoaded', function () {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            // Chỉ quan sát các phần tử cần animation khi đã được render
            document.querySelectorAll('.feature-card, .info-card, .stat-item').forEach((el) => {
                // Tắt animation ban đầu nếu chưa được xem
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.8s ease-out, transform 0.8s ease-out';
                observer.observe(el);
            });
        });
    </script>
</body>

</html>