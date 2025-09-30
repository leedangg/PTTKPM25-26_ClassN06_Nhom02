<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách giáo viên cho filter
$giaoviens = $conn->query("SELECT ma_gv, ho_ten FROM giaovien ORDER BY ho_ten")->fetchAll();

// Filter theo giáo viên
$selected_gv = isset($_GET['ma_gv']) ? $_GET['ma_gv'] : '';

$sql = "SELECT ld.*, gv.ho_ten, mh.ten_mon 
        FROM lich_day ld
        JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
        JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
        WHERE 1=1 ";

$params = [];
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($selected_gv) {
    $sql .= " AND ld.ma_gv = :ma_gv";
    $params[':ma_gv'] = $selected_gv;
}

if ($search) {
    $sql .= " AND (gv.ho_ten LIKE :search OR gv.ma_gv LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY ld.ngay_day DESC, ld.tiet_bat_dau ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$lich_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Quản lý Buổi dạy Giảng viên");
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
        <div class="d-flex justify-content-between mb-3">
            <form class="form-inline">
                <select name="ma_gv" class="form-control mr-2" onchange="this.form.submit()">
                    <option value="">-- Chọn giáo viên --</option>
                    <?php foreach ($giaoviens as $gv): ?>
                        <option value="<?= $gv['ma_gv'] ?>" <?= $selected_gv == $gv['ma_gv'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gv['ho_ten']) ?> (<?= $gv['ma_gv'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" class="form-control mr-2" placeholder="Tìm theo mã/tên giáo viên"
                    value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </form>
        </div>

        <?php if ($selected_gv): ?>
            <div class="alert alert-info">
                Danh sách buổi dạy của giáo viên:
                <strong>
                    <?= htmlspecialchars($giaoviens[array_search($selected_gv, array_column($giaoviens, 'ma_gv'))]['ho_ten']) ?>
                </strong>
                <strong>
                    <?= htmlspecialchars($giaoviens[array_search($selected_gv, array_column($giaoviens, 'ma_gv'))]['ma_gv']) ?>
                </strong>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Mã lịch</th>
                        <th>Giảng viên</th>
                        <th>Môn học</th>
                        <th>Ngày dạy</th>
                        <th>Tiết</th>
                        <th>Phòng</th>

                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lich_days)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Không có dữ liệu</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lich_days as $lich): ?>
                            <tr>
                                <td><?= htmlspecialchars($lich['ma_lich']) ?></td>
                                <td><?= htmlspecialchars($lich['ho_ten']) ?></td>
                                <td><?= htmlspecialchars($lich['ten_mon']) ?></td>
                                <td><?= date('d/m/Y', strtotime($lich['ngay_day'])) ?></td>
                                <td><?= $lich['tiet_bat_dau'] ?> - <?= $lich['tiet_bat_dau'] + $lich['so_tiet'] - 1 ?></td>
                                <td><?= htmlspecialchars($lich['phong_hoc']) ?></td>

                                <td>
                                    <a href="sua.php?id=<?= $lich['ma_lich'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


echo getFooter();
?>