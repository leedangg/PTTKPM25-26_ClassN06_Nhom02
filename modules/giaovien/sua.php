<?php
session_start();
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

// Lấy thông tin giảng viên
$stmt = $conn->prepare("SELECT * FROM giaovien WHERE ma_gv = ?");
$stmt->execute([$id]);
$giaovien = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra nếu không tìm thấy giảng viên
if (!$giaovien) {
    die('Lỗi: Giảng viên không tồn tại.');
}

// Lấy danh sách khoa và bằng cấp
$khoas = $conn->query("SELECT * FROM khoa ORDER BY ten_khoa")->fetchAll(PDO::FETCH_ASSOC);
$bangcaps = $conn->query("SELECT * FROM bangcap ORDER BY ma_bangcap DESC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy thông tin tài khoản
$stmt = $conn->prepare("SELECT username FROM users WHERE ma_gv = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
// Gán lại dữ liệu POST nếu có lỗi để giữ lại giá trị
$post_data = $_POST;

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
            $file_mime = mime_content_type($_FILES['avatar']['tmp_name']);
            if (!in_array($file_mime, $allowed)) {
                throw new Exception("Ảnh không hợp lệ (chỉ JPG/PNG/GIF). Loại file: {$file_mime}");
            }
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Kích thước ảnh tối đa 2MB.");
            }
            // Lưu vào thư mục cha của thư mục 'admin' (là root) -> assets/img/giaovien
            $uploadDir = dirname(__DIR__, 2) . '/assets/img/giaovien';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            // Xóa ảnh cũ nếu tồn tại
            if ($giaovien['avatar'] && file_exists($uploadDir . '/' . $giaovien['avatar'])) {
                @unlink($uploadDir . '/' . $giaovien['avatar']);
            }

            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar_filename = $id . '_' . time() . '.' . $ext;
            $target = $uploadDir . '/' . $avatar_filename;
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                throw new Exception("Không thể lưu ảnh tải lên.");
            }
        }

        // Cập nhật thông tin giảng viên
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

        // Cập nhật thông tin tài khoản (nếu có)
        if (!empty($_POST['username']) || !empty($_POST['password'])) {
            $username = trim($_POST['username'] ?? ($user['username'] ?? ''));
            $password = $_POST['password'] ?? '';

            if (!$user) {
                // Tài khoản không tồn tại, cần tạo mới (Dù trường hợp này hiếm)
                if (!empty($username) && !empty($password)) {
                    $sql = "INSERT INTO users (username, password, ma_gv, role) VALUES (:username, :password, :ma_gv, 'teacher')";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':username' => $username, ':password' => $password, ':ma_gv' => $id]);
                } else {
                    throw new Exception("Cần điền đầy đủ Username và Mật khẩu nếu chưa có tài khoản.");
                }
            } else {
                // Tài khoản đã tồn tại, tiến hành cập nhật
                if (!empty($username) && !empty($password)) {
                    $sql = "UPDATE users SET username = :username, password = :password WHERE ma_gv = :ma_gv";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':username' => $username, ':password' => $password, ':ma_gv' => $id]);
                } else if (!empty($password)) {
                    $sql = "UPDATE users SET password = :password WHERE ma_gv = :ma_gv";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':password' => $password, ':ma_gv' => $id]);
                } else if (!empty($username)) {
                    $sql = "UPDATE users SET username = :username WHERE ma_gv = :ma_gv";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':username' => $username, ':ma_gv' => $id]);
                }
            }
        }


        $conn->commit();
        $_SESSION['success_message'] = "Cập nhật giảng viên **" . htmlspecialchars($_POST['ho_ten']) . "** thành công.";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Đường dẫn ảnh hiển thị (sử dụng thư mục cha)
$avatarUrl = $giaovien['avatar']
    ? "../../assets/img/giaovien/" . htmlspecialchars($giaovien['avatar'], ENT_QUOTES, 'UTF-8')
    : "../../assets/img/avatar-placeholder.png";

// Gán lại giá trị sau POST nếu có lỗi
$current_ho_ten = $post_data['ho_ten'] ?? $giaovien['ho_ten'];
$current_gioi_tinh = $post_data['gioi_tinh'] ?? $giaovien['gioi_tinh'];
$current_ngay_sinh = $post_data['ngay_sinh'] ?? $giaovien['ngay_sinh'];
$current_ngay_vao_lam = $post_data['ngay_vao_lam'] ?? $giaovien['ngay_vao_lam'];
$current_dia_chi = $post_data['dia_chi'] ?? $giaovien['dia_chi'];
$current_email = $post_data['email'] ?? $giaovien['email'];
$current_so_dien_thoai = $post_data['so_dien_thoai'] ?? $giaovien['so_dien_thoai'];
$current_ma_khoa = $post_data['ma_khoa'] ?? $giaovien['ma_khoa'];
$current_ma_bangcap = $post_data['ma_bangcap'] ?? $giaovien['ma_bangcap'];
$current_username = $post_data['username'] ?? ($user['username'] ?? '');

echo getHeader("Sửa Thông Tin Giảng Viên");
?>

<style>

