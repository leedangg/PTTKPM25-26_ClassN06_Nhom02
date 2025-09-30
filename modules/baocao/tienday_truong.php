<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$years = [];
for ($y = 2020; $y <= date('Y'); $y++)
    $years[] = $y;
$months = range(1, 12);

$selectedType = isset($_GET['loai']) ? $_GET['loai'] : 'thang';
$selectedYear = isset($_GET['nam']) && in_array($_GET['nam'], $years) ? intval($_GET['nam']) : date('Y');
$selectedMonth = isset($_GET['thang']) && in_array(intval($_GET['thang']), $months) ? intval($_GET['thang']) : date('n');
$selectedHk = isset($_GET['ma_hk']) ? $_GET['ma_hk'] : '';

$hockys = $conn->query("SELECT ma_hk, ten_hk, nam_hoc FROM hoc_ky ORDER BY nam_hoc DESC, ngay_bat_dau DESC")->fetchAll(PDO::FETCH_ASSOC);

$params = [];
$where = [];
if ($selectedType === 'nam') {
    $where[] = "YEAR(ld.ngay_day) = :nam";
    $params['nam'] = $selectedYear;
} elseif ($selectedType === 'thang') {
    $where[] = "YEAR(ld.ngay_day) = :nam";
    $where[] = "MONTH(ld.ngay_day) = :thang";
    $params['nam'] = $selectedYear;
    $params['thang'] = $selectedMonth;
} elseif ($selectedType === 'hocky' && $selectedHk) {
    $where[] = "ld.ma_hk = :ma_hk";
    $params['ma_hk'] = $selectedHk;
}
$whereSql = count($where) ? "WHERE " . implode(' AND ', $where) : "";

$tieuDe = 'Báo cáo tiền dạy toàn trường';

