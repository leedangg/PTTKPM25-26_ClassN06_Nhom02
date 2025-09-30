<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Khởi tạo $error
$error = '';

// Lấy danh sách khoa
$khoas = $conn->query("SELECT * FROM khoa ORDER BY ten_khoa")->fetchAll();

/**
 * Hàm tạo mã môn học tự động dựa trên tên môn
 * Ví dụ: Lập Trình Web -> LTW001
 */
function taoMaMH($conn, $ten_mon)
{
    // Chuyển tiếng Việt có dấu thành không dấu và viết hoa
    $str = str_replace(
        ['á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'đ', 'é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'í', 'ì', 'ỉ', 'ĩ', 'ị', 'ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'],
        ['a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'd', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y'],
        strtolower($ten_mon)
    );
    $str = strtoupper($str);

    // Lấy các chữ cái đầu của tên môn học
    $words = preg_split('/\s+/', $str);
    $prefix = '';
    foreach ($words as $word) {
        if (!empty($word)) {
            $prefix .= $word[0];
        }
    }

    // Giới hạn prefix tối đa 3 ký tự (ví dụ: LTW)
    $prefix = substr($prefix, 0, 3);

    // Nếu prefix quá ngắn, dùng 'MH'
    if (strlen($prefix) < 2) {
        $prefix = 'MH';
    }

    // Tìm số tiếp theo cho mã môn
    $sql = "SELECT ma_mon FROM mon_hoc WHERE ma_mon LIKE :prefix ORDER BY ma_mon DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);

    $number = 1;
    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['ma_mon'];
        // Tách phần số từ chuỗi mã môn
        if (preg_match('/\d+$/', $lastCode, $matches)) {
            $number = intval($matches[0]) + 1;
        }
    }

    // Lặp để chắc chắn mã chưa tồn tại (Mặc dù logic trên gần như đảm bảo)
    do {
        $newCode = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT 1 FROM mon_hoc WHERE ma_mon = ?");
        $check->execute([$newCode]);
        $exists = $check->fetchColumn();
        $number++;
    } while ($exists);

    return $newCode;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ... (Giữ nguyên logic xử lý POST đã được cải tiến)
    try {
        $required = ['ten_mon', 'so_tiet', 'so_tin_chi', 'he_so', 'ma_khoa'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc!");
            }
        }

        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM mon_hoc WHERE LOWER(TRIM(ten_mon)) = LOWER(TRIM(:ten_mon))");
        $stmtCheck->execute([':ten_mon' => $_POST['ten_mon']]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "Học phần **" . htmlspecialchars($_POST['ten_mon']) . "** đã tồn tại!";
        } else {
            if ($_POST['so_tin_chi'] < 1 || $_POST['so_tin_chi'] > 10) {
                throw new Exception("Số tín chỉ phải nằm trong khoảng 1 đến 10.");
            }
            if ($_POST['he_so'] < 1.0 || $_POST['he_so'] > 1.5) {
                throw new Exception("Hệ số môn học phải nằm trong khoảng 1.0 đến 1.5.");
            }

            $ma_mon = taoMaMH($conn, $_POST['ten_mon']);
            $sql = "INSERT INTO mon_hoc (ma_mon, ten_mon, so_tiet, so_tin_chi, mo_ta, ma_khoa, he_so) 
                     VALUES (:ma_mon, :ten_mon, :so_tiet, :so_tin_chi, :mo_ta, :ma_khoa, :he_so)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':ma_mon' => $ma_mon,
                ':ten_mon' => $_POST['ten_mon'],
                ':so_tiet' => (int) $_POST['so_tiet'],
                ':so_tin_chi' => (int) $_POST['so_tin_chi'],
                ':mo_ta' => $_POST['mo_ta'],
                ':ma_khoa' => !empty($_POST['ma_khoa']) ? $_POST['ma_khoa'] : null,
                ':he_so' => (float) $_POST['he_so']
            ]);

            session_start();
            $_SESSION['success_message'] = "Thêm môn học **" . htmlspecialchars($_POST['ten_mon']) . "** với mã **" . $ma_mon . "** thành công!";

            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        $error = "Lỗi: " . $e->getMessage();
    } catch (PDOException $e) {
        $error = "Lỗi CSDL: " . $e->getMessage();
    }
}

// At the beginning, define $root_path so images can be correctly referenced
$root_path = '../../';

echo getHeader("Thêm Môn học");
?>

