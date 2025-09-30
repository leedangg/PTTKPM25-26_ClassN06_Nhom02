<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách năm có lương
$years = $conn->query("SELECT DISTINCT nam FROM bang_luong ORDER BY nam DESC")->fetchAll(PDO::FETCH_COLUMN);
$months = range(1, 12);

// Lấy filter
$year = isset($_GET['nam']) ? intval($_GET['nam']) : (date('Y'));
$month = isset($_GET['thang']) ? intval($_GET['thang']) : (date('n'));

// Lấy danh sách lương giáo viên theo tháng/năm
$sql = "SELECT g.ma_gv, g.ho_ten, k.ten_khoa, b.thang, b.nam, b.so_tiet, b.he_so_luong, b.thuc_lanh, b.trang_thai
        FROM bang_luong b
        JOIN giaovien g ON b.ma_gv = g.ma_gv
        LEFT JOIN khoa k ON g.ma_khoa = k.ma_khoa
        WHERE b.nam = :nam AND b.thang = :thang
        ORDER BY k.ten_khoa, g.ho_ten";
$stmt = $conn->prepare($sql);
$stmt->execute(['nam' => $year, 'thang' => $month]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Thống kê lương giáo viên");
?>

<div class="card mt-4">
    <div class="card-header bg-success text-white">
        <i class="fas fa-money-bill-wave"></i> Thống kê lương giáo viên
    </div>
    <div class="card-body">
        <form method="get" class="form-inline mb-3">
            <label class="mr-2">Năm:</label>
            <select name="nam" class="form-control mr-3" onchange="this.form.submit()">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
            <label class="mr-2">Tháng:</label>
            <select name="thang" class="form-control mr-3" onchange="this.form.submit()">
                <?php foreach ($months as $m): ?>
                    <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= $m ?></option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit" class="btn btn-primary">Xem</button></noscript>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Mã GV</th>
                        <th>Họ tên</th>
                        <th>Khoa</th>
                        <th>Tháng</th>
                        <th>Năm</th>
                        <th>Số tiết</th>
                        <th>Hệ số lương</th>
                        <th>Thực lãnh</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tong_luong = 0;
                    foreach ($rows as $row):
                        $tong_luong += $row['thuc_lanh'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['ma_gv']) ?></td>
                            <td><?= htmlspecialchars($row['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($row['ten_khoa']) ?></td>
                            <td><?= htmlspecialchars($row['thang']) ?></td>
                            <td><?= htmlspecialchars($row['nam']) ?></td>
                            <td class="text-right"><?= htmlspecialchars($row['so_tiet']) ?></td>
                            <td class="text-right"><?= htmlspecialchars($row['he_so_luong']) ?></td>
                            <td class="text-right"><?= number_format($row['thuc_lanh'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['trang_thai']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="7" class="text-right">Tổng thực lãnh:</td>
                        <td class="text-right"><?= number_format($tong_luong, 0, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <?php if (empty($rows)): ?>
                <div class="alert alert-warning text-center">Không có dữ liệu lương cho tháng/năm này.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php echo getFooter(); ?>