// Lấy danh sách khoa
$khoas = $conn->query("SELECT ma_khoa, ten_khoa FROM khoa ORDER BY ten_khoa")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách các môn học theo khoa
$sql = "SELECT 
            k.ten_khoa,
            mh.ten_mon,
            SUM(ld.so_tiet) as tong_so_tiet,
            COUNT(DISTINCT ld.ma_gv) as tong_gv,
            (SELECT IFNULL(SUM(lh2.so_sinh_vien),0) FROM lop_hoc lh2 
                WHERE lh2.ma_mon = mh.ma_mon " .
    ($selectedType === 'nam' ? "AND YEAR((SELECT ngay_bat_dau FROM hoc_ky WHERE ma_hk = lh2.ma_hk)) = :nam" : "") .
    ($selectedType === 'thang' ? "AND YEAR((SELECT ngay_bat_dau FROM hoc_ky WHERE ma_hk = lh2.ma_hk)) = :nam AND MONTH((SELECT ngay_bat_dau FROM hoc_ky WHERE ma_hk = lh2.ma_hk)) = :thang" : "") .
    ($selectedType === 'hocky' && $selectedHk ? "AND lh2.ma_hk = :ma_hk" : "") .
    ") as tong_sv,
            COUNT(DISTINCT lh.ma_lop) as tong_lop
        FROM lich_day ld
        JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
        LEFT JOIN khoa k ON gv.ma_khoa = k.ma_khoa
        JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
        LEFT JOIN lop_hoc lh ON lh.ma_mon = mh.ma_mon AND lh.ma_hk = ld.ma_hk
        $whereSql
        GROUP BY k.ten_khoa, mh.ten_mon
        ORDER BY k.ten_khoa, mh.ten_mon";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gom dữ liệu cho biểu đồ và bảng
$chartData = [];
$tableData = [];
foreach ($rows as $row) {
    $khoa = $row['ten_khoa'] ?: 'Khác';
    $mon = $row['ten_mon'];

    // Tính tổng tiền dạy theo công thức mới nếu lọc theo học kỳ
    if ($selectedType === 'hocky' && $selectedHk) {
        // Lấy lương học kỳ
        $stmtLuongHK = $conn->prepare("SELECT luong_hocky FROM hoc_ky WHERE ma_hk = :ma_hk");
        $stmtLuongHK->execute([':ma_hk' => $selectedHk]);
        $luong_hocky = $stmtLuongHK->fetchColumn() ?: 0;

        $sqlLuong = "SELECT 
                        SUM(ld.so_tiet * (mh.he_so + ld.he_so_lop) * :luong_hocky) as tong_luong
                    FROM lich_day ld
                    JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
                    JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
                    JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                    WHERE ld.ma_mon = (SELECT ma_mon FROM mon_hoc WHERE ten_mon = :ten_mon LIMIT 1)
                    AND gv.ma_khoa = (SELECT ma_khoa FROM khoa WHERE ten_khoa = :ten_khoa LIMIT 1)
                    AND ld.ma_hk = :ma_hk";
        $paramsLuong = [
            ':ten_mon' => $mon,
            ':ten_khoa' => $khoa,
            ':ma_hk' => $selectedHk,
            ':luong_hocky' => $luong_hocky
        ];
    } else {
        $sqlLuong = "SELECT 
                        SUM(ld.so_tiet * (mh.he_so + ld.he_so_lop) * bc.he_so * bc.he_so_luong) as tong_luong
                    FROM lich_day ld
                    JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
                    JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
                    JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                    WHERE ld.ma_mon = (SELECT ma_mon FROM mon_hoc WHERE ten_mon = :ten_mon LIMIT 1)
                    AND gv.ma_khoa = (SELECT ma_khoa FROM khoa WHERE ten_khoa = :ten_khoa LIMIT 1)";
        $paramsLuong = [
            ':ten_mon' => $mon,
            ':ten_khoa' => $khoa
        ];
        if ($selectedType === 'nam') {
            $sqlLuong .= " AND YEAR(ld.ngay_day) = :nam";
            $paramsLuong[':nam'] = $selectedYear;
        } elseif ($selectedType === 'thang') {
            $sqlLuong .= " AND YEAR(ld.ngay_day) = :nam AND MONTH(ld.ngay_day) = :thang";
            $paramsLuong[':nam'] = $selectedYear;
            $paramsLuong[':thang'] = $selectedMonth;
        }
    }
    $stmtLuong = $conn->prepare($sqlLuong);
    $stmtLuong->execute($paramsLuong);
    $tong_luong = $stmtLuong->fetchColumn();

    // Gom dữ liệu cho biểu đồ
    if (!isset($chartData[$khoa])) {
        $chartData[$khoa] = 0;
    }
    $chartData[$khoa] += (float) $tong_luong;

    // Gom dữ liệu cho bảng
    $tableData[$khoa][] = [
        'ten_mon' => $mon,
        'tong_so_tiet' => $row['tong_so_tiet'],
        'tong_gv' => $row['tong_gv'],
        'tong_sv' => $row['tong_sv'],
        'tong_lop' => $row['tong_lop'],
        'tong_tien' => $tong_luong
    ];
}

echo getHeader($tieuDe);
?>
<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header bg-gradient-info">
            <h5 class="mb-0">
                <i class="fas fa-university mr-2"></i>
                <?= $tieuDe ?>
            </h5>
        </div>
        <div class="card-body">
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
                document.getElementById('loai').addEventListener('change', function () {
                    document.getElementById('chon-thang-nam').style.display = this.value === 'thang' ? 'inline-flex' : 'none';
                    document.getElementById('chon-nam').style.display = this.value === 'nam' ? 'inline-flex' : 'none';
                    document.getElementById('chon-hocky').style.display = this.value === 'hocky' ? 'inline-flex' : 'none';
                });
            </script>

            <!-- Biểu đồ tổng tiền dạy theo khoa -->
            <div class="my-4">
                <canvas id="chartTongTien"></canvas>
            </div>

            <?php if (count($tableData) > 0): ?>
                <?php foreach ($tableData as $khoa => $monRows): ?>
                    <div class="mb-4">
                        <h5 class="text-primary"><?= htmlspecialchars($khoa) ?></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Tên môn học</th>
                                        <th>Tổng số tiết</th>
                                        <th>Tổng số giáo viên dạy</th>
                                        <th>Tổng số sinh viên</th>
                                        <th>Tổng số lớp học</th>
                                        <th>Tổng tiền dạy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sum_tiet = 0;
                                    $sum_gv = 0;
                                    $sum_sv = 0;
                                    $sum_lop = 0;
                                    $sum_tien = 0;
                                    foreach ($monRows as $row):
                                        $sum_tiet += $row['tong_so_tiet'];
                                        $sum_gv += $row['tong_gv'];
                                        $sum_sv += $row['tong_sv'];
                                        $sum_lop += $row['tong_lop'];
                                        $sum_tien += $row['tong_tien'];
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['ten_mon']) ?></td>
                                            <td class="text-right"><?= htmlspecialchars($row['tong_so_tiet']) ?></td>
                                            <td class="text-right"><?= htmlspecialchars($row['tong_gv']) ?></td>
                                            <td class="text-right"><?= htmlspecialchars($row['tong_sv']) ?></td>
                                            <td class="text-right"><?= htmlspecialchars($row['tong_lop']) ?></td>
                                            <td class="text-right"><?= number_format($row['tong_tien'] ?? 0, 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold bg-light">
                                        <td class="text-right">Tổng cộng:</td>
                                        <td class="text-right"><?= $sum_tiet ?></td>
                                        <td class="text-right"><?= $sum_gv ?></td>
                                        <td class="text-right"><?= $sum_sv ?></td>
                                        <td class="text-right"><?= $sum_lop ?></td>
                                        <td class="text-right"><?= number_format($sum_tien ?? 0, 0, ',', '.') ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning text-center">Không có dữ liệu cho lựa chọn này.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Biểu đồ tổng tiền dạy theo khoa
    const khoaLabels = <?= json_encode(array_keys($chartData)) ?>;
    const tienData = <?= json_encode(array_values($chartData)) ?>;

    // Hàm tạo màu ngẫu nhiên cho từng khoa
    function randomColor() {
        const r = Math.floor(Math.random() * 200 + 30);
        const g = Math.floor(Math.random() * 200 + 30);
        const b = Math.floor(Math.random() * 200 + 30);
        return `rgba(${r},${g},${b},0.7)`;
    }
    const bgColors = khoaLabels.map(() => randomColor());

    const ctx = document.getElementById('chartTongTien').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: khoaLabels,
            datasets: [
                {
                    label: 'Tổng tiền dạy',
                    data: tienData,
                    backgroundColor: bgColors
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: { display: true, text: 'Tổng tiền dạy theo khoa' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
<?php echo getFooter(); ?>