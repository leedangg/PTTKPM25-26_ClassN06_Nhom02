<?php
require_once '../config/init.php';
require_once '../config/database.php';

// Kiểm tra và xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        $error = "Lỗi kết nối cơ sở dữ liệu";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Lấy thông tin user
        $stmt = $conn->prepare("SELECT u.*, g.ho_ten FROM users u 
                               LEFT JOIN giaovien g ON u.ma_gv = g.ma_gv 
                               WHERE u.username = ? AND u.active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Kiểm tra mật khẩu (Giữ nguyên logic kiểm tra mật khẩu)
        $valid_password = false;
        if ($user) {
            // Logic cho Giáo viên (mặc định 1234 hoặc mật khẩu đã hash)
            if ($user['role'] === 'teacher') {
                // Luôn kiểm tra mật khẩu đã hash trước nếu có, sau đó kiểm tra mật khẩu mặc định
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
    <style>
        /* ------------------- Global/Body Style ------------------- */
        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            /* Nền gradient hiện đại */
            background: linear-gradient(135deg, #e0f7fa 0%, #b3e5fc 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ------------------- Login Card Style ------------------- */
        .login-card {
            width: 100%;
            max-width: 420px;
            /* Hơi rộng hơn */
            background: #fff;
            border-radius: 18px;
            /* Bo góc lớn hơn */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            /* Đổ bóng sâu hơn */
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-3px);
        }

        .login-card .card-header {
            /* Gradient xanh dương mạnh mẽ */
            background: linear-gradient(90deg, #1e88e5 0%, #00acc1 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
        }

        .login-card .card-body {
            padding: 2rem;
        }

        /* ------------------- Form Elements ------------------- */
        .form-group label {
            font-weight: 600;
            color: #343a40;
            margin-bottom: 0.5rem;
            display: block;
        }

        .input-group-text {
            border-radius: 8px 0 0 8px;
            background-color: #f8f9fa;
            border-right: none;
            color: #6c757d;
        }

        .form-control {
            border-radius: 0 8px 8px 0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border-left: none;
            height: auto;
            /* Tự động điều chỉnh chiều cao */
        }

        .form-control:focus {
            border-color: #00acc1;
            box-shadow: 0 0 0 0.2rem rgba(0, 172, 193, 0.25);
        }

        /* ------------------- Button Style ------------------- */
        .btn-login {
            background: linear-gradient(45deg, #00acc1 0%, #1e88e5 100%);
            border: none;
            font-weight: 700;
            padding: 0.75rem;
            font-size: 1.05rem;
            border-radius: 10px;
            margin-top: 1rem;
            box-shadow: 0 4px 10px rgba(0, 172, 193, 0.3);
            transition: all 0.3s;
        }

        .btn-login:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }

        /* ------------------- Alert/Error Style ------------------- */
        .alert {
            font-size: 0.95rem;
            border-left: 5px solid #dc3545;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background-color: #f8d7da;
            /* Màu nền nhẹ nhàng hơn */
            color: #721c24;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.2);
        }

        /* ------------------- Login Note Style ------------------- */
        .login-note {
            margin-top: 1.5rem;
            padding: 1rem;
            border: 1px dashed #ced4da;
            border-radius: 10px;
            background-color: #f8f9fa;
            font-size: 0.85rem;
            color: #495057;
            text-align: left;
            line-height: 1.6;
        }

        .login-note strong {
            color: #2196f3;
        }

        .login-note .fas {
            color: #1e88e5;
            margin-right: 5px;
        }

        /* Media Queries */
        @media (max-width: 576px) {
            .login-card {
                margin: 20px;
            }

            .login-card .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="card-header">
            <i class="fas fa-university mr-2"></i> **HỆ THỐNG QUẢN LÝ**
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger shadow-sm">
                    <i class="fas fa-times-circle mr-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                        </div>
                        <input type="text" name="username" id="username" class="form-control"
                            placeholder="Nhập tên đăng nhập" required
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                        </div>
                        <input type="password" name="password" id="password" class="form-control"
                            placeholder="Nhập mật khẩu" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-login shadow">
                    <i class="fas fa-sign-in-alt mr-2"></i> **Đăng nhập**
                </button>
            </form>

            <div class="login-note">
                <p class="font-weight-bold mb-2"><i class="fas fa-info-circle"></i> THÔNG TIN ĐĂNG NHẬP:</p>
                <?php if (isset($_GET['role']) && $_GET['role'] === 'admin'): ?>
                    <p class="mb-1">Tài khoản **Admin**:</p>
                    <p class="mb-0">- Username: **admin**</p>
                    <p class="mb-0">- Password: **admin**</p>
                <?php elseif (isset($_GET['role']) && $_GET['role'] === 'accountant'): ?>
                    <p class="mb-1">Tài khoản **Kế toán**:</p>
                    <p class="mb-0">- Username: **ketoan**</p>
                    <p class="mb-0">- Password: **ketoan**</p>
                <?php else: ?>
                    <p class="mb-1">Tài khoản **Giáo viên**:</p>
                    <p class="mb-0">- Tên đăng nhập: **[họ tên không dấu]@teacher.edu.vn**</p>
                    <p class="mb-0">- Mật khẩu mặc định: **1234**</p>
                    <small class="d-block mt-1 text-muted">VD: Nguyễn Văn A -> nguyenvana@teacher.edu.vn</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
</body>

</html>