<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách lịch dạy theo role
if ($_SESSION['role'] === 'teacher') {
    $sql = "SELECT l.*, g.ho_ten, m.ten_mon 
            FROM lich_day l
            JOIN giaovien g ON l.ma_gv = g.ma_gv
            JOIN mon_hoc m ON l.ma_mon = m.ma_mon
            WHERE l.ma_gv = :ma_gv
            ORDER BY l.ngay_day DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':ma_gv' => $_SESSION['ma_gv']]);
} else {
    // SQL cho admin
    $sql = "SELECT l.*, g.ho_ten, m.ten_mon 
            FROM lich_day l
            JOIN giaovien g ON l.ma_gv = g.ma_gv
            JOIN mon_hoc m ON l.ma_mon = m.ma_mon
            ORDER BY l.ngay_day DESC";
    $stmt = $conn->query($sql);
}
$lichs = $stmt->fetchAll();

echo getHeader("Quản lý Lịch dạy");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="card">
    <div class="card-body">


        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Mã lịch</th>
                    <th>Giảng viên</th>
                    <th>Môn học</th>
                    <th>Lớp</th>
                    <th>Số SV</th>
                    <th>Ngày dạy</th>
                    <th>Tiết</th>
                    <th>Phòng</th>

                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lichs as $lich): ?>
                    <tr>
                        <td><?= htmlspecialchars($lich['ma_lich'] ?? '') ?></td>
                        <td><?= htmlspecialchars($lich['ho_ten'] ?? '') ?></td>
                        <td><?= htmlspecialchars($lich['ten_mon'] ?? '') ?></td>
                        <td><?= htmlspecialchars($lich['ten_lop_hoc'] ?? '') ?></td>
                        <td><?= $lich['so_sinh_vien'] ?? '40' ?></td>
                        <td><?= $lich['ngay_day'] ? date('d/m/Y', strtotime($lich['ngay_day'])) : '' ?></td>
                        <td><?= $lich['tiet_bat_dau'] ?> - <?= ($lich['tiet_bat_dau'] + $lich['so_tiet'] - 1) ?></td>
                        <td><?= htmlspecialchars($lich['phong_hoc'] ?? '') ?></td>

                        <td>
                            <a href="sua.php?id=<?= $lich['ma_lich'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php

echo getFooter();
?>