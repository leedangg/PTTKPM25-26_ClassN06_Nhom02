<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

// Lấy thông tin khoa
$stmt = $conn->prepare("SELECT * FROM khoa WHERE ma_khoa = ?");
$stmt->execute([$id]);
$khoa = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "UPDATE khoa SET ten_khoa = :ten WHERE ma_khoa = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':ten' => $_POST['ten_khoa'],
        ':id' => $id
    ]);
    header("Location: index.php");
    exit();
}

echo getHeader("Sửa Khoa");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sửa Khoa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <div class="container mt-4">
        <div class="card shadow-lg">
            <div class="card-header bg-gradient-info">
                <h5 class="mb-0">
                    <i class="fas fa-building mr-2"></i>
                    Sửa thông tin Khoa
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
                            <label for="ma_khoa">
                                <i class="fas fa-key mr-1"></i>
                                Mã khoa <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ma_khoa" name="ma_khoa" class="form-control"
                                value="<?= htmlspecialchars($khoa['ma_khoa']) ?>" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ten_khoa">
                                <i class="fas fa-university mr-1"></i>
                                Tên khoa <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="ten_khoa" name="ten_khoa" class="form-control"
                                value="<?= htmlspecialchars($khoa['ten_khoa']) ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập tên khoa</div>
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