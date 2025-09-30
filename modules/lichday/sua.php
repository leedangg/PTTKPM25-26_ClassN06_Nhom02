<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

// Lấy thông tin lịch dạy
$stmt = $conn->prepare("SELECT * FROM lich_day WHERE ma_lich = ?");
$stmt->execute([$id]);
$lichday = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách giáo viên và môn học
$stmt_gv = $conn->query("SELECT ma_gv, ho_ten FROM giaovien ORDER BY ho_ten");
$giaoviens = $stmt_gv->fetchAll(PDO::FETCH_ASSOC);

$stmt_mon = $conn->query("SELECT ma_mon, ten_mon FROM mon_hoc ORDER BY ten_mon");
$mon_hocs = $stmt_mon->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "UPDATE lich_day SET 
                ma_gv = :ma_gv,
                ma_mon = :ma_mon,
                ngay_day = :ngay_day,
                tiet_bat_dau = :tiet_bat_dau,
                so_tiet = :so_tiet,
                phong_hoc = :phong_hoc,
                trang_thai = :trang_thai,
                ghi_chu = :ghi_chu,
                ten_lop = :ten_lop,
                so_sinh_vien = :so_sinh_vien
                WHERE ma_lich = :ma_lich";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ma_gv' => $_POST['ma_gv'],
            ':ma_mon' => $_POST['ma_mon'],
            ':ngay_day' => $_POST['ngay_day'],
            ':tiet_bat_dau' => $_POST['tiet_bat_dau'],
            ':so_tiet' => $_POST['so_tiet'],
            ':phong_hoc' => $_POST['phong_hoc'],
            ':trang_thai' => $_POST['trang_thai'],
            ':ghi_chu' => $_POST['ghi_chu'],
            ':ten_lop' => $_POST['ten_lop'],
            ':so_sinh_vien' => $_POST['so_sinh_vien'],
            ':ma_lich' => $id
        ]);

        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

echo getHeader("Sửa Lịch dạy");
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
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Giảng viên</label>
                        <select name="ma_gv" class="form-control" readonly>
                            <?php foreach ($giaoviens as $gv): ?>
                                <option value="<?= $gv['ma_gv'] ?>" <?= $lichday['ma_gv'] == $gv['ma_gv'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($gv['ho_ten']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Môn học</label>
                        <select name="ma_mon" class="form-control" readonly>
                            <?php foreach ($mon_hocs as $mon): ?>
                                <option value="<?= $mon['ma_mon'] ?>" <?= $lichday['ma_mon'] == $mon['ma_mon'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mon['ten_mon']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ngày dạy</label>
                        <input type="date" name="ngay_day" class="form-control" value="<?= $lichday['ngay_day'] ?>"
                            required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tiết bắt đầu</label>
                        <input type="number" name="tiet_bat_dau" class="form-control"
                            value="<?= $lichday['tiet_bat_dau'] ?>" min="1" max="10" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Số tiết</label>
                        <input type="number" name="so_tiet" class="form-control" value="<?= $lichday['so_tiet'] ?>"
                            min="1" max="6" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Phòng học</label>
                        <input type="text" name="phong_hoc" class="form-control"
                            value="<?= htmlspecialchars($lichday['phong_hoc']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tên lớp</label>
                        <input type="text" name="ten_lop" class="form-control"
                            value="<?= htmlspecialchars($lichday['ten_lop']) ?>" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Số sinh viên</label>
                        <input type="number" name="so_sinh_vien" class="form-control"
                            value="<?= $lichday['so_sinh_vien'] ?>" min="1" readonly>
                    </div>
                </div>
            </div>



            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="index.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>

<?php echo getFooter(); ?>