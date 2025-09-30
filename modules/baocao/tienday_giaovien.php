<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();


// Lấy tất cả năm có thể có trong bảng bang_luong và lich_day (không phụ thuộc dữ liệu hiện tại)
$years = [];
for ($y = 2020; $y <= date('Y'); $y++) { // Có thể điều chỉnh mốc năm nhỏ nhất
    $years[] = $y;
}
$months = range(1, 12);

// Lấy danh sách học kỳ
$hockys = $conn->query("SELECT ma_hk, ten_hk, nam_hoc FROM hoc_ky ORDER BY nam_hoc DESC, ngay_bat_dau DESC")->fetchAll(PDO::FETCH_ASSOC);

$selectedType = isset($_GET['loai']) ? $_GET['loai'] : 'thang';
$selectedYear = isset($_GET['nam']) && in_array($_GET['nam'], $years) ? intval($_GET['nam']) : date('Y');
$selectedMonth = isset($_GET['thang']) && in_array(intval($_GET['thang']), $months) ? intval($_GET['thang']) : date('n');
$selectedHk = isset($_GET['ma_hk']) ? $_GET['ma_hk'] : '';

// Chuẩn bị dữ liệu
$rows = [];
$tieuDe = '';

if ($selectedType === 'nam') {
    // Theo năm: tính chi tiết lương từng môn cho từng giáo viên trong năm
    $sqlGv = "SELECT 
                g.ma_gv, g.ho_ten, k.ten_khoa, bc.he_so as he_so_gv
            FROM giaovien g
            LEFT JOIN khoa k ON g.ma_khoa = k.ma_khoa
            LEFT JOIN bangcap bc ON g.ma_bangcap = bc.ma_bangcap";
    $giaoviens = $conn->query($sqlGv)->fetchAll(PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($giaoviens as $teacherInfo) {
        // Lấy lương học kỳ nếu có
        $sqlLuongHK = "SELECT luong_hocky FROM hoc_ky WHERE nam_hoc = :nam_hoc ORDER BY ngay_bat_dau DESC LIMIT 1";
        $stmtLuongHK = $conn->prepare($sqlLuongHK);
        $stmtLuongHK->execute([':nam_hoc' => $selectedYear . '-' . ($selectedYear + 1)]);
        $luong_hocky = $stmtLuongHK->fetchColumn() ?: 0;

        $sql2 = "SELECT 
                mh.ten_mon,
                ld.ten_lop_hoc,
                COUNT(ld.ma_lich) as so_buoi_day,
                SUM(ld.so_tiet) as tong_so_tiet,
                mh.he_so as he_so_mon,
                ld.he_so_lop,
                SUM(
                    ld.so_tiet * 
                    (mh.he_so + ld.he_so_lop) * 
                    :he_so_gv * 
                    :luong_hocky
                ) as luong_mon
                FROM lich_day ld
                JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
                JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
                JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                WHERE ld.ma_gv = :ma_gv 
                AND YEAR(ld.ngay_day) = :nam
                GROUP BY mh.ma_mon, ld.ten_lop_hoc
                ORDER BY mh.ten_mon, ld.ten_lop_hoc";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([
            ':ma_gv' => $teacherInfo['ma_gv'],
            ':nam' => $selectedYear,
            ':he_so_gv' => $teacherInfo['he_so_gv'] ?? 0,
            ':luong_hocky' => $luong_hocky
        ]);
        $subjectDetails = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $tongTien = 0;
        foreach ($subjectDetails as $subject) {
            $tongTien += $subject['luong_mon'];
        }

        if ($tongTien > 0) {
            $rows[] = [
                'ma_gv' => $teacherInfo['ma_gv'],
                'ho_ten' => $teacherInfo['ho_ten'],
                'ten_khoa' => $teacherInfo['ten_khoa'],
                'luong_hocky' => $luong_hocky,
                'he_so_gv' => $teacherInfo['he_so_gv'],
                'chi_tiet_mon' => $subjectDetails,
                'thuc_lanh' => $tongTien,
                'so_tiet' => array_sum(array_column($subjectDetails, 'tong_so_tiet'))
            ];
        }
    }
    $tieuDe = "Báo cáo tiền dạy giáo viên theo năm $selectedYear";
} elseif ($selectedType === 'thang') {
    // Theo tháng: tính chi tiết lương từng môn cho từng giáo viên trong tháng
    $sqlGv = "SELECT 
                g.ma_gv, g.ho_ten, k.ten_khoa, bc.he_so as he_so_gv
            FROM giaovien g
            LEFT JOIN khoa k ON g.ma_khoa = k.ma_khoa
            LEFT JOIN bangcap bc ON g.ma_bangcap = bc.ma_bangcap";
    $giaoviens = $conn->query($sqlGv)->fetchAll(PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($giaoviens as $teacherInfo) {
        $sqlLuongHK = "SELECT luong_hocky FROM hoc_ky WHERE nam_hoc = :nam_hoc ORDER BY ngay_bat_dau DESC LIMIT 1";
        $stmtLuongHK = $conn->prepare($sqlLuongHK);
        $stmtLuongHK->execute([':nam_hoc' => $selectedYear . '-' . ($selectedYear + 1)]);
        $luong_hocky = $stmtLuongHK->fetchColumn() ?: 0;

        $sql2 = "SELECT 
                mh.ten_mon,
                ld.ten_lop_hoc,
                COUNT(ld.ma_lich) as so_buoi_day,
                SUM(ld.so_tiet) as tong_so_tiet,
                mh.he_so as he_so_mon,
                ld.he_so_lop,
                SUM(
                    ld.so_tiet * 
                    (mh.he_so + ld.he_so_lop) * 
                    :he_so_gv * 
                    :luong_hocky
                ) as luong_mon
                FROM lich_day ld
                JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
                JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
                JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                WHERE ld.ma_gv = :ma_gv 
                AND MONTH(ld.ngay_day) = :thang
                AND YEAR(ld.ngay_day) = :nam
                GROUP BY mh.ma_mon, ld.ten_lop_hoc
                ORDER BY mh.ten_mon, ld.ten_lop_hoc";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([
            ':ma_gv' => $teacherInfo['ma_gv'],
            ':thang' => $selectedMonth,
            ':nam' => $selectedYear,
            ':he_so_gv' => $teacherInfo['he_so_gv'] ?? 0,
            ':luong_hocky' => $luong_hocky
        ]);
        $subjectDetails = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $tongTien = 0;
        foreach ($subjectDetails as $subject) {
            $tongTien += $subject['luong_mon'];
        }

        if ($tongTien > 0) {
            $rows[] = [
                'ma_gv' => $teacherInfo['ma_gv'],
                'ho_ten' => $teacherInfo['ho_ten'],
                'ten_khoa' => $teacherInfo['ten_khoa'],
                'luong_hocky' => $luong_hocky,
                'he_so_gv' => $teacherInfo['he_so_gv'],
                'chi_tiet_mon' => $subjectDetails,
                'thuc_lanh' => $tongTien,
                'so_tiet' => array_sum(array_column($subjectDetails, 'tong_so_tiet'))
            ];
        }
    }
    $tieuDe = "Báo cáo tiền dạy giáo viên theo tháng $selectedMonth/$selectedYear";
} elseif ($selectedType === 'hocky' && $selectedHk) {
    // Theo học kỳ: lấy chi tiết lương từng môn cho từng giáo viên
    $sqlGv = "SELECT 
                g.ma_gv, g.ho_ten, k.ten_khoa, bc.he_so as he_so_gv
            FROM giaovien g
            LEFT JOIN khoa k ON g.ma_khoa = k.ma_khoa
            LEFT JOIN bangcap bc ON g.ma_bangcap = bc.ma_bangcap";
    $giaoviens = $conn->query($sqlGv)->fetchAll(PDO::FETCH_ASSOC);

    $rows = [];
    foreach ($giaoviens as $teacherInfo) {
        // Lấy lương học kỳ theo mã học kỳ
        $sqlLuongHK = "SELECT luong_hocky FROM hoc_ky WHERE ma_hk = :ma_hk";
        $stmtLuongHK = $conn->prepare($sqlLuongHK);
        $stmtLuongHK->execute([':ma_hk' => $selectedHk]);
        $luong_hocky = $stmtLuongHK->fetchColumn() ?: 0;

        $sql2 = "SELECT 
                mh.ten_mon,
                ld.ten_lop_hoc,
                COUNT(ld.ma_lich) as so_buoi_day,
                SUM(ld.so_tiet) as tong_so_tiet,
                mh.he_so as he_so_mon,
                ld.he_so_lop,
                SUM(
                    ld.so_tiet * 
                    (mh.he_so + ld.he_so_lop) * 
                    :he_so_gv * 
                    :luong_hocky
                ) as luong_mon
                FROM lich_day ld
                JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
                JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
                JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                WHERE ld.ma_gv = :ma_gv 
                AND ld.ma_hk = :ma_hk
                GROUP BY mh.ma_mon, ld.ten_lop_hoc
                ORDER BY mh.ten_mon, ld.ten_lop_hoc";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([
            ':ma_gv' => $teacherInfo['ma_gv'],
            ':ma_hk' => $selectedHk,
            ':he_so_gv' => $teacherInfo['he_so_gv'] ?? 0,
            ':luong_hocky' => $luong_hocky
        ]);
        $subjectDetails = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $tongTien = 0;
        foreach ($subjectDetails as $subject) {
            $tongTien += $subject['luong_mon'];
        }

        if ($tongTien > 0) {
            $rows[] = [
                'ma_gv' => $teacherInfo['ma_gv'],
                'ho_ten' => $teacherInfo['ho_ten'],
                'ten_khoa' => $teacherInfo['ten_khoa'],
                'luong_hocky' => $luong_hocky,
                'he_so_gv' => $teacherInfo['he_so_gv'],
                'chi_tiet_mon' => $subjectDetails,
                'thuc_lanh' => $tongTien,
                'so_tiet' => array_sum(array_column($subjectDetails, 'tong_so_tiet'))
            ];
        }
    }
    // Lấy tên học kỳ
    $hkinfo = null;
    foreach ($hockys as $hk) {
        if ($hk['ma_hk'] == $selectedHk) {
            $hkinfo = $hk;
            break;
        }
    }
    $tieuDe = "Báo cáo tiền dạy giáo viên theo học kỳ " . ($hkinfo ? htmlspecialchars($hkinfo['ten_hk'] . ' - ' . $hkinfo['nam_hoc']) : $selectedHk);
} elseif ($selectedType === 'hocky' && !$selectedHk) {
    $tieuDe = "Báo cáo tiền dạy giáo viên theo học kỳ";
    $error = "Hãy chọn học kỳ!";
}

