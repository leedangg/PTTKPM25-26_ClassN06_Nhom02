<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Get current semester
$sql = "SELECT * FROM hoc_ky WHERE trang_thai = 'Đang diễn ra' LIMIT 1";
$current_hk = $conn->query($sql)->fetch();

// Get all semesters
$sql = "SELECT * FROM hoc_ky ORDER BY nam_hoc DESC, ngay_bat_dau DESC";
$hockys = $conn->query($sql)->fetchAll();


$sql = "SELECT * FROM khoa ORDER BY ten_khoa";
$khoas = $conn->query($sql)->fetchAll();

// Xử lý lọc theo khoa (thêm tùy chọn 'Tất cả')
$selectedKhoa = isset($_GET['ma_khoa']) ? $_GET['ma_khoa'] : 'all';

echo getHeader("Thống kê số lớp học phần");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Thống kê số lớp học phần</h5>
    </div>

    <div class="card-body">
        <!-- Filter form -->
        <form id="filter-form" class="mb-4" method="get">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-building"></i> Khoa</label>
                        <select name="ma_khoa" id="ma_khoa" class="form-control" required onchange="this.form.submit()">
                            <option value="all" <?= $selectedKhoa == 'all' ? 'selected' : '' ?>>Tất cả các khoa</option>
                            <?php foreach ($khoas as $khoa): ?>
                                <option value="<?= $khoa['ma_khoa'] ?>" <?= $selectedKhoa == $khoa['ma_khoa'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($khoa['ten_khoa']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Học kỳ</label>
                        <select name="ma_hk" id="ma_hk" class="form-control" required onchange="this.form.submit()">
                            <?php foreach ($hockys as $hk): ?>
                                <option value="<?= $hk['ma_hk'] ?>" <?= ($current_hk && $current_hk['ma_hk'] == $hk['ma_hk'] && (!isset($_GET['ma_hk']) || $_GET['ma_hk'] == $hk['ma_hk'])) ? 'selected' : (isset($_GET['ma_hk']) && $_GET['ma_hk'] == $hk['ma_hk'] ? 'selected' : '') ?>>
                                    <?= $hk['ten_hk'] ?> - <?= $hk['nam_hoc'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Xem thống kê
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Results table -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>Mã môn</th>
                        <th>Tên môn học</th>
                        <th class="text-center">Số lớp</th>
                        <th class="text-center">Tổng số sinh viên</th>
                        <th class="text-center">TB SV/Lớp</th>
                        <th>Danh sách lớp</th>
                        <th>Khoa</th>
                    </tr>
                </thead>
                <tbody id="results">
                    <?php
                    // Lấy dữ liệu thống kê theo khoa và học kỳ đã chọn
                    $ma_hk = isset($_GET['ma_hk']) ? $_GET['ma_hk'] : ($current_hk['ma_hk'] ?? '');
                    if ($selectedKhoa == 'all') {
                        // Thống kê toàn bộ các khoa
                        $sql = "SELECT 
                                    mh.ma_mon,
                                    mh.ten_mon,
                                    k.ten_khoa,
                                    COUNT(DISTINCT lh.ma_lop) as so_lop,
                                    SUM(lh.so_sinh_vien) as tong_sv,
                                    GROUP_CONCAT(DISTINCT lh.ten_lop ORDER BY lh.ten_lop SEPARATOR ', ') as danh_sach_lop
                                FROM lop_hoc lh
                                JOIN mon_hoc mh ON lh.ma_mon = mh.ma_mon
                                JOIN khoa k ON mh.ma_khoa = k.ma_khoa
                                WHERE lh.ma_hk = :ma_hk
                                GROUP BY mh.ma_mon, mh.ten_mon, k.ten_khoa
                                ORDER BY k.ten_khoa, mh.ten_mon";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([
                            ':ma_hk' => $ma_hk
                        ]);
                    } else {
                        // Thống kê theo khoa đã chọn
                        $sql = "SELECT 
                                    mh.ma_mon,
                                    mh.ten_mon,
                                    k.ten_khoa,
                                    COUNT(DISTINCT lh.ma_lop) as so_lop,
                                    SUM(lh.so_sinh_vien) as tong_sv,
                                    GROUP_CONCAT(DISTINCT lh.ten_lop ORDER BY lh.ten_lop SEPARATOR ', ') as danh_sach_lop
                                FROM lop_hoc lh
                                JOIN mon_hoc mh ON lh.ma_mon = mh.ma_mon
                                JOIN khoa k ON mh.ma_khoa = k.ma_khoa
                                WHERE lh.ma_hk = :ma_hk AND mh.ma_khoa = :ma_khoa
                                GROUP BY mh.ma_mon, mh.ten_mon, k.ten_khoa
                                ORDER BY k.ten_khoa, mh.ten_mon";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([
                            ':ma_hk' => $ma_hk,
                            ':ma_khoa' => $selectedKhoa
                        ]);
                    }
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $tong_lop = 0;
                    $tong_sv = 0;
                    foreach ($rows as $row):
                        $tong_lop += $row['so_lop'];
                        $tong_sv += $row['tong_sv'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['ma_mon']) ?></td>
                            <td><?= htmlspecialchars($row['ten_mon']) ?></td>
                            <td class="text-center"><?= $row['so_lop'] ?></td>
                            <td class="text-center"><?= $row['tong_sv'] ?></td>
                            <td class="text-center">
                                <?= $row['so_lop'] > 0 ? number_format($row['tong_sv'] / $row['so_lop'], 1) : 0 ?></td>
                            <td><small><?= htmlspecialchars($row['danh_sach_lop'] ?: 'Không có') ?></small></td>
                            <td><?= htmlspecialchars($row['ten_khoa']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="2" class="text-right">Tổng cộng:</td>
                        <td class="text-center" id="total-classes"><?= $tong_lop ?></td>
                        <td class="text-center" id="total-students"><?= $tong_sv ?></td>
                        <td class="text-center" id="avg-students">
                            <?= $tong_lop > 0 ? number_format($tong_sv / $tong_lop, 1) : 0 ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php echo getFooter(); ?>