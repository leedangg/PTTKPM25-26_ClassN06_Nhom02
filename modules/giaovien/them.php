<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách Khoa và Bằng cấp
$stmt_khoa = $conn->query("SELECT * FROM khoa ORDER BY ten_khoa");
$khoas = $stmt_khoa->fetchAll(PDO::FETCH_ASSOC);

$stmt_bangcap = $conn->query("SELECT * FROM bangcap ORDER BY ten_bangcap");
$bangcaps = $stmt_bangcap->fetchAll(PDO::FETCH_ASSOC);

// Định nghĩa biến error để tránh lỗi PHP khi chưa POST
$error = '';
// Khởi tạo $_POST nếu chưa tồn tại
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_POST = [];
}

function taoMaGV($conn)
{
    // Lấy tất cả mã giáo viên hiện có và tìm số lớn nhất
    $stmt = $conn->query("SELECT ma_gv FROM giaovien");
    $max = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (preg_match('/^GV0*(\d+)$/', $row['ma_gv'], $m)) {
            $num = intval($m[1]);
            if ($num > $max)
                $max = $num;
        }
    }
    // Lặp để chắc chắn mã chưa tồn tại
    do {
        $max++;
        $newCode = 'GV' . str_pad($max, 4, '0', STR_PAD_LEFT);
        $check = $conn->prepare("SELECT 1 FROM giaovien WHERE ma_gv = ?");
        $check->execute([$newCode]);
        $exists = $check->fetchColumn();
    } while ($exists);
    return $newCode;
}