</style>
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-edit mr-2"></i>Sửa Thông Tin Giảng Viên:
            **<?= htmlspecialchars($giaovien['ho_ten'], ENT_QUOTES, 'UTF-8') ?>**
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mb-4 rounded-lg shadow-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">

                    <div class="col-lg-4 col-md-4 text-center mb-4 mb-lg-0">
                        <h6 class="text-primary mb-3"><i class="fas fa-camera mr-1"></i> Ảnh Đại Diện</h6>
                        <img id="avatarPreview" src="<?= $avatarUrl ?>" alt="Ảnh đại diện"
                            class="img-thumbnail avatar-lg mb-3">
                        <div class="custom-file mb-3">
                            <input type="file" name="avatar" id="avatar" class="custom-file-input" accept="image/*">
                            <label class="custom-file-label" for="avatar">Chọn ảnh mới</label>
                        </div>
                        <small class="text-muted">PNG/JPG/GIF, tối đa 2MB</small>
                    </div>

                    <div class="col-lg-4 col-md-4">
                        <h6 class="text-primary mb-3"><i class="fas fa-id-card-alt mr-1"></i> Thông Tin Cơ Bản</h6>
                        <div class="form-group">
                            <label class="required-label">Họ và tên <span class="text-danger">*</span></label>
                            <input name="ho_ten" type="text" class="form-control form-control-sm" required
                                value="<?= htmlspecialchars($current_ho_ten, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Nhập họ và tên</div>
                        </div>
                        <div class="form-group">
                            <label class="required-label">Giới tính <span class="text-danger">*</span></label>
                            <select name="gioi_tinh" class="form-control form-control-sm" required>
                                <option value="">-- Chọn --</option>
                                <option value="Nam" <?= $current_gioi_tinh === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                <option value="Nữ" <?= $current_gioi_tinh === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                            </select>
                            <div class="invalid-feedback">Chọn giới tính</div>
                        </div>
                        <div class="form-group">
                            <label class="required-label">Ngày sinh <span class="text-danger">*</span></label>
                            <input name="ngay_sinh" type="date" class="form-control form-control-sm"
                                max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required
                                value="<?= htmlspecialchars($current_ngay_sinh, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Chọn ngày sinh</div>
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="dia_chi" class="form-control form-control-sm"
                                rows="3"><?= htmlspecialchars($current_dia_chi ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="required-label">Email <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control form-control-sm" required
                                value="<?= htmlspecialchars($current_email, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Nhập email hợp lệ</div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Số điện thoại</label>
                            <input name="so_dien_thoai" type="tel" class="form-control form-control-sm"
                                pattern="[0-9]{10,11}"
                                value="<?= htmlspecialchars($current_so_dien_thoai ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Số điện thoại không hợp lệ (10-11 số)</div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 form-column-divider">
                        <h6 class="text-primary mb-3"><i class="fas fa-briefcase mr-1"></i> Thông Tin Công Việc</h6>
                        <div class="form-group">
                            <label class="required-label">Khoa <span class="text-danger">*</span></label>
                            <select name="ma_khoa" class="form-control form-control-sm" required>
                                <option value="">-- Chọn khoa --</option>
                                <?php foreach ($khoas as $khoa): ?>
                                    <option value="<?= htmlspecialchars($khoa['ma_khoa'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $current_ma_khoa === $khoa['ma_khoa'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($khoa['ten_khoa'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Chọn khoa</div>
                        </div>
                        <div class="form-group">
                            <label class="required-label">Bằng cấp <span class="text-danger">*</span></label>
                            <select name="ma_bangcap" class="form-control form-control-sm" required>
                                <option value="">-- Chọn bằng cấp --</option>
                                <?php foreach ($bangcaps as $b): ?>
                                    <option value="<?= htmlspecialchars($b['ma_bangcap'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= $current_ma_bangcap === $b['ma_bangcap'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['ten_bangcap'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Chọn bằng cấp</div>
                        </div>
                        <div class="form-group">
                            <label class="required-label">Ngày vào làm <span class="text-danger">*</span></label>
                            <input name="ngay_vao_lam" type="date" class="form-control form-control-sm"
                                max="<?= date('Y-m-d') ?>" required
                                value="<?= htmlspecialchars($current_ngay_vao_lam, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Chọn ngày vào làm</div>
                        </div>

                        <h6 class="text-primary mt-4 mb-3"><i class="fas fa-key mr-1"></i> Tài Khoản</h6>
                        <div class="form-group">
                            <label>Username</label>
                            <input name="username" type="text" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($current_username, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="form-group mb-0">
                            <label>Mật khẩu mới</label>
                            <input name="password" type="password" class="form-control form-control-sm">
                        </div>
                        <small class="form-text text-muted text-left mt-1">Chỉ điền 1 trong 2 trường trên nếu muốn thay
                            đổi.</small>
                    </div>
                </div>

                <div class="button-group-equal-width">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Danh sách
                    </a>
                    <button type="reset" class="btn btn-info">
                        <i class="fas fa-undo"></i> Đặt lại
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cập nhật
                    </button>
                </div>
            </form>
        </div>
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
        }, false);

        // Preview ảnh đại diện
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        const fileLabel = document.querySelector('.custom-file-label');

        avatarInput?.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                fileLabel.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                fileLabel.textContent = 'Chọn ảnh mới';
            }
        });
    })();
</script>