echo getHeader($tieuDe);
?>
<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header bg-gradient-info">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice-dollar mr-2"></i>
                <?= $tieuDe ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
                <script>
                    alert("<?= str_replace('"', '\"', $error) ?>");
                </script>
            <?php endif; ?>
            <form method="get" class="form-inline mb-4 needs-validation" novalidate>
                <label class="mr-2 font-weight-bold" for="loai">Xem theo:</label>
                <select name="loai" id="loai" class="form-control mr-3" onchange="this.form.submit()">
                    <option value="thang" <?= $selectedType == 'thang' ? 'selected' : '' ?>>Tháng</option>
                    <option value="nam" <?= $selectedType == 'nam' ? 'selected' : '' ?>>Năm</option>
                    <option value="hocky" <?= $selectedType == 'hocky' ? 'selected' : '' ?>>Học kỳ</option>
                </select>
                <div id="chon-thang-nam" style="display:<?= $selectedType == 'thang' ? 'inline-flex' : 'none' ?>;">
                    <label class="mr-2 font-weight-bold" for="thang">Tháng:</label>
                    <select name="thang" id="thang" class="form-control mr-3">
                        <?php foreach ($months as $m): ?>
                            <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="mr-2 font-weight-bold" for="nam">Năm:</label>
                    <select name="nam" id="nam" class="form-control mr-3">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="chon-nam" style="display:<?= $selectedType == 'nam' ? 'inline-flex' : 'none' ?>;">
                    <label class="mr-2 font-weight-bold" for="nam">Năm:</label>
                    <select name="nam" id="nam2" class="form-control mr-3">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="chon-hocky" style="display:<?= $selectedType == 'hocky' ? 'inline-flex' : 'none' ?>;">
                    <label class="mr-2 font-weight-bold" for="ma_hk">Học kỳ:</label>
                    <select name="ma_hk" id="ma_hk" class="form-control mr-3" onchange="this.form.submit()">
                        <option value="">-- Chọn học kỳ --</option>
                        <?php foreach ($hockys as $hk): ?>
                            <option value="<?= $hk['ma_hk'] ?>" <?= $selectedHk == $hk['ma_hk'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($hk['ten_hk']) ?> - <?= htmlspecialchars($hk['nam_hoc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary ml-2">
                    <i class="fas fa-search mr-1"></i> Xem báo cáo
                </button>
            </form>
            <script>
                // Hiển thị các filter phù hợp với loại báo cáo
                document.getElementById('loai').addEventListener('change', function () {
                    document.getElementById('chon-thang-nam').style.display = this.value === 'thang' ? 'inline-flex' : 'none';
                    document.getElementById('chon-nam').style.display = this.value === 'nam' ? 'inline-flex' : 'none';
                    document.getElementById('chon-hocky').style.display = this.value === 'hocky' ? 'inline-flex' : 'none';
                });
                // Tự động submit khi chọn học kỳ
                document.getElementById('ma_hk').addEventListener('change', function () {
                    if (document.getElementById('loai').value === 'hocky') {
                        this.form.submit();
                    }
                });
            </script>
            <?php if ($selectedType !== 'hocky' || ($selectedType === 'hocky' && $selectedHk)): ?>
                <?php if (count($rows) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Mã GV</th>
                                    <th>Họ tên</th>
                                    <th>Khoa</th>
                                    <th>Số tiết</th>
                                    <?php if ($selectedType !== 'nam'): ?>
                                        <th>lương Học kỳ</th>
                                    <?php endif; ?>
                                    <th>Tiền dạy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $tong_tien = 0; ?>
                                <?php foreach ($rows as $row):
                                    $tong_tien += $row['thuc_lanh']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['ma_gv'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['ho_ten'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['ten_khoa'] ?? '') ?></td>
                                        <td class="text-right"><?= htmlspecialchars($row['so_tiet'] ?? '') ?></td>
                                        <?php if ($selectedType !== 'nam'): ?>
                                            <td class="text-right"><?= htmlspecialchars($row['luong_hocky'] ?? '') ?></td>
                                        <?php endif; ?>
                                        <td class="text-right"><?= number_format($row['thuc_lanh'] ?? 0, 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">Không có dữ liệu cho lựa chọn này.</div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php echo getFooter(); ?>