// small helper for image validation
function isValidImageFile($file)
{
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    return $file && $file['error'] === UPLOAD_ERR_OK && in_array(mime_content_type($file['tmp_name']), $allowed);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Bắt buộc điền đầy đủ thông tin
        $required = ['ho_ten', 'gioi_tinh', 'ngay_sinh', 'ngay_vao_lam', 'email', 'ma_khoa', 'ma_bangcap'];
        foreach ($required as $f) {
            if (!isset($_POST[$f]) || trim($_POST[$f]) === '') {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc.");
            }
        }
        // Kiểm tra trùng email
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM giaovien WHERE LOWER(email)=LOWER(?)");
        $stmtCheck->execute([trim($_POST['email'])]);
        if ($stmtCheck->fetchColumn() > 0)
            throw new Exception("Email đã tồn tại.");

        $conn->beginTransaction();
        $ma_gv = taoMaGV($conn);

        // xử lý ảnh đại diện (nếu có)
        $avatar_filename = null;
        if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if (!isValidImageFile($_FILES['avatar']))
                throw new Exception("Ảnh không hợp lệ (chỉ JPG/PNG/GIF).");
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024)
                throw new Exception("Kích thước ảnh tối đa 2MB.");
            $uploadDir = dirname(__DIR__, 2) . '/assets/img/giaovien'; // Lưu vào assets/img/giaovien
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $avatar_filename = $ma_gv . '_' . time() . '.' . $ext;
            $target = $uploadDir . '/' . $avatar_filename;
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target))
                throw new Exception("Không thể lưu ảnh tải lên.");
        }

        // kiểm tra cột avatar và cột luong_co_ban trong DB
        $hasAvatarCol = false;
        $hasLuongCol = false;
        try {
            $colStmt = $conn->query("SHOW COLUMNS FROM giaovien LIKE 'avatar'");
            if ($colStmt && $colStmt->rowCount() > 0) $hasAvatarCol = true;
            $colStmt2 = $conn->query("SHOW COLUMNS FROM giaovien LIKE 'luong_co_ban'");
            if ($colStmt2 && $colStmt2->rowCount() > 0) $hasLuongCol = true;
        } catch (Exception $e) {
            $hasAvatarCol = $hasAvatarCol ?? false;
            $hasLuongCol = $hasLuongCol ?? false;
        }

        // chuẩn bị câu lệnh INSERT động (hỗ trợ avatar và luong_co_ban nếu tồn tại)
        $cols = ['ma_gv','ho_ten','gioi_tinh','ngay_sinh','dia_chi','email','so_dien_thoai','ma_khoa','ma_bangcap','ngay_vao_lam'];
        $phs = [':ma_gv',':ho_ten',':gioi_tinh',':ngay_sinh',':dia_chi',':email',':so_dien_thoai',':ma_khoa',':ma_bangcap',':ngay_vao_lam'];
        if ($hasAvatarCol) { $cols[] = 'avatar'; $phs[] = ':avatar'; }
        if ($hasLuongCol) { $cols[] = 'luong_co_ban'; $phs[] = ':luong_co_ban'; }

        $sql = "INSERT INTO giaovien (" . implode(',', $cols) . ") VALUES (" . implode(',', $phs) . ")";
        $stmt = $conn->prepare($sql);
        $params = [
            ':ma_gv' => $ma_gv,
            ':ho_ten' => $_POST['ho_ten'],
            ':gioi_tinh' => $_POST['gioi_tinh'],
            ':ngay_sinh' => $_POST['ngay_sinh'],
            ':dia_chi' => $_POST['dia_chi'],
            ':email' => $_POST['email'],
            ':so_dien_thoai' => $_POST['so_dien_thoai'],
            ':ma_khoa' => $_POST['ma_khoa'],
            ':ma_bangcap' => $_POST['ma_bangcap'],
            ':ngay_vao_lam' => $_POST['ngay_vao_lam']
        ];
        if ($hasAvatarCol) $params[':avatar'] = $avatar_filename;
        if ($hasLuongCol) $params[':luong_co_ban'] = isset($_POST['luong_co_ban']) && $_POST['luong_co_ban'] !== '' ? floatval($_POST['luong_co_ban']) : null;

        $stmt->execute($params);
        
        // Sau khi thêm giảng viên, tiến hành thêm tài khoản đăng nhập (Username/Password)
        // Mật khẩu mặc định là '1234'
        $defaultPassword = password_hash('1234', PASSWORD_DEFAULT);
        
        // Tạo username dựa trên Họ Tên (ví dụ: Nguyen Van A -> nva)
        $hoTenKhongDau = vn_to_str($_POST['ho_ten']);
        $username = $hoTenKhongDau;

        // Kiểm tra username đã tồn tại chưa, nếu rồi thì thêm số (nva1, nva2,...)
        $i = 0;
        $originalUsername = $username;
        do {
            $finalUsername = $i > 0 ? $originalUsername . $i : $originalUsername;
            $stmtCheckUser = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmtCheckUser->execute([$finalUsername]);
            $exists = $stmtCheckUser->fetchColumn();
            $i++;
        } while ($exists);


        $sqlUser = "INSERT INTO users (username, password, role, ma_gv) VALUES (:username, :password, 'giangvien', :ma_gv)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->execute([
            ':username' => $finalUsername,
            ':password' => $defaultPassword,
            ':ma_gv' => $ma_gv
        ]);
        
        $conn->commit();

        // thành công
        session_start();
        $_SESSION['success_message'] = "Thêm giảng viên **" . htmlspecialchars($_POST['ho_ten'], ENT_QUOTES, 'UTF-8') . "** thành công! Username: **" . htmlspecialchars($finalUsername, ENT_QUOTES, 'UTF-8') . "**";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction())
            $conn->rollBack();
        $error = $e->getMessage();
    } catch (PDOException $e) {
        if ($conn->inTransaction())
            $conn->rollBack();
        $error = "Lỗi CSDL: " . $e->getMessage();
    }
}

