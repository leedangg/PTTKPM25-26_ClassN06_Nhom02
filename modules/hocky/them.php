<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

function taoMaHocKy($conn)
{
    $sql = "SELECT ma_hk FROM hoc_ky ORDER BY ma_hk DESC LIMIT 1";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['ma_hk'];
        // Tách số từ 'HK' và tăng lên 1
        $number = intval(substr($lastCode, 2)) + 1;
        return 'HK' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    return 'HK001';
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $ma_hk = taoMaHocKy($conn);
        // Bỏ ô 'luong_hocky' khỏi required
        $required = ['ten_hk', 'nam_hoc', 'ngay_bat_dau', 'ngay_ket_thuc'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc!");
            }
        }

        // Kiểm tra ngày bắt đầu và kết thúc
        if ($_POST['ngay_ket_thuc'] <= $_POST['ngay_bat_dau']) {
            throw new Exception("Ngày kết thúc phải sau ngày bắt đầu!");
        }

        // Kiểm tra năm học hợp lệ
        if (!preg_match("/^\d{4}-\d{4}$/", $_POST['nam_hoc'])) {
            throw new Exception("Năm học không hợp lệ (định dạng: YYYY-YYYY)!");
        }

        $sql = "INSERT INTO hoc_ky (ma_hk, ten_hk, nam_hoc, ngay_bat_dau, ngay_ket_thuc, trang_thai) 
                VALUES (:ma_hk, :ten_hk, :nam_hoc, :ngay_bat_dau, :ngay_ket_thuc, 'Sắp diễn ra')";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ma_hk' => $ma_hk,
            ':ten_hk' => $_POST['ten_hk'],
            ':nam_hoc' => $_POST['nam_hoc'],
            ':ngay_bat_dau' => $_POST['ngay_bat_dau'],
            ':ngay_ket_thuc' => $_POST['ngay_ket_thuc']
        ]);

        $_SESSION['success_message'] = "Thêm kỳ học **" . htmlspecialchars($_POST['ten_hk']) . "** thành công!";
        header('Location: index.php');
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

echo getHeader("Thêm Kỳ học mới");
?>

<style>
    /* ------------------- Global Look & Feel ------------------- */
    body {
        background-color: #f4f7f6;
        /* Light background */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container-custom {
        max-width: 800px;
        margin-top: 30px;
    }

    /* Card Styling */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        /* Elegant shadow */
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
        /* Blue Gradient */
        color: white;
        padding: 1.5rem 1.5rem;
        font-size: 1.4rem;
        font-weight: 700;
        border-bottom: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-icon-container {
        display: flex;
        align-items: center;
    }

    .header-icon-container h5 {
        margin: 0;
        line-height: 1.2;
    }

    .header-image {
        border-radius: 8px;
        margin-left: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        height: 60px;
        width: auto;
        object-fit: cover;
    }

    /* ------------------- UL/LI Custom Styling ------------------- */
    .form-list {
        list-style: none;
        /* Bỏ dấu chấm */
        padding: 0;
        margin: 0;
    }

    .form-list li {
        display: flex;
        /* Dùng Flexbox cho bố cục ngang */
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .form-list label {
        flex-basis: 40%;
        /* Chiếm 40% chiều rộng cho label */
        font-weight: 600;
        color: #343a40;
        margin-bottom: 0;
        /* Bỏ margin dưới */
        padding-right: 15px;
        /* Khoảng cách với input */
        text-align: right;
        /* Căn phải tên trường */
    }

    .form-list .input-group-wrapper {
        flex-basis: 60%;
        /* Chiếm 60% chiều rộng cho input */
    }

    .form-control {
        border-radius: 8px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s, box-shadow 0.3s;
        height: 45px;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(45deg, #28a745 0%, #1e7e34 100%);
        border: none;
        font-weight: 600;
        border-radius: 8px;
        padding: 10px 25px;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        transform: translateY(-1px);
    }

    .btn-secondary {
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 600;
    }

    /* Alert Styling */
    .alert-danger {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
    }
</style>

<div class="container-custom mx-auto">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="header-icon-container">
                <h5 class="mb-0"><i class="fas fa-plus-circle mr-2"></i> Thêm kỳ học mới</h5>
            </div>
            <img src="<?= $root_path ?>assets/images/hocky.jpg" alt="Học kỳ" class="header-image">
        </div>
        <div class="card-body p-4">
            <form method="POST" onsubmit="return validateForm()" class="needs-validation" novalidate>

                <ul class="form-list">
                    <li>
                        <label for="ten_hk"><i class="fas fa-tag"></i> Tên kỳ học <span
                                class="text-danger">*</span></label>
                        <div class="input-group-wrapper">
                            <input type="text" name="ten_hk" id="ten_hk" class="form-control"
                                value="<?= htmlspecialchars($_POST['ten_hk'] ?? '') ?>" required>
                            <div class="invalid-feedback">Vui lòng nhập tên kỳ học.</div>
                        </div>
                    </li>

                    <li>
                        <label for="nam_hoc"><i class="fas fa-graduation-cap"></i> Năm học <span
                                class="text-danger">*</span></label>
                        <div class="input-group-wrapper">
                            <input type="text" name="nam_hoc" id="nam_hoc" class="form-control"
                                placeholder="VD: 2023-2024" value="<?= htmlspecialchars($_POST['nam_hoc'] ?? '') ?>"
                                pattern="\d{4}-\d{4}" required>
                            <div class="invalid-feedback">Năm học không hợp lệ (định dạng YYYY-YYYY).</div>
                        </div>
                    </li>

                    <li>
                        <label for="ngay_bat_dau"><i class="fas fa-calendar-day"></i> Ngày bắt đầu <span
                                class="text-danger">*</span></label>
                        <div class="input-group-wrapper">
                            <input type="date" name="ngay_bat_dau" id="ngay_bat_dau" class="form-control"
                                value="<?= htmlspecialchars($_POST['ngay_bat_dau'] ?? '') ?>" required>
                            <div class="invalid-feedback">Vui lòng chọn ngày bắt đầu.</div>
                        </div>
                    </li>

                    <li>
                        <label for="ngay_ket_thuc"><i class="fas fa-calendar-alt"></i> Ngày kết thúc <span
                                class="text-danger">*</span></label>
                        <div class="input-group-wrapper">
                            <input type="date" name="ngay_ket_thuc" id="ngay_ket_thuc" class="form-control"
                                value="<?= htmlspecialchars($_POST['ngay_ket_thuc'] ?? '') ?>" required>
                            <div class="invalid-feedback">Vui lòng chọn ngày kết thúc.</div>
                        </div>
                    </li>


                </ul>

                <div class="text-center pt-3">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                        <i class="fas fa-save mr-2"></i> Lưu kỳ học
                    </button>
                    <a href="index.php" class="btn btn-secondary btn-lg ml-3 shadow-sm">
                        <i class="fas fa-arrow-left mr-2"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Enable Bootstrap custom validation styles
    (function () {
        'use strict';
        window.addEventListener('load', function () {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.checkValidity() === false || !validateForm()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    function validateForm() {
        const start = document.getElementById('ngay_bat_dau').value;
        const end = document.getElementById('ngay_ket_thuc').value;

        if (end <= start) {
            alert('Ngày kết thúc phải sau ngày bắt đầu!');
            return false;
        }

        return true;
    }
</script>

<?php echo getFooter(); ?>