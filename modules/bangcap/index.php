<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách bằng cấp
$sql = "SELECT * FROM bangcap ORDER BY ma_bangcap";
$stmt = $conn->query($sql);
$bangcaps = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Quản lý Bằng cấp");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý Bằng cấp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Thẻ bao ngoài chính */
        .page-wrapper {
            padding: 30px 15px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Hiệu ứng lung linh cho Card */
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

        /* Bảng dữ liệu */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            /* Cần thiết để áp dụng border-radius cho bảng */
        }

        .table {
            margin-bottom: 0;
        }

        .thead-dark th {
            background-color: #343a40;
            background: linear-gradient(to right, #495057, #343a40);
            color: #fff;
            border-bottom: 3px solid #007bff;
            vertical-align: middle;
            font-weight: 600;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f8f9fa;
            /* Màu nền nhẹ cho hàng lẻ */
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: #e9ecef;
            /* Hiệu ứng hover cho hàng */
            transform: scale(1.005);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }

        /* Nút Thêm */
        .btn-primary {
            background: linear-gradient(45deg, #28a745, #157347);
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #157347, #28a745);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(40, 167, 69, 0.4);
        }

        /* Nút Thao tác */
        .btn-sm {
            border-radius: 5px;
            transition: all 0.2s;
            margin: 2px;
        }

        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-list-alt mr-3"></i> Quản lý Bằng cấp
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <a href="them.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Thêm Bằng cấp Mới
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-center">
                        <thead class="thead-dark">
                            <tr>
                                <th><i class="fas fa-key"></i> Mã</th>
                                <th><i class="fas fa-certificate"></i> Tên Bằng cấp</th>
                                <th><i class="fas fa-calculator"></i> Hệ số Giảng dạy</th>

                                <th><i class="fas fa-tools"></i> Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($bangcaps) > 0): ?>
                                <?php foreach ($bangcaps as $bc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($bc['ma_bangcap']) ?></td>
                                        <td class="text-left"><?= htmlspecialchars($bc['ten_bangcap']) ?></td>
                                        <td><?= number_format($bc['he_so'], 2) ?></td>

                                        <td>
                                            <a href="sua.php?id=<?= htmlspecialchars($bc['ma_bangcap']) ?>"
                                                class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Sửa
                                            </a>
                                            <a href="xoa.php?id=<?= htmlspecialchars($bc['ma_bangcap']) ?>"
                                                onclick="return confirm('Bạn có chắc muốn xóa bằng cấp <?= htmlspecialchars($bc['ten_bangcap']) ?> không? Hành động này không thể hoàn tác!')"
                                                class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted p-4">
                                        <i class="fas fa-info-circle mr-2"></i>Chưa có bằng cấp nào được thêm vào hệ thống.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>