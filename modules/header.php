<?php
require_once dirname(__FILE__) . '/../config/auth.php';

function getHeader($title)
{
    $role = $_SESSION['role'] ?? 'guest';
    $username = htmlspecialchars($_SESSION['username'] ?? 'Guest');
    $root_path = "/";

    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="vi">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $title ?> - Quản lý</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>

    <body>
        <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="../../index.php">
                    <i class="fas fa-university mr-2"></i>Quản lý
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ml-auto">
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navQuanLy" role="button"
                                    data-toggle="dropdown">
                                    <i class="fas fa-user-cog mr-1"></i>Quản lý
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="../../modules/giaovien/index.php"><i
                                            class="fas fa-chalkboard-teacher"></i> Giảng viên</a>
                                    <a class="dropdown-item" href="../../modules/bangcap/index.php"><i
                                            class="fas fa-graduation-cap"></i> Bằng cấp</a>
                                    <a class="dropdown-item" href="../../modules/khoa/index.php"><i class="fas fa-building"></i>
                                        Khoa</a>
                                    <a class="dropdown-item" href="../../modules/thongke/giaovien_theo_khoa.php"><i
                                            class="fas fa-users"></i> Thống kê Giảng viên</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navLichDay" role="button"
                                    data-toggle="dropdown">
                                    <i class="fas fa-calendar-check mr-1"></i>Quản lý môn học
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="../../modules/monhoc/index.php"><i
                                            class="fas fa-book"></i>Học phần</a>
                                    <a class="dropdown-item" href="../../modules/buoiday/index.php"><i
                                            class="fas fa-calendar-alt"></i> Buổi dạy</a>
                                    <a class="dropdown-item" href="../../modules/lichday/lichday_dinhky.php"><i
                                            class="fas fa-calendar-week"></i> Lập lịch dạy</a>
                                    <a class="dropdown-item" href="../../modules/hocky/index.php"><i class="fas fa-school"></i>
                                        Học kỳ</a>
                                    <a class="dropdown-item" href="../../modules/thongke/solop.php"><i
                                            class="fas fa-chart-pie"></i> Thống kê Số lớp học phần</a>

                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navLuong" role="button" data-toggle="dropdown">
                                    <i class="fas fa-money-bill-wave mr-1"></i>Lương
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="../../modules/luong/index.php"><i
                                            class="fas fa-calculator"></i> Tính lương</a>
                                </div>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navBaoCao" role="button"
                                    data-toggle="dropdown">
                                    <i class="fas fa-table mr-1"></i>Báo cáo
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="../../modules/baocao/tienday_giaovien.php">
                                        <i class="fas fa-money-bill"></i> Báo cáo tiền lương giáo viên
                                    </a>
                                    <a class="dropdown-item" href="../../modules/baocao/tienday_khoa.php">
                                        <i class="fas fa-building"></i> Báo cáo tiền lương theo khoa
                                    </a>
                                    <a class="dropdown-item" href="../../modules/baocao/tienday_truong.php">
                                        <i class="fas fa-university"></i> Báo cáo tiền lương toàn trường
                                    </a>
                                </div>
                            </li>
                            <li class="nav-item">
                                <a href="../../auth/logout.php" class="nav-link">
                                    <i class="fas fa-sign-out-alt"></i> Đăng xuất (<?= $username ?>)
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container mt-4">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            <h2 class="mb-4" style="font-size:1.3rem;font-weight:600;color:#2196f3;">
                <i class="fas fa-chalkboard-teacher mr-2"></i><?= $title ?>
            </h2>
            <?php
            return ob_get_clean();
}

function getFooter()
{
    return <<<HTML
        </div>
        <!-- Scripts -->
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script>
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            var formChanged = false;
            $('form input, form select, form textarea').on('change input', function() {
                formChanged = true;
            });
            $('form').on('submit', function() {
                formChanged = false;
            });
            // Chỉ cảnh báo nếu KHÔNG phải trang báo cáo
            if (!/baocao/.test(window.location.pathname)) {
                $(window).on('beforeunload', function() {
                    if (formChanged) {
                        return 'Bạn có thay đổi chưa được lưu. Bạn có chắc chắn muốn rời khỏi trang này?';
                    }
                });
            }
        </script>
    </body>
    </html>
HTML;
}