<?php
session_start();
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách Học phần với thông tin khoa
$sql = "SELECT m.*, k.ten_khoa 
        FROM mon_hoc m 
        LEFT JOIN khoa k ON m.ma_khoa = k.ma_khoa 
        ORDER BY k.ten_khoa, m.ten_mon";

$stmt = $conn->query($sql);
$monhocs = $stmt->fetchAll();

// Nhóm Học phần theo khoa
$monhoc_by_khoa = [];
foreach ($monhocs as $mon) {
    // Đảm bảo tên khoa không phải là null (Học phần chung)
    $khoa_name = $mon['ten_khoa'] ?? 'Học phần chung (Không thuộc Khoa nào)';
    $khoa_ma = $mon['ma_khoa'] ?? 'CHUNG';
    $monhoc_by_khoa[$khoa_name]['monhocs'][] = $mon;
    $monhoc_by_khoa[$khoa_name]['ma'] = $khoa_ma;
}

echo getHeader("Quản lý Học phần");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #f4f7f6;
            /* Light background */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
            /* Blue Gradient Header */
            color: white;
            font-weight: 700;
            padding: 1rem 1.5rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        /* Nút Thêm Học phần */
        .btn-success-gradient {
            background: linear-gradient(45deg, #28a745 0%, #1e7e34 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-success-gradient:hover {
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.4);
            transform: translateY(-1px);
            color: white;
        }

        /* ACCORDION (Nhóm theo Khoa) */
        .accordion .card {
            margin-bottom: 10px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        .accordion .card-header {
            background: #fff;
            border-radius: 10px;
            padding: 0;
        }

        .accordion .btn-link {
            width: 100%;
            text-align: left;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #343a40;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .accordion .btn-link:hover {
            background-color: #f8f9fa;
        }

        .accordion .btn-link:not(.collapsed) {
            color: #007bff;
            /* Màu xanh khi đang mở */
            background-color: #eaf3ff;
            border-bottom: 1px solid #007bff;
            border-radius: 10px 10px 0 0;
        }

        /* Table Styling */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background-color: #eaf3ff;
        }

        .thead-light th {
            background-color: #f1f1f1;
            color: #343a40;
            font-weight: 600;
        }

        /* Thao tác buttons */
        .btn-sm {
            margin-right: 5px;
            border-radius: 5px;
        }
    </style>
</head>

<div class="container mt-5">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-2"></i>
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success_message']); // Xóa thông báo sau khi hiển thị ?>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-book-reader"></i> Danh sách Học phần</h4>
            <a href="them.php" class="btn btn-success-gradient">
                <i class="fas fa-plus"></i> Thêm Học phần mới
            </a>
        </div>

        <div class="card-body">

            <?php if (empty($monhoc_by_khoa)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Hiện chưa có môn học nào được thêm vào hệ thống.
                </div>
            <?php else: ?>
                <div id="accordion" class="accordion">
                    <?php
                    $i = 0;
                    foreach ($monhoc_by_khoa as $khoa_name => $data):
                        $collapse_id = 'collapse' . $i;
                        $is_first = ($i == 0);
                        $mon_list = $data['monhocs'];
                        $khoa_ma_slug = $data['ma'];
                        ?>
                        <div class="card">
                            <div class="card-header" id="heading<?= $i ?>">
                                <button class="btn btn-link <?= $is_first ? '' : 'collapsed' ?>" data-toggle="collapse"
                                    data-target="#<?= $collapse_id ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>"
                                    aria-controls="<?= $collapse_id ?>">
                                    <i class="fas fa-folder mr-2"></i>
                                    **<?= htmlspecialchars($khoa_name) ?>** <span
                                        class="badge badge-primary badge-pill ml-2"><?= count($mon_list) ?> Môn</span>
                                </button>
                            </div>

                            <div id="<?= $collapse_id ?>" class="collapse <?= $is_first ? 'show' : '' ?>"
                                aria-labelledby="heading<?= $i ?>" data-parent="#accordion">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 12%;"><i class="fas fa-hashtag"></i> Mã môn</th>
                                                    <th style="width: 35%;"><i class="fas fa-book-open"></i> Tên Học phần</th>
                                                    <th style="width: 10%;"><i class="fas fa-clock"></i> Số tiết</th>
                                                    <th style="width: 10%;"><i class="fas fa-graduation-cap"></i> TC</th>
                                                    <th style="width: 10%;"><i class="fas fa-calculator"></i> Hệ số</th>
                                                    <th style="width: 23%;" class="text-center"><i class="fas fa-cogs"></i> Thao
                                                        tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($mon_list as $mh): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($mh['ma_mon'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($mh['ten_mon'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($mh['so_tiet'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($mh['so_tin_chi'] ?? 0) ?></td>
                                                        <td><?= number_format($mh['he_so'] ?? 0, 1) ?></td>
                                                        <td class="text-center">
                                                            <a href="sua.php?id=<?= $mh['ma_mon'] ?>" class="btn btn-warning btn-sm"
                                                                title="Sửa">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="xoa.php?id=<?= $mh['ma_mon'] ?>"
                                                                onclick="return confirm('Xác nhận xóa môn học [<?= htmlspecialchars($mh['ma_mon']) ?> - <?= htmlspecialchars($mh['ten_mon']) ?>]?')"
                                                                class="btn btn-danger btn-sm" title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $i++;
                    endforeach;
                    ?>
                </div> <?php endif; ?>

        </div>
    </div>
</div>

<?php echo getFooter(); ?>