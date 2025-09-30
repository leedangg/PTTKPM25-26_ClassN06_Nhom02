<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

function taoMaHK($conn)
{
    $sql = "SELECT ma_hk FROM hoc_ky ORDER BY ma_hk DESC LIMIT 1";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['ma_hk'];
        $number = intval(substr($lastCode, 2)) + 1;
        return 'HK' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    return 'HK001';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $ma_hk = taoMaHK($conn);
        $sql = "INSERT INTO hoc_ky (ma_hk, ten_hk, ngay_bat_dau, ngay_ket_thuc, nam_hoc) 
                VALUES (:ma_hk, :ten_hk, :ngay_bat_dau, :ngay_ket_thuc, :nam_hoc)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ma_hk' => $ma_hk,
            ':ten_hk' => $_POST['ten_hk'],
            ':ngay_bat_dau' => $_POST['ngay_bat_dau'],
            ':ngay_ket_thuc' => $_POST['ngay_ket_thuc'],
            ':nam_hoc' => $_POST['nam_hoc']
        ]);

        header("Location: hocky.php");
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách học kỳ
$sql = "SELECT * FROM hoc_ky ORDER BY nam_hoc DESC, ngay_bat_dau DESC";
$stmt = $conn->query($sql);
$hockys = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Quản lý Học kỳ");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="card mb-4">
    <div class="card-body">
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tên học kỳ</label>
                        <input type="text" name="ten_hk" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Năm học</label>
                        <input type="text" name="nam_hoc" class="form-control" placeholder="VD: 2023-2024" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ngày bắt đầu</label>
                        <input type="date" name="ngay_bat_dau" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ngày kết thúc</label>
                        <input type="date" name="ngay_ket_thuc" class="form-control" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Thêm học kỳ</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Danh sách học kỳ</h5>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Mã HK</th>
                    <th>Tên học kỳ</th>
                    <th>Năm học</th>
                    <th>Thời gian</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hockys as $hk): ?>
                    <tr>
                        <td><?= $hk['ma_hk'] ?></td>
                        <td><?= htmlspecialchars($hk['ten_hk']) ?></td>
                        <td><?= htmlspecialchars($hk['nam_hoc']) ?></td>
                        <td>
                            <?= date('d/m/Y', strtotime($hk['ngay_bat_dau'])) ?> -
                            <?= date('d/m/Y', strtotime($hk['ngay_ket_thuc'])) ?>
                        </td>
                        <td>
                            <a href="lichday_dinhky.php?hk=<?= $hk['ma_hk'] ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-calendar"></i> Lập lịch
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo getFooter(); ?>