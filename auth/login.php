<?php
// Bắt đầu session và require các file cần thiết
session_start();
require_once '../config/init.php';
require_once '../config/database.php';

// Kiểm tra và xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        $error = "Lỗi kết nối cơ sở dữ liệu";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        // Lấy thông tin user
        $stmt = $conn->prepare("SELECT u.*, g.ho_ten, g.avatar FROM users u 
                                 LEFT JOIN giaovien g ON u.ma_gv = g.ma_gv 
                                 WHERE u.username = ? AND u.active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Kiểm tra mật khẩu (Giữ nguyên logic kiểm tra mật khẩu)
        $valid_password = false;
        if ($user) {
            // Logic cho Giáo viên (mặc định 1234 hoặc mật khẩu đã hash)
            if ($user['role'] === 'teacher') {
                // Luôn kiểm tra mật khẩu đã hash trước, sau đó kiểm tra mật khẩu mặc định (1234)
                if (password_verify($password, $user['password'] ?? '') || $password === '1234') {
                    $valid_password = true;
                }
                // Logic cho Admin và Kế toán (mật khẩu cứng)
            } elseif ($user['role'] === 'admin' && $password === 'admin') {
                $valid_password = true;
            } elseif ($user['role'] === 'accountant' && $password === 'ketoan') {
                $valid_password = true;
            }
        }

        if ($user && $valid_password) {
            // Thiết lập Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['ma_gv'] = $user['ma_gv'];
            $_SESSION['ho_ten'] = $user['ho_ten'];
            $_SESSION['avatar'] = $user['avatar']; // Lưu thêm avatar

            // Chuyển hướng
            header("Location: ../index.php");
            exit();
        } else {
            $error = "Tên đăng nhập hoặc mật khẩu không đúng";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            color: white;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: rotate 4s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header-title {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 300;
            margin-top: 5px;
        }

        .card-body {
            padding: 40px 35px;
        }

        .alert {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(238, 90, 111, 0.3);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .alert i {
            font-size: 18px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 10px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 16px;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 400;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-control:focus + .input-icon {
            color: #764ba2;
            transform: translateY(-50%) scale(1.1);
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            font-size: 16px;
            transition: color 0.3s ease;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-info {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .login-info-title {
            font-size: 13px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .account-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .account-item {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
            color: #4a5568;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .account-item:last-child {
            border-bottom: none;
        }

        .account-item i {
            color: #667eea;
            font-size: 10px;
        }

        .account-role {
            font-weight: 600;
            color: #2d3748;
            min-width: 80px;
        }

        .account-credentials {
            color: #718096;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 30px 25px;
            }

            .header-title {
                font-size: 20px;
            }

            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }

            .login-info {
                padding: 20px;
            }

            .account-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <div class="header-content">
                    <div class="logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h1 class="header-title">Đăng nhập</h1>
                    <p class="header-subtitle">Hệ thống quản lý giáo dục</p>
                </div>
            </div>

            <div class="card-body">
                <?php if (isset($error)): ?>
                        <div class="alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error) ?></span>
                        </div>
                <?php endif; ?>

                <form method="POST" novalidate id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="username">Tên đăng nhập</label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                name="username" 
                                id="username" 
                                class="form-control"
                                placeholder="Nhập tên đăng nhập của bạn" 
                                required
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                autocomplete="username">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                class="form-control"
                                placeholder="Nhập mật khẩu của bạn" 
                                required
                                autocomplete="current-password">
                            <i class="fas fa-lock input-icon"></i>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </button>
                </form>

                <div class="login-info">
                    <div class="login-info-title">
                        <i class="fas fa-info-circle"></i>
                        <span>Tài khoản thử nghiệm</span>
                    </div>
                    <ul class="account-list">
                        <li class="account-item">
                            <i class="fas fa-circle"></i>
                            <span class="account-role">Admin:</span>
                            <span class="account-credentials">admin / admin</span>
                        </li>
                        <li class="account-item">
                            <i class="fas fa-circle"></i>
                            <span class="account-role">Kế toán:</span>
                            <span class="account-credentials">ketoan / ketoan</span>
                        </li>
                        <li class="account-item">
                            <i class="fas fa-circle"></i>
                            <span class="account-role">Giảng viên:</span>
                            <span class="account-credentials">Mã GV / 1234</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Add smooth transitions on input focus
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>

</html>