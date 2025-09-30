<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $sql = "UPDATE mon_hoc SET 
                ten_mon = :ten_mon,
                so_tiet = :so_tiet,
                so_tin_chi = :so_tin_chi,
                mo_ta = :mo_ta,
                ma_khoa = :ma_khoa,
                he_so = :he_so 
                WHERE ma_mon = :ma_mon";

        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':ma_mon' => $_POST['ma_mon'],
            ':ten_mon' => $_POST['ten_mon'],
            ':so_tiet' => $_POST['so_tiet'],
            ':so_tin_chi' => $_POST['so_tin_chi'],
            ':mo_ta' => $_POST['mo_ta'],
            ':ma_khoa' => $_POST['ma_khoa'],
            ':he_so' => $_POST['he_so']
        ]);

        if ($result) {
            $_SESSION['success_message'] = "Cập nhật môn học thành công!";
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Lỗi cập nhật: " . $e->getMessage();
    }
}

// Get current monhoc info
$stmt = $conn->prepare("SELECT m.*, k.ten_khoa 
                        FROM mon_hoc m 
                        LEFT JOIN khoa k ON m.ma_khoa = k.ma_khoa 
                        WHERE m.ma_mon = :id");
$stmt->execute([':id' => $_GET['id']]);
$monhoc = $stmt->fetch();

// Get khoa list
$khoas = $conn->query("SELECT * FROM khoa ORDER BY ten_khoa")->fetchAll();

echo getHeader("Sửa học phần");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sửa Môn học</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="container mt-4">
        <div class="card shadow-lg">
            <div class="card-header bg-gradient-info">
                <h5 class="mb-0">
                    <i class="fas fa-book mr-2"></i>
                    Sửa thông tin Môn học
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
                        <div class="form-group col-md-6">
                            <label for="ma_mon">
                                <i class="fas fa-key mr-1"></i>
                                Mã môn <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ma_mon" name="ma_mon" class="form-control"
                                value="<?= htmlspecialchars($monhoc['ma_mon']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ten_mon">
                                <i class="fas fa-book-open mr-1"></i>
                                Tên môn học <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ten_mon" name="ten_mon" class="form-control"
                                value="<?= htmlspecialchars($monhoc['ten_mon']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập tên môn học</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="so_tiet">
                                <i class="fas fa-clock mr-1"></i>
                                Số tiết <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="so_tiet" name="so_tiet" class="form-control"
                                value="<?= htmlspecialchars($monhoc['so_tiet']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập số tiết</div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="so_tin_chi">
                                <i class="fas fa-layer-group mr-1"></i>
                                Số tín chỉ <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="so_tin_chi" name="so_tin_chi" class="form-control"
                                value="<?= htmlspecialchars($monhoc['so_tin_chi']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập số tín chỉ</div>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="he_so">
                                <i class="fas fa-percentage mr-1"></i>
                                Hệ số
                            </label>
                            <input type="number" step="0.01" id="he_so" name="he_so" class="form-control"
                                value="<?= htmlspecialchars($monhoc['he_so']) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ma_khoa">
                                <i class="fas fa-building mr-1"></i>
                                Khoa <span class="text-danger">*</span>
                            </label>
                            <select name="ma_khoa" id="ma_khoa" class="form-control" required>
                                <option value="">-- Chọn khoa --</option>
                                <?php foreach ($khoas as $khoa): ?>
                                    <option value="<?= htmlspecialchars($khoa['ma_khoa']) ?>"
                                        <?= $monhoc['ma_khoa'] == $khoa['ma_khoa'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($khoa['ten_khoa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn khoa</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="mo_ta">
                                <i class="fas fa-info-circle mr-1"></i>
                                Mô tả
                            </label>
                            <textarea id="mo_ta" name="mo_ta" class="form-control"
                                rows="3"><?= htmlspecialchars($monhoc['mo_ta']) ?></textarea>
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