<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

// Lấy thông tin giảng viên
$stmt = $conn->prepare("SELECT * FROM giaovien WHERE ma_gv = ?");
$stmt->execute([$id]);
$giaovien = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách khoa và bằng cấp
$khoas = $conn->query("SELECT * FROM khoa")->fetchAll(PDO::FETCH_ASSOC);
$bangcaps = $conn->query("SELECT * FROM bangcap")->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin tài khoản
$stmt = $conn->prepare("SELECT username FROM users WHERE ma_gv = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $required = ['ho_ten', 'gioi_tinh', 'ngay_sinh', 'ngay_vao_lam', 'email', 'ma_khoa', 'ma_bangcap'];
        foreach ($required as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc.");
            }
        }

        $conn->beginTransaction();

        // Xử lý ảnh đại diện (nếu có)
        $avatar_filename = $giaovien['avatar'] ?? null;
        if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array(mime_content_type($_FILES['avatar']['tmp_name']), $allowed)) {
                throw new Exception("Ảnh không hợp lệ (chỉ JPG/PNG/GIF).");
            }
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Kích thước ảnh tối đa 2MB.");
            }
            $uploadDir = dirname(__DIR__, 2) . '/assets/img/giaovien'; // Lưu vào assets/img/giaovien
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar_filename = $id . '_' . time() . '.' . $ext;
            $target = $uploadDir . '/' . $avatar_filename;
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                throw new Exception("Không thể lưu ảnh tải lên.");
            }
        }

        $sql = "UPDATE giaovien SET 
                ho_ten = :ho_ten,
                gioi_tinh = :gioi_tinh,
                ngay_sinh = :ngay_sinh,
                dia_chi = :dia_chi,
                email = :email,
                so_dien_thoai = :so_dien_thoai,
                ma_khoa = :ma_khoa,
                ma_bangcap = :ma_bangcap,
                ngay_vao_lam = :ngay_vao_lam,
                avatar = :avatar
                WHERE ma_gv = :ma_gv";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ho_ten' => $_POST['ho_ten'],
            ':gioi_tinh' => $_POST['gioi_tinh'],
            ':ngay_sinh' => $_POST['ngay_sinh'],
            ':dia_chi' => $_POST['dia_chi'] ?? '',
            ':email' => $_POST['email'],
            ':so_dien_thoai' => $_POST['so_dien_thoai'] ?? '',
            ':ma_khoa' => $_POST['ma_khoa'],
            ':ma_bangcap' => $_POST['ma_bangcap'],
            ':ngay_vao_lam' => $_POST['ngay_vao_lam'],
            ':avatar' => $avatar_filename,
            ':ma_gv' => $id
        ]);

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $sql = "UPDATE users SET username = :username, password = :password WHERE ma_gv = :ma_gv";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':username' => $_POST['username'],
                ':password' => $_POST['password'], // Lưu mật khẩu trực tiếp (plaintext)
                ':ma_gv' => $id
            ]);
        }

        $conn->commit();
        session_start();
        $_SESSION['success_message'] = "Cập nhật giảng viên thành công.";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

$avatarUrl = $giaovien['avatar']
    ? "/assets/img/giaovien/" . htmlspecialchars($giaovien['avatar'], ENT_QUOTES, 'UTF-8')
    : "/assets/img/avatar-placeholder.png"; // Hiển thị ảnh từ assets/img/giaovien

echo getHeader("Sửa Thông Tin Giảng Viên");
?>

<!-- Compact styles giống với them.php -->
<style>
    .card {
        font-size: 0.95rem;
    }

    .card .card-header {
        padding: .5rem .75rem;
    }

    .card .card-body {
        padding: .75rem;
    }

    .form-group {
        margin-bottom: .5rem;
    }

    .form-row .form-group {
        padding-right: .35rem;
        padding-left: .35rem;
    }

    .img-thumbnail.avatar-sm {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
    }

    .custom-file .custom-file-label {
        font-size: .9rem;
        padding: .35rem .6rem;
    }

    input.form-control-sm,
    select.form-control-sm,
    textarea.form-control-sm {
        padding: .25rem .5rem;
        font-size: .875rem;
    }

    .text-right.mt-2 {
        margin-top: .5rem !important;
    }
