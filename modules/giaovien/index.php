<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

// Tự động xác định $root_path (Đường dẫn tương đối trực tiếp)
$root_path = '../';

$sql = "SELECT gv.*, k.ten_khoa, bc.ten_bangcap, u.username as tai_khoan, u.password as mat_khau
        FROM giaovien gv
        LEFT JOIN khoa k ON gv.ma_khoa = k.ma_khoa
        LEFT JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
        LEFT JOIN users u ON gv.ma_gv = u.ma_gv
        WHERE gv.ho_ten LIKE :search 
        OR gv.email LIKE :search
        ORDER BY gv.ma_gv DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([':search' => $search_param]);
$giaoviens = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Quản lý giảng viên");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Giảng viên</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* SỬA LỖI 1: Tăng độ rộng tối đa của container */
        .page-wrapper {
            padding: 30px 15px;
            max-width: 1500px;
            /* Tăng từ 1400px lên 1500px */
            margin: 0 auto;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .search-add-bar {
            padding: 0 15px 15px 15px;
            margin-bottom: 1rem;
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #157347);
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            transition: all 0.3s;
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #157347, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
        }

        .form-inline .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-inline .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .form-inline .btn-primary:hover {
            background: #0056b3;
        }

        /* Bảng dữ liệu */
        .table-responsive {
            border-radius: 10px;
            overflow-x: auto;
            /* Đảm bảo có thanh cuộn ngang nếu cần */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
            /* Đặt min-width cho bảng nếu cần, nhưng .table-responsive đã làm tốt việc này */
        }

        .thead-dark th {
            background: linear-gradient(to right, #495057, #343a40);
            color: #fff;
            border-bottom: 3px solid #007bff;
            vertical-align: middle;
            font-weight: 600;
            text-align: center;
        }

        .table tbody tr:hover {
            background-color: #e9ecef;
            transform: scale(1.005);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }

        .password-cell {
            font-family: monospace;
            color: #dc3545;
            cursor: pointer;
        }

        .password-hidden {
            filter: blur(4px);
            user-select: none;
        }

        /* SỬA LỖI 2: Tối ưu hóa cột Thao tác */
        .action-column {
            width: 150px;
            /* Đảm bảo cột có độ rộng cố định */
            min-width: 150px;
            text-align: center;
        }

        .btn-group .btn {
            padding: 0.3rem 0.5rem;
            /* Giảm padding để các nút sát nhau hơn */
            font-size: 0.8rem;
            /* Giảm cỡ chữ */
            border-radius: 5px !important;
            margin: 0 1px;
            /* Giảm margin giữa các nút */
            transition: transform 0.2s;
        }

        .btn-group .btn:hover {
            transform: translateY(-1px);
        }

        .table td {
            vertical-align: middle;
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users-cog mr-3"></i> Danh sách Giảng viên
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between search-add-bar">
                    <a href="them.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Thêm giảng viên
                    </a>
                    <form class="form-inline" method="GET">
                        <input type="text" name="search" class="form-control" placeholder="Tìm kiếm (Họ tên, Email)..."
                            value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-primary ml-2">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="index.php" class="btn btn-secondary ml-2" title="Xóa tìm kiếm">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th><i class="fas fa-id-card-alt"></i> Mã GV</th>
                                <th><i class="fas fa-user"></i> Họ tên</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-phone"></i> SĐT</th>
                                <th><i class="fas fa-university"></i> Khoa</th>
                                <th><i class="fas fa-graduation-cap"></i> Bằng cấp</th>
                                <th><i class="fas fa-user-shield"></i> Tài khoản</th>
                                <th><i class="fas fa-lock"></i> Mật khẩu</th>
                                <th class="action-column"><i class="fas fa-cogs"></i> Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($giaoviens) > 0): ?>
                                <?php foreach ($giaoviens as $gv): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($gv['ma_gv'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($gv['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($gv['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($gv['so_dien_thoai'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($gv['ten_khoa'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($gv['ten_bangcap'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($gv['tai_khoan'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="password-cell">
                                            <span class="password-hidden"
                                                data-password="<?= htmlspecialchars($gv['mat_khau'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                <?php
                                                // Hiển thị một chuỗi sao/chấm đại diện
                                                echo str_repeat('•', strlen($gv['mat_khau'] ?? ''));
                                                ?>
                                            </span>
                                        </td>
                                        <td class="action-column">
                                            <div class="btn-group" role="group">
                                                <a href="chitiet.php?id=<?= htmlspecialchars($gv['ma_gv'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    class="btn btn-info btn-sm" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="sua.php?id=<?= htmlspecialchars($gv['ma_gv'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    class="btn btn-warning btn-sm" title="Sửa thông tin">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="xoa.php?id=<?= htmlspecialchars($gv['ma_gv'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                    class="btn btn-danger btn-sm" title="Xóa"
                                                    onclick="return confirm('Bạn có chắc muốn xóa giảng viên <?= htmlspecialchars($gv['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8') ?> này không?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted p-4">
                                        <i class="fas fa-exclamation-circle mr-2"></i>Không tìm thấy giảng viên nào phù hợp
                                        với từ khóa: **<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>**
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Script JavaScript để xử lý ẩn/hiện mật khẩu
        $(document).ready(function () {
            $('.password-cell').on('click', function () {
                var $span = $(this).find('.password-hidden');
                var currentText = $span.text();
                var actualPassword = $span.data('password');

                if ($span.hasClass('password-hidden')) {
                    // Hiện mật khẩu
                    $span.text(actualPassword);
                    $span.removeClass('password-hidden');
                    $span.css({ 'filter': 'none', 'color': '#007bff' });
                } else {
                    // Ẩn mật khẩu
                    var maskedText = '•'.repeat(actualPassword.length);
                    $span.text(maskedText);
                    $span.addClass('password-hidden');
                    $span.css({ 'filter': 'blur(4px)', 'color': '#dc3545' });
                }
            });
        });
    </script>
</body>

</html>