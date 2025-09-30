<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách khoa
$khoaStmt = $conn->query("SELECT ma_khoa, ten_khoa FROM khoa ORDER BY ten_khoa");
$khoas = $khoaStmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách bằng cấp động từ CSDL
$bangcapList = [];
$bcStmt = $conn->query("SELECT ma_bangcap, ten_bangcap FROM bangcap ORDER BY ten_bangcap");
foreach ($bcStmt->fetchAll(PDO::FETCH_ASSOC) as $bc) {
    $bangcapList[$bc['ma_bangcap']] = $bc['ten_bangcap'];
}

// Xử lý chọn khoa
$ma_khoa = isset($_GET['ma_khoa']) ? $_GET['ma_khoa'] : '';
$params = [];
$where = '';
if ($ma_khoa) {
    $where = "WHERE k.ma_khoa = :ma_khoa";
    $params[':ma_khoa'] = $ma_khoa;
}

// Lấy danh sách giáo viên theo khoa hoặc tất cả khoa
$sql = "SELECT k.ma_khoa, k.ten_khoa, g.ma_gv, g.ho_ten, g.email, g.so_dien_thoai, g.gioi_tinh, g.ma_bangcap
        FROM khoa k
        LEFT JOIN giaovien g ON k.ma_khoa = g.ma_khoa
        " . ($where ? $where : "") . "
        ORDER BY k.ten_khoa, g.ho_ten";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê số lượng giáo viên, nam, nữ, theo bằng cấp cho từng khoa hoặc tất cả
$stat = [
    'tong' => 0,
    'nam' => 0,
    'nu' => 0,
    'bangcap' => []
];
foreach ($bangcapList as $ma_bc => $ten_bc) {
    $stat['bangcap'][$ma_bc] = 0;
}

foreach ($rows as $row) {
    if (!empty($row['ma_gv'])) {
        $stat['tong']++;
        if (($row['gioi_tinh'] ?? '') === 'Nam')
            $stat['nam']++;
        if (($row['gioi_tinh'] ?? '') === 'Nữ')
            $stat['nu']++;
        $ma_bc = $row['ma_bangcap'] ?? '';
        if ($ma_bc && isset($stat['bangcap'][$ma_bc])) {
            $stat['bangcap'][$ma_bc]++;
        }
    }
}

echo getHeader("Thống kê giáo viên theo khoa");
?>

<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-chart-bar"></i> Thống kê giáo viên theo khoa
    </div>
    <div class="card-body">
        <form method="get" class="form-inline mb-3">
            <label for="ma_khoa" class="mr-2 font-weight-bold">Chọn khoa:</label>
            <select name="ma_khoa" id="ma_khoa" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">-- Tất cả khoa --</option>
                <?php foreach ($khoas as $khoa): ?>
                    <option value="<?= htmlspecialchars($khoa['ma_khoa']) ?>" <?= $ma_khoa == $khoa['ma_khoa'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($khoa['ten_khoa']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn-primary">Xem</button></noscript>
        </form>

        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <div class="alert alert-info text-center mb-0">
                    <b>Tổng giáo viên:</b> <?= $stat['tong'] ?>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="alert alert-primary text-center mb-0">
                    <b>Nam:</b> <?= $stat['nam'] ?> &nbsp; <b>Nữ:</b> <?= $stat['nu'] ?>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="alert alert-success text-center mb-0">
                    <?php foreach ($bangcapList as $ma_bc => $ten_bc): ?>
                        <b><?= htmlspecialchars($ten_bc) ?>:</b> <?= $stat['bangcap'][$ma_bc] ?> <br>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Mã GV</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Giới tính</th>
                    <th>Bằng cấp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['ma_khoa'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ten_khoa'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ma_gv'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['ho_ten'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['so_dien_thoai'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['gioi_tinh'] ?? '') ?></td>
                            <td>
                                <?php
                                $bc = $row['ma_bangcap'] ?? '';
                                echo $bc && isset($bangcapList[$bc]) ? htmlspecialchars($bangcapList[$bc]) : '';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Không có giáo viên nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo getFooter(); ?>