// Chức năng chuyển tiếng Việt có dấu thành không dấu
function vn_to_str($str)
{
    $unicode = [
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
        'A' => 'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ằ|Ẳ|Ẵ|Ặ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
        'd' => 'đ',
        'D' => 'Đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
        'E' => 'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
        'i' => 'í|ì|ỉ|ĩ|ị',
        'I' => 'Í|Ì|Ỉ|Ĩ|Ị',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
        'O' => 'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
        'U' => 'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
        'Y' => 'Ý|Ỳ|Ỷ|Ỹ|Ỵ'
    ];
    foreach ($unicode as $ascii => $uni)
        $str = preg_replace("/($uni)/i", $ascii, $str);
    
    $parts = explode(' ', trim($str));
    $usernameParts = [];
    foreach ($parts as $part) {
        $usernameParts[] = strtolower(substr($part, 0, 1));
    }
    // Lấy ký tự đầu của tất cả các từ, sau đó lấy toàn bộ từ cuối cùng
    $finalUsername = implode('', array_slice($usernameParts, 0, -1)) . strtolower(end($parts));
    
    // Xóa ký tự không phải chữ cái (chỉ giữ lại a-z)
    return preg_replace("/[^a-z]/", '', $finalUsername);
}


// render header
echo getHeader("Thêm giảng viên");
?>