<style>
    /* Global Look */
    body {
        background-color: #e9ecef;
        /* Light grey background */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Card Styling (Core of the new look) */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        /* Elegant shadow */
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        /* Blue Gradient */
        color: white;
        padding: 1.25rem 1.5rem;
        font-size: 1.25rem;
        font-weight: 700;
        border-bottom: none;
    }

    /* Form Section Box */
    .form-content-box {
        background-color: #f8f9fa;
        /* Very light grey */
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 1.5rem;
        transition: box-shadow 0.3s;
    }

    .form-content-box:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    /* Form Controls */
    .form-control,
    .form-control:focus {
        border-radius: 8px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Labels & Icons */
    label {
        font-weight: 500;
        color: #343a40;
    }

    .fas {
        margin-right: 5px;
        color: #007bff;
        /* Primary color for icons */
    }

    /* Decoration/Info Section (Left Column) */
    .info-decoration {
        padding: 2rem;
        background-color: #eaf3ff;
        /* Light blue background */
        border-radius: 15px;
        box-shadow: inset 0 0 10px rgba(0, 123, 255, 0.1);
        height: 100%;
    }

    .info-decoration h4 {
        color: #007bff;
        font-weight: 600;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid #007bff;
        padding-bottom: 0.5rem;
    }

    /* UL/LI Styling for Info */
    .info-list {
        list-style: none;
        padding-left: 0;
    }

    .info-list li {
        margin-bottom: 1rem;
        padding-left: 1.5rem;
        position: relative;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    .info-list li::before {
        content: "\f058";
        /* check-circle icon */
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
        color: #28a745;
        /* Success Green */
        position: absolute;
        left: 0;
        top: 0;
    }

    .decoration-img {
        width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        margin-top: 1rem;
        object-fit: cover;
    }

    /* Buttons */
    .btn-primary {
        background-color: #28a745;
        /* Green for 'Add' */
        border-color: #28a745;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        background-color: #1e7e34;
        border-color: #1c7430;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
    }

    .btn-secondary {
        border-radius: 8px;
        font-weight: 600;
    }
</style>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-plus-circle mr-2"></i> Thêm Môn học Mới
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="info-decoration">
                        <h4><i class="fas fa-lightbulb"></i> Quy định chung về Học phần</h4>

                        <ul class="info-list">
                            <li>Tên môn học: Phải là duy nhất, không trùng lặp với học phần hiện có.</li>
                            <li>Mã môn học: Sẽ được hệ thống tự động sinh ra dựa trên tên môn.</li>
                            <li>Số tín chỉ: Quy định từ 1 đến 10 tín chỉ cho mỗi môn học.</li>
                            <li>Hệ số: Đánh giá độ khó hoặc tầm quan trọng, dao động từ 1.0 đến 1.5.</li>
                            <li>Số tiết: Phải là số nguyên dương hợp lệ.</li>
                        </ul>

                        <hr>
                        <img src="<?= $root_path ?>assets/img/hocphan.webp" alt="Sổ sách học tập"
                            class="decoration-img">

                    </div>
                </div>

                <div class="col-md-7">
                    <div class="form-content-box h-100">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger mb-4">
                                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <h6 class="text-primary mb-4"><i class="fas fa-edit"></i> Điền thông tin Học phần</h6>

                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Tên môn học <span class="text-danger">*</span></label>
                                <input type="text" name="ten_mon" class="form-control"
                                    value="<?= htmlspecialchars($_POST['ten_mon'] ?? '') ?>" required>
                                <div class="invalid-feedback">Vui lòng nhập tên môn học.</div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-building"></i> Khoa quản lý <span
                                                class="text-danger">*</span></label>
                                        <select name="ma_khoa" class="form-control" required>
                                            <option value="">-- Chọn Khoa --</option>
                                            <?php foreach ($khoas as $khoa): ?>
                                                <option value="<?= htmlspecialchars($khoa['ma_khoa']) ?>"
                                                    <?= (($_POST['ma_khoa'] ?? '') === $khoa['ma_khoa']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($khoa['ten_khoa']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Vui lòng chọn Khoa/Bộ môn.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-percentage"></i> Hệ số môn học <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="he_so" class="form-control"
                                            value="<?= htmlspecialchars($_POST['he_so'] ?? '1.0') ?>" step="0.1"
                                            min="1.0" max="1.5" required>
                                        <div class="invalid-feedback">Hệ số phải nằm trong khoảng 1.0 đến 1.5</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-graduation-cap"></i> Số tín chỉ <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="so_tin_chi" class="form-control"
                                            value="<?= htmlspecialchars($_POST['so_tin_chi'] ?? '2') ?>" min="1"
                                            max="10" required>
                                        <div class="invalid-feedback">Số tín chỉ phải từ 1 đến 10.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-chalkboard-teacher"></i> Số tiết <span
                                                class="text-danger">*</span></label>
                                        <input type="number" name="so_tiet" class="form-control"
                                            value="<?= htmlspecialchars($_POST['so_tiet'] ?? '') ?>" min="1" required>
                                        <div class="invalid-feedback">Vui lòng nhập số tiết học.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-align-left"></i> Mô tả/Tóm tắt nội dung</label>
                                <textarea name="mo_ta" class="form-control" rows="3"
                                    placeholder="Nhập tóm tắt về mục tiêu và nội dung chính của môn học..."><?= htmlspecialchars($_POST['mo_ta'] ?? '') ?></textarea>
                            </div>

                            <div class="text-right mt-4">
                                <a href="index.php" class="btn btn-secondary btn-lg ml-3 shadow-sm">
                                    <i class="fas fa-arrow-left mr-2"></i> Quay lại Danh sách
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
                                    <i class="fas fa-save mr-2"></i> Thêm Môn học
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo getFooter(); ?>

<script>
    // Client-side validation for Bootstrap
    (function () {
        'use strict';
        window.addEventListener('load', function () {
            var forms = document.getElementsByClassName('needs-validation');
            Array.prototype.forEach.call(forms, function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        // Thêm kiểm tra số tín chỉ, số tiết, hệ số (lặp lại kiểm tra PHP để cảnh báo sớm)
                        const soTinChi = document.querySelector('input[name="so_tin_chi"]');
                        const heSo = document.querySelector('input[name="he_so"]');
                        let valid = true;

                        if (soTinChi && (parseInt(soTinChi.value) < 1 || parseInt(soTinChi.value) > 10)) {
                            soTinChi.setCustomValidity('Số tín chỉ phải từ 1 đến 10.');
                            valid = false;
                        } else if (soTinChi) {
                            soTinChi.setCustomValidity('');
                        }

                        if (heSo && (parseFloat(heSo.value) < 1.0 || parseFloat(heSo.value) > 1.5)) {
                            heSo.setCustomValidity('Hệ số phải từ 1.0 đến 1.5.');
                            valid = false;
                        } else if (heSo) {
                            heSo.setCustomValidity('');
                        }

                        if (!valid) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>