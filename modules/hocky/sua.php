<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : header('Location: index.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if ($_POST['ngay_ket_thuc'] <= $_POST['ngay_bat_dau']) {
            throw new Exception("Ngày kết thúc phải sau ngày bắt đầu!");
        }

        // Kiểm tra trùng thời gian học kỳ khác
        $sqlCheck = "SELECT COUNT(*) FROM hoc_ky 
            WHERE ma_hk != :ma_hk
            AND (
                (ngay_bat_dau <= :ngay_ket_thuc AND ngay_ket_thuc >= :ngay_bat_dau)
            )";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([
            ':ma_hk' => $id,
            ':ngay_bat_dau' => $_POST['ngay_bat_dau'],
            ':ngay_ket_thuc' => $_POST['ngay_ket_thuc']
        ]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("Lỗi: Trùng thời gian với học kỳ khác!");
        }

        $sql = "UPDATE hoc_ky SET 
                ten_hk = :ten_hk,
                nam_hoc = :nam_hoc,
                luong_hocky = :luong_hocky,
                ngay_bat_dau = :ngay_bat_dau,
                ngay_ket_thuc = :ngay_ket_thuc
                WHERE ma_hk = :ma_hk";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ten_hk' => $_POST['ten_hk'],
            ':nam_hoc' => $_POST['nam_hoc'],
            ':luong_hocky' => $_POST['luong_hocky'],
            ':ngay_bat_dau' => $_POST['ngay_bat_dau'],
            ':ngay_ket_thuc' => $_POST['ngay_ket_thuc'],
            ':ma_hk' => $id
        ]);

        header('Location: index.php?success=2');
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$stmt = $conn->prepare("SELECT * FROM hoc_ky WHERE ma_hk = ?");
$stmt->execute([$id]);
$hocky = $stmt->fetch();

if (!$hocky) {
    header('Location: index.php');
    exit();
}

echo getHeader("Sửa kỳ học");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sửa Học kỳ</title>
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
                    Sửa thông tin Học kỳ
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
                            <label for="ma_hk">
                                <i class="fas fa-key mr-1"></i>
                                Mã học kỳ <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ma_hk" name="ma_hk" class="form-control"
                                value="<?= htmlspecialchars($hocky['ma_hk']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="ten_hk">
                                <i class="fas fa-calendar mr-1"></i>
                                Tên học kỳ <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ten_hk" name="ten_hk" class="form-control"
                                value="<?= htmlspecialchars($hocky['ten_hk']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập tên học kỳ</div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="nam_hoc">
                                <i class="fas fa-calendar-week mr-1"></i>
                                Năm học <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="nam_hoc" name="nam_hoc" class="form-control"
                                value="<?= htmlspecialchars($hocky['nam_hoc']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập năm học</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ngay_bat_dau">
                                <i class="fas fa-play mr-1"></i>
                                Ngày bắt đầu <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="ngay_bat_dau" name="ngay_bat_dau" class="form-control"
                                value="<?= htmlspecialchars($hocky['ngay_bat_dau']) ?>" required>
                            <div class="invalid-feedback">Vui lòng chọn ngày bắt đầu</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ngay_ket_thuc">
                                <i class="fas fa-stop mr-1"></i>
                                Ngày kết thúc <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="ngay_ket_thuc" name="ngay_ket_thuc" class="form-control"
                                value="<?= htmlspecialchars($hocky['ngay_ket_thuc']) ?>" required>
                            <div class="invalid-feedback">Vui lòng chọn ngày kết thúc</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="luong_hocky">
                                <i class="fas fa-stop mr-1"></i>
                                Lương học kỳ <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="luong_hocky" name="luong_hocky" class="form-control"
                                value="<?= htmlspecialchars($hocky['luong_hocky']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập lương học kỳ</div>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="trang_thai">
                            <i class="fas fa-info-circle mr-1"></i>
                            Trạng thái
                        </label>
                        <select id="trang_thai" name="trang_thai" class="form-control">
                            <option value="Sắp diễn ra" <?= $hocky['trang_thai'] == 'Sắp diễn ra' ? 'selected' : '' ?>>Sắp
                                diễn
                                ra</option>
                            <option value="Đang diễn ra" <?= $hocky['trang_thai'] == 'Đang diễn ra' ? 'selected' : '' ?>>
                                Đang
                                diễn ra</option>
                            <option value="Đã kết thúc" <?= $hocky['trang_thai'] == 'Đã kết thúc' ? 'selected' : '' ?>>Đã
                                kết
                                thúc</option>
                        </select>
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