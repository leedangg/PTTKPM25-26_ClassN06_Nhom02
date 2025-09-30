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

if (!$lichday) {
    die('Không tìm thấy lịch dạy');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sửa lỗi dấu phẩy dư trước WHERE trong câu lệnh SQL
    $sql = "UPDATE lich_day SET 
        ma_gv = :ma_gv,
        ma_mon = :ma_mon,
        ten_lop_hoc = :ten_lop_hoc,
        ngay_day = :ngay_day,
        so_tiet = :so_tiet,
        phong_hoc = :phong_hoc,
        tiet_bat_dau = :tiet_bat_dau
        WHERE ma_lich = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':ma_gv' => $_POST['ma_gv'],
        ':ma_mon' => $_POST['ma_mon'],
        ':ten_lop_hoc' => $_POST['ten_lop_hoc'],
        ':ngay_day' => $_POST['ngay_day'],
        ':so_tiet' => $_POST['so_tiet'],
        ':phong_hoc' => $_POST['phong'],
        ':tiet_bat_dau' => $_POST['tiet_bat_dau'],
        ':id' => $id
    ]);
    header("Location: index.php");
    exit();
}

echo getHeader("Sửa Lịch Dạy");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sửa Lịch Dạy</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="container mt-4">
        <div class="card shadow-lg">
            <div class="card-header bg-gradient-info">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Sửa thông tin Lịch Dạy
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="ma_lich">
                                <i class="fas fa-key mr-1"></i>
                                Mã lịch
                            </label>
                            <input type="text" id="ma_lich" name="ma_lich" class="form-control"
                                value="<?= htmlspecialchars($lichday['ma_lich']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="ma_gv">
                                <i class="fas fa-user mr-1"></i>
                                Mã GV <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ma_gv" name="ma_gv" class="form-control"
                                value="<?= htmlspecialchars($lichday['ma_gv']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="ma_mon">
                                <i class="fas fa-book mr-1"></i>
                                Mã môn <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ma_mon" name="ma_mon" class="form-control"
                                value="<?= htmlspecialchars($lichday['ma_mon']) ?>" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="ten_lop_hoc">
                                <i class="fas fa-users mr-1"></i>
                                Lớp học <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ten_lop_hoc" name="ten_lop_hoc" class="form-control"
                                value="<?= htmlspecialchars($lichday['ten_lop_hoc']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="ngay_day">
                                <i class="fas fa-calendar-day mr-1"></i>
                                Ngày dạy <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="ngay_day" name="ngay_day" class="form-control"
                                value="<?= htmlspecialchars($lichday['ngay_day']) ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="so_tiet">
                                <i class="fas fa-clock mr-1"></i>
                                Số tiết <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="so_tiet" name="so_tiet" class="form-control"
                                value="<?= htmlspecialchars($lichday['so_tiet']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="phong">
                                <i class="fas fa-clock mr-1"></i>
                                Phòng <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="phong" name="phong" class="form-control"
                                value="<?= htmlspecialchars($lichday['phong_hoc'] ?? '') ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="tiet_bat_dau">
                                <i class="fas fa-clock mr-1"></i>
                                Tiết bắt đầu <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="tiet_bat_dau" name="tiet_bat_dau" class="form-control"
                                value="<?= htmlspecialchars($lichday['tiet_bat_dau'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group mt-4 text-right">
                        <a href="index.php" class="btn btn-secondary mr-2">
                            <i class="fas fa-times mr-1"></i>
                            Hủy bỏ
                        </a>
                        <button type="reset" class="btn btn-outline-secondary mr-2">
                            <i class="fas fa-undo mr-1"></i>
                            Làm mới
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Bootstrap form validation
        (function () {
            'use strict';
            window.addEventListener('load', function () {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function (form) {
                    form.addEventListener('submit', function (event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>

</html>