<style>
    /* ------------------------------------------- */
    /* GLOBAL STYLES (for a cleaner look) */
    /* ------------------------------------------- */
    body {
        background-color: #f8f9fa; /* Light grey background */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* ------------------------------------------- */
    /* CARD STYLING */
    /* ------------------------------------------- */
    .card {
        border: none;
        border-radius: 15px; /* Rounded corners */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Soft, elegant shadow */
        transition: transform 0.3s, box-shadow 0.3s;
        overflow: hidden;
    }

    .card:hover {
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .card-header {
        background: linear-gradient(90deg, #007bff, #0056b3); /* Blue gradient */
        color: white;
        padding: 1rem 1.5rem;
        font-size: 1.2rem;
        font-weight: 600;
        border-bottom: none;
    }

    /* ------------------------------------------- */
    /* FORM CONTROL STYLING */
    /* ------------------------------------------- */
    .form-group { 
        margin-bottom: 1rem; 
    }
    
    .form-control-sm, select.form-control-sm {
        border-radius: 8px; /* Tighter rounding */
        border: 1px solid #ced4da;
        transition: border-color 0.3s, box-shadow 0.3s;
        padding: 0.4rem 0.75rem; /* Tăng padding nhẹ cho dễ nhìn */
        font-size: 0.9rem;
    }

    .form-control-sm:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* ------------------------------------------- */
    /* BUTTONS */
    /* ------------------------------------------- */
    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background: #0056b3;
        border-color: #004085;
        transform: translateY(-1px);
    }

    .btn-secondary:hover {
        transform: translateY(-1px);
    }

    .btn-outline-secondary {
        border-color: #ced4da;
        color: #6c757d;
    }

    /* ------------------------------------------- */
    /* AVATAR UPLOAD */
    /* ------------------------------------------- */
    .img-thumbnail.avatar-lg { /* Tăng kích thước ảnh lên 220px */
        width: 220px;
        height: 220px;
        object-fit: cover;
        border-radius: 50%; /* Chuyển thành hình tròn */
        border: 4px solid #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }
    .custom-file-label {
        border-radius: 8px;
        transition: border-color 0.3s;
        cursor: pointer;
    }
    .custom-file-label:hover {
        border-color: #007bff;
    }

    @media (max-width: 768px) {
        .img-thumbnail.avatar-lg {
            width: 150px;
            height: 150px;
        }
    }
</style>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-plus mr-2"></i> Thêm Giảng viên Mới
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small mb-3">
                    <i class="fas fa-times-circle mr-2"></i>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-3 text-center border-right pr-md-4">
                        <?php
                        // Đảm bảo đường dẫn avatar mặc định hoạt động chính xác
                        // Tạm thời dùng đường dẫn tương đối (giả định)
                        $avatarUrl = "/assets/img/avatar-placeholder.png"; 
                        ?>
                        <img id="avatarPreview" src="<?= $avatarUrl ?>" alt="Ảnh đại diện" class="img-thumbnail avatar-lg mb-3">
                        
                        <div class="custom-file mb-2">
                            <input type="file" name="avatar" id="avatar" class="custom-file-input" accept="image/jpeg,image/png,image/gif">
                            <label class="custom-file-label" for="avatar">Chọn ảnh</label>
                        </div>
                        <small class="text-muted d-block mt-n1">PNG/JPG/GIF, tối đa 2MB</small>
                    </div>

                    <div class="col-md-9 pl-md-4">
                        
                        <h5 class="text-primary mb-3">Thông tin cá nhân</h5>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Họ và tên <span class="text-danger">*</span></label>
                                <input name="ho_ten" type="text" class="form-control form-control-sm" required
                                    value="<?= htmlspecialchars($_POST['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <div class="invalid-feedback">Vui lòng nhập họ và tên</div>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Ngày sinh <span class="text-danger">*</span></label>
                                <input name="ngay_sinh" type="date" class="form-control form-control-sm"
                                    max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required
                                    value="<?= htmlspecialchars($_POST['ngay_sinh'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <div class="invalid-feedback">Giảng viên phải đủ 18 tuổi</div>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Giới tính <span class="text-danger">*</span></label>
                                <select name="gioi_tinh" class="form-control form-control-sm" required>
                                    <option value="">-- Chọn --</option>
                                    <option value="Nam" <?= (($_POST['gioi_tinh'] ?? '') === 'Nam') ? 'selected' : '' ?>>Nam</option>
                                    <option value="Nữ" <?= (($_POST['gioi_tinh'] ?? '') === 'Nữ') ? 'selected' : '' ?>>Nữ</option>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn giới tính</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="dia_chi" class="form-control form-control-sm" rows="1"
                                placeholder="Địa chỉ chi tiết..."><?= htmlspecialchars($_POST['dia_chi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        
                        <div class="form-row mb-4">
                            <div class="form-group col-md-6">
                                <label>Email <span class="text-danger">*</span></label>
                                <input name="email" type="email" class="form-control form-control-sm" required
                                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <div class="invalid-feedback">Nhập địa chỉ email hợp lệ</div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Số điện thoại</label>
                                <input name="so_dien_thoai" type="tel" class="form-control form-control-sm" pattern="[0-9]{10,11}"
                                    placeholder="VD: 0987654321"
                                    value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <div class="invalid-feedback">Số điện thoại không hợp lệ (10-11 chữ số)</div>
                            </div>
                        </div>

                        <h5 class="text-primary mb-3 mt-3">Thông tin công việc</h5>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Khoa <span class="text-danger">*</span></label>
                                <select name="ma_khoa" class="form-control form-control-sm" required>
                                    <option value="">-- Chọn Khoa --</option>
                                    <?php foreach ($khoas as $khoa): ?>
                                        <option value="<?=htmlspecialchars($khoa['ma_khoa'],ENT_QUOTES,'UTF-8')?>"
                                            <?= (($_POST['ma_khoa'] ?? '') === $khoa['ma_khoa']) ? 'selected' : '' ?>>
                                            <?=htmlspecialchars($khoa['ten_khoa'],ENT_QUOTES,'UTF-8')?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn khoa</div>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Bằng cấp <span class="text-danger">*</span></label>
                                <select name="ma_bangcap" class="form-control form-control-sm" required>
                                    <option value="">-- Chọn Bằng cấp --</option>
                                    <?php foreach ($bangcaps as $b): ?>
                                        <option value="<?=htmlspecialchars($b['ma_bangcap'],ENT_QUOTES,'UTF-8')?>"
                                            <?= (($_POST['ma_bangcap'] ?? '') === $b['ma_bangcap']) ? 'selected' : '' ?>>
                                            <?=htmlspecialchars($b['ten_bangcap'],ENT_QUOTES,'UTF-8')?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Vui lòng chọn bằng cấp</div>
                            </div>
                             <div class="form-group col-md-4">
                                <label>Ngày vào làm <span class="text-danger">*</span></label>
                                <input name="ngay_vao_lam" type="date" class="form-control form-control-sm" max="<?= date('Y-m-d') ?>"
                                    required
                                    value="<?= htmlspecialchars($_POST['ngay_vao_lam'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="invalid-feedback">Chọn ngày vào làm</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Lương cơ bản (VND) <span class="text-danger">*</span></label>
                                <input name="luong_co_ban" type="number" step="1000" min="0" class="form-control form-control-sm"
                                        placeholder="VD: 10000000" required
                                        value="<?= htmlspecialchars($_POST['luong_co_ban'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>">
                                <small class="form-text text-muted">Nhập số nguyên, để trống nếu không áp dụng</small>
                            </div>
                        </div>

                        <div class="text-right pt-3 border-top mt-3">
                            <a href="index.php" class="btn btn-secondary mr-2"><i class="fas fa-arrow-left"></i> Danh
                                sách</a>
                            <button type="reset" class="btn btn-outline-secondary mr-2"><i class="fas fa-undo"></i> Làm
                                lại</button>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Thêm Giảng viên</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-3">
        <div class="alert alert-info shadow-sm small border-0">
            <i class="fas fa-info-circle mr-2"></i> 
            <strong>Lưu ý về tài khoản:</strong> Hệ thống sẽ tự động tạo tài khoản đăng nhập cho giảng viên mới.
            <br>Tên đăng nhập (Username) sẽ sinh từ Họ Tên (ví dụ: Nguyễn Văn A -> **nva**); Mật khẩu mặc định là **1234**.
        </div>
    </div>
</div>

<?php echo getFooter(); ?>

<script>
    // client preview + file label & bootstrap validation
    (function () {
        'use strict';

        // Set default avatar if not set
        const DEFAULT_AVATAR_URL = '/assets/img/avatar-placeholder.png';
        const avatarPreview = document.getElementById('avatarPreview');
        if (avatarPreview && (!avatarPreview.src || avatarPreview.src.includes('avatar-placeholder.png'))) {
            avatarPreview.src = DEFAULT_AVATAR_URL;
        }

        const avatar = document.getElementById('avatar');
        if (avatar) {
            avatar.addEventListener('change', function () {
                const file = this.files[0];
                const label = this.nextElementSibling;
                
                if (!file) {
                    avatarPreview.src = DEFAULT_AVATAR_URL;
                    label.textContent = 'Chọn ảnh';
                    return;
                }

                const allowed = ['image/jpeg', 'image/png', 'image/gif'];
                
                // Client-side validation
                if (!allowed.includes(file.type)) {
                    alert('Chỉ chấp nhận JPG/PNG/GIF');
                    this.value = '';
                    avatarPreview.src = DEFAULT_AVATAR_URL;
                    label.textContent = 'Chọn ảnh';
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    alert('Kích thước tối đa 2MB');
                    this.value = '';
                    avatarPreview.src = DEFAULT_AVATAR_URL;
                    label.textContent = 'Chọn ảnh';
                    return;
                }

                // Image preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Update file label
                if (label && label.classList.contains('custom-file-label')) {
                    label.textContent = file.name.length > 30 ? file.name.substring(0, 27) + '...' : file.name;
                }
            });
        }

        // Bootstrap validation, phone formatting, and input cleanup
        window.addEventListener('load', function () {
            const forms = document.getElementsByClassName('needs-validation');
            Array.prototype.forEach.call(forms, function (form) {
                form.addEventListener('submit', function (e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
            
            // Phone number cleanup
            const phone = document.querySelector('input[name="so_dien_thoai"]');
            if (phone) phone.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 11);
            });
            
            // Lương cơ bản cleanup (chỉ cho phép số nguyên)
            const luong = document.querySelector('input[name="luong_co_ban"]');
            if (luong) luong.addEventListener('input', function() {
                 this.value = this.value.replace(/[^0-9]/g, '');
            });

        }, false);
    })();
</script>