</style>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white py-2">
        <h6 class="mb-0"><i class="fas fa-user-edit mr-2"></i>Sửa Thông Tin Giảng Viên</h6>
    </div>
    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger small mb-2"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-3 text-center">
                    <img id="avatarPreview" src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>"
                        alt="Ảnh đại diện" class="img-thumbnail avatar-sm mb-2">
                    <div class="custom-file mb-2">
                        <input type="file" name="avatar" id="avatar" class="custom-file-input" accept="image/*">
                        <label class="custom-file-label" for="avatar">Chọn ảnh</label>
                    </div>
                    <small class="text-muted">PNG/JPG/GIF, tối đa 2MB</small>
                </div>
                <div class="col-md-9">
                    <!-- Thông tin cá nhân -->
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Họ và tên <span class="text-danger">*</span></label>
                            <input name="ho_ten" type="text" class="form-control form-control-sm" required
                                value="<?= htmlspecialchars($giaovien['ho_ten'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Nhập họ và tên</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Giới tính <span class="text-danger">*</span></label>
                            <select name="gioi_tinh" class="form-control form-control-sm" required>
                                <option value="">-- Chọn --</option>
                                <option <?= $giaovien['gioi_tinh'] === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                <option <?= $giaovien['gioi_tinh'] === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                            </select>
                            <div class="invalid-feedback">Chọn giới tính</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Ngày sinh <span class="text-danger">*</span></label>
                            <input name="ngay_sinh" type="date" class="form-control form-control-sm"
                                max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required
                                value="<?= htmlspecialchars($giaovien['ngay_sinh'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Chọn ngày sinh</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Ngày vào làm <span class="text-danger">*</span></label>
                            <input name="ngay_vao_lam" type="date" class="form-control form-control-sm"
                                max="<?= date('Y-m-d') ?>" required
                                value="<?= htmlspecialchars($giaovien['ngay_vao_lam'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Chọn ngày vào làm</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea name="dia_chi" class="form-control form-control-sm"
                            rows="2"><?= htmlspecialchars($giaovien['dia_chi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <!-- Thông tin liên hệ -->
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Email <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control form-control-sm" required
                                value="<?= htmlspecialchars($giaovien['email'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Nhập email hợp lệ</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Số điện thoại</label>
                            <input name="so_dien_thoai" type="tel" class="form-control form-control-sm"
                                pattern="[0-9]{10,11}"
                                value="<?= htmlspecialchars($giaovien['so_dien_thoai'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Số điện thoại không hợp lệ</div>
                        </div>
                    </div>

                    <!-- Thông tin công việc -->
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Khoa <span class="text-danger">*</span></label>
                            <select name="ma_khoa" class="form-control form-control-sm" required>
                                <option value="">-- Chọn khoa --</option>
                                <?php foreach ($khoas as $khoa): ?>
                                    <option value="<?= htmlspecialchars($khoa['ma_khoa'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $giaovien['ma_khoa'] === $khoa['ma_khoa'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($khoa['ten_khoa'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Chọn khoa</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Bằng cấp <span class="text-danger">*</span></label>
                            <select name="ma_bangcap" class="form-control form-control-sm" required>
                                <option value="">-- Chọn bằng cấp --</option>
                                <?php foreach ($bangcaps as $b): ?>
                                    <option value="<?= htmlspecialchars($b['ma_bangcap'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $giaovien['ma_bangcap'] === $b['ma_bangcap'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['ten_bangcap'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Chọn bằng cấp</div>
                        </div>
                    </div>

                    <!-- Thông tin tài khoản -->
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input name="username" type="text" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mật khẩu mới</label>
                            <input name="password" type="password" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="text-right mt-2">
                        <a href="index.php" class="btn btn-secondary btn-sm mr-2">
                            <i class="fas fa-arrow-left"></i> Danh sách
                        </a>
                        <button type="reset" class="btn btn-outline-secondary btn-sm mr-2">
                            <i class="fas fa-undo"></i> Làm lại
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="mt-3">
    <div class="alert alert-info small">
        <strong>Lưu ý:</strong> Chỉ điền username và mật khẩu nếu muốn thay đổi thông tin tài khoản.
    </div>
</div>

<?php echo getFooter(); ?>

<script>
    (function () {
        // Bootstrap validation
        window.addEventListener('load', function () {
            var forms = document.getElementsByClassName('needs-validation');
            Array.prototype.forEach.call(forms, function (form) {
                form.addEventListener('submit', function (e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });

        // Preview ảnh đại diện
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        avatarInput?.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    })();
</script>