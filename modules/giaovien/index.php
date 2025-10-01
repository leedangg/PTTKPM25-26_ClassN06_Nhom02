<?php
require_once '../../config/database.php';
require_once '../header.php';

// PHP logic: Kết nối DB, lấy dữ liệu, xử lý tìm kiếm (giữ nguyên, đã chuẩn)
$database = new Database();
$conn = $database->getConnection();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_param = "%$search%";

$root_path = '../';

// Lệnh SQL đã an toàn và hiệu quả
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ==================== BASE STYLES ==================== */
        body {
            background-color: #f0f4f8;
            /* Màu nền nhẹ nhàng hơn */
            font-family: 'Inter', sans-serif;
        }

        .page-wrapper {
            padding: 30px 15px;
            max-width: 1400px;
            /* Giữ 1400px hoặc 1500px tùy ý, 1400px cân đối hơn */
            margin: 0 auto;
        }

        /* ==================== CARD STYLES ==================== */
        .card {
            border: 1px solid #e3e6f0;
            border-radius: 18px;
            /* Tăng border-radius cho cảm giác mềm mại */
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 18px 18px 0 0 !important;
            padding: 1.25rem 1.5rem;
            /* Tối ưu padding */
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        /* ==================== SEARCH & ADD BUTTONS ==================== */
        .search-add-bar {
            padding: 20px 15px 15px;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
            /* Đường viền nhẹ */
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #1e8738);
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.4);
        }

        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 8px;
        }

        /* ==================== TABLE STYLES ==================== */
        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .thead-dark th {
            background: linear-gradient(to right, #343a40, #495057);
            color: #f8f9fa;
            border-bottom: 4px solid #007bff;
            /* Viền xanh nổi bật */
            font-weight: 600;
            text-align: left;
            /* Căn trái cho header để dễ đọc hơn */
            padding: 0.75rem 1rem;
        }

        .table tbody tr td {
            vertical-align: middle;
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
            color: #344767;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Zebra Striping */
        }

        .table tbody tr:hover {
            background-color: #eef1f5;
            box-shadow: none;
            /* Bỏ box-shadow khi hover để không bị lặp */
            transform: none;
        }


        /* ==================== CUSTOM COLUMNS ==================== */

        /* Tối ưu hóa độ rộng các cột (Readability) */
        .table th:nth-child(1),
        .table td:nth-child(1) {
            width: 80px;
            text-align: center;
        }

        /* Mã GV */
        .table th:nth-child(4),
        .table td:nth-child(4) {
            width: 120px;
        }

        /* SĐT */
        .table th:nth-child(5),
        .table td:nth-child(5) {
            width: 150px;
        }

        /* Khoa */
        .table th:nth-child(6),
        .table td:nth-child(6) {
            width: 150px;
        }

        /* Bằng cấp */
        .action-column {
            width: 120px;
            min-width: 120px;
            text-align: center;
        }

        /* Mật khẩu (UX/UI Nâng cao) */
        .password-cell {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            color: #dc3545;
            cursor: pointer;
            text-align: center;
            user-select: none;
            width: 120px;
            min-width: 120px;
        }

        .password-hidden {
            filter: blur(4px);
            opacity: 0.7;
            transition: filter 0.3s, opacity 0.3s;
        }

        .password-visible {
            color: #007bff;
            filter: none !important;
            opacity: 1;
            font-weight: 700;
            text-decoration: underline;
            text-decoration-color: #007bff50;
        }

        /* Nút hành động */
        .btn-group .btn {
            padding: 0.3rem 0.6rem;
            font-size: 0.85rem;
            border-radius: 6px !important;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Căn giữa tiêu đề cột thao tác */
        .table th:last-child {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-users-cog mr-3"></i> Danh sách Giảng viên
            </div>

            <div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center search-add-bar">
                    <a href="them.php" class="btn btn-success">
                        <i class="fas fa-plus mr-1"></i> Thêm giảng viên
                    </a>
                    <form class="form-inline" method="GET">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                placeholder="Tìm kiếm (Họ tên, Email)..."
                                value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Tìm
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="index.php" class="btn btn-secondary" title="Xóa tìm kiếm">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
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
                                        <td class="text-center"><?= htmlspecialchars($gv['ma_gv'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </td>
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
                                                echo str_repeat('•', 8); // Dùng số cố định (8) để tránh đoán độ dài
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
                                        <?php if (!empty($search)): ?>
                                            <div class="mt-2"><a href="index.php" class="text-primary font-weight-bold">Hiển thị
                                                    tất cả giảng viên</a></div>
                                        <?php endif; ?>
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
                var actualPassword = $span.data('password');
                var maskedText = '•'.repeat(8); // Mask cố định

                if ($span.hasClass('password-hidden')) {
                    // Hiện mật khẩu
                    $span.text(actualPassword);
                    $span.removeClass('password-hidden').addClass('password-visible');
                } else {
                    // Ẩn mật khẩu
                    $span.text(maskedText);
                    $span.removeClass('password-visible').addClass('password-hidden');
                }
            });

            // Khởi tạo trạng thái ban đầu cho tất cả ô mật khẩu
            $('.password-cell').each(function () {
                var $span = $(this).find('.password-hidden');
                var actualPassword = $span.data('password');
                if (actualPassword.length === 0) {
                    $span.text('Không TK'); // Hiện thị rõ ràng nếu không có tài khoản
                    $span.css({ 'filter': 'none', 'color': '#6c757d', 'cursor': 'default' });
                    $(this).off('click'); // Vô hiệu hóa click
                }
            });
        });
    </script>
</body>

</html>