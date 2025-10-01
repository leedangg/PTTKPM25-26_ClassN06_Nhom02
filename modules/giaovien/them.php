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
        // Hỗ trợ cả định dạng GV0001 và GV1, tìm số lớn nhất
        if (preg_match('/^GV0*(\d+)$/i', $row['ma_gv'], $m)) {
            $num = intval($m[1]);
            if ($num > $max)
                $max = $num;
        }
    }
    // Lặp để chắc chắn mã chưa tồn tại (phòng trường hợp DB có data lỗi)
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
    // Dùng mime_content_type để kiểm tra loại file thực sự
    return $file && $file['error'] === UPLOAD_ERR_OK && in_array(mime_content_type($file['tmp_name']), $allowed);
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
    
    // Tách chuỗi thành các phần
    $parts = array_filter(explode(' ', trim($str)));
    if (empty($parts)) return '';

    $usernameParts = [];
    foreach ($parts as $part) {
        // Lấy ký tự đầu của mỗi từ (viết thường)
        $usernameParts[] = strtolower(substr($part, 0, 1));
    }
    
    // Lấy ký tự đầu của các từ đầu, và toàn bộ từ cuối cùng
    // Ví dụ: Nguyen Van A -> nva
    $finalUsername = implode('', array_slice($usernameParts, 0, -1)) . strtolower(end($parts));
    
    // Xóa ký tự không phải chữ cái (chỉ giữ lại a-z)
    return preg_replace("/[^a-z]/", '', $finalUsername);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Bắt buộc điền đầy đủ thông tin
        $required = ['ho_ten', 'gioi_tinh', 'ngay_sinh', 'ngay_vao_lam', 'email', 'ma_khoa', 'ma_bangcap', 'luong_co_ban'];
        foreach ($required as $f) {
            if (!isset($_POST[$f]) || trim($_POST[$f]) === '') {
                throw new Exception("Vui lòng điền đầy đủ thông tin bắt buộc.");
            }
        }
        // Kiểm tra trùng email (ko phân biệt hoa thường)
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

        // kiểm tra cột avatar và cột luong_co_ban trong DB (để tránh lỗi SQL nếu schema cũ)
        $hasAvatarCol = false;
        $hasLuongCol = false;
        try {
            $colStmt = $conn->query("SHOW COLUMNS FROM giaovien LIKE 'avatar'");
            if ($colStmt && $colStmt->rowCount() > 0) $hasAvatarCol = true;
            $colStmt2 = $conn->query("SHOW COLUMNS FROM giaovien LIKE 'luong_co_ban'");
            if ($colStmt2 && $colStmt2->rowCount() > 0) $hasLuongCol = true;
        } catch (Exception $e) {
            // Bỏ qua lỗi, giữ nguyên giá trị false nếu có lỗi truy vấn schema
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
            ':ho_ten' => trim($_POST['ho_ten']),
            ':gioi_tinh' => $_POST['gioi_tinh'],
            ':ngay_sinh' => $_POST['ngay_sinh'],
            // Lấy địa chỉ, SDT, email đã được trim
            ':dia_chi' => trim($_POST['dia_chi'] ?? ''),
            ':email' => trim($_POST['email']),
            ':so_dien_thoai' => trim($_POST['so_dien_thoai'] ?? ''),
            ':ma_khoa' => $_POST['ma_khoa'],
            ':ma_bangcap' => $_POST['ma_bangcap'],
            ':ngay_vao_lam' => $_POST['ngay_vao_lam']
        ];
        if ($hasAvatarCol) $params[':avatar'] = $avatar_filename;
        if ($hasLuongCol) {
            // Lương cơ bản: lấy giá trị, nếu rỗng thì là NULL, nếu có thì ép kiểu float
            $luong = trim($_POST['luong_co_ban'] ?? '');
            $params[':luong_co_ban'] = $luong !== '' ? floatval($luong) : null;
        }

        $stmt->execute($params);
        
        // Sau khi thêm giảng viên, tiến hành thêm tài khoản đăng nhập (Username/Password)
        // Mật khẩu mặc định là '1234'
        $defaultPassword = password_hash('1234', PASSWORD_DEFAULT);
        
        // Tạo username dựa trên Họ Tên (ví dụ: Nguyen Van A -> nva)
        $username = vn_to_str($_POST['ho_ten']);
        if (empty($username)) {
             throw new Exception("Không thể tạo username từ Họ Tên.");
        }

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
        $_SESSION['success_message'] = "Thêm giảng viên **" . htmlspecialchars(trim($_POST['ho_ten']), ENT_QUOTES, 'UTF-8') . "** thành công! Username: **" . htmlspecialchars($finalUsername, ENT_QUOTES, 'UTF-8') . "**";
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
    
    .form-control-sm, select.form-control-sm, .custom-file-label {
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
    
    /* Hiệu ứng hover cho nút */
    .btn-primary:hover {
        background: #0056b3;
        border-color: #004085;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.4); /* Thêm box-shadow */
    }
    .btn-secondary:hover {
        background: #5a6268;
        border-color: #545b62;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.4);
    }
    /* Thay đổi màu nút Reset/Làm lại thành Info (xanh nhạt) */
    .btn-info {
        color: #fff;
        background-color: #17a2b8; /* Màu xanh nước biển */
        border-color: #17a2b8;
    }

    .btn-info:hover {
        background-color: #117a8b;
        border-color: #10707f;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(23, 162, 184, 0.4); /* Thêm box-shadow nhẹ */
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
    
    /* ------------------------------------------- */
    /* FORM LAYOUT AND RESPONSIVENESS */
    /* ------------------------------------------- */
    @media (max-width: 991.98px) { /* Adjust for medium and smaller screens */
        .img-thumbnail.avatar-lg {
            width: 150px;
            height: 150px;
        }
        .col-md-4:not(:last-child) {
            border-bottom: 1px solid #eee;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .col-md-4:first-child {
            border-right: none;
        }
    }

    .form-column-divider {
        border-left: 1px solid #e9ecef;
    }
    @media (max-width: 991.98px) {
        .form-column-divider {
            border-left: none;
        }
    }
    .required-label {
        font-weight: 500;
        color: #343a40;
    }

    /* ------------------------------------------- */
    /* BUTTON GROUP STYLING - Equal Width (NEW) */
    /* ------------------------------------------- */
    .button-group-equal-width {
        display: flex; /* Bật Flexbox */
        gap: 10px; /* Khoảng cách giữa các nút */
        justify-content: space-between; /* Giãn đều các phần tử */
        padding: 1.25rem 0 0; /* Tăng padding trên nhẹ */
        margin-top: 1.5rem !important; /* Đảm bảo khoảng cách trên */
    }

    .button-group-equal-width .btn {
        flex-grow: 1; /* Cho phép các nút giãn đều ra */
        margin: 0 !important; /* Xóa margin cũ */
        font-size: 0.9rem; /* Giảm size chữ nhẹ cho vừa vặn */
        padding: 0.5rem 0.25rem; /* Điều chỉnh padding */
        min-width: 0; /* Đảm bảo flexbox hoạt động tốt */
        display: flex; /* Bật flex cho icon và text */
        align-items: center;
        justify-content: center;
    }

    .button-group-equal-width .btn i {
        margin-right: 0.5rem;
    }
    
    /* Điều chỉnh trên màn hình nhỏ (chuyển về xếp dọc) */
    @media (max-width: 575.98px) {
        .button-group-equal-width {
            flex-direction: column; /* Xếp dọc các nút trên màn hình cực nhỏ */
            gap: 8px;
        }
        .button-group-equal-width .btn {
            width: 100%; /* Đảm bảo chiếm toàn bộ chiều rộng */
        }
    }
</style>

<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-plus mr-2"></i> Thêm Giảng viên Mới
        </div>
        <div class="card-body p-4">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small mb-3">
                    <i class="fas fa-times-circle mr-2"></i>
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    
                    <div class="col-lg-4 text-center pb-3 pb-lg-0">
                        <?php
                        $avatarUrl = "/assets/img/avatar-placeholder.png"; 
                        ?>
                        <img id="avatarPreview" src="<?= $avatarUrl ?>" alt="Ảnh đại diện" class="img-thumbnail avatar-lg mb-3">
                        
                        <div class="custom-file mb-2">
                            <input type="file" name="avatar" id="avatar" class="custom-file-input" accept="image/jpeg,image/png,image/gif">
                            <label class="custom-file-label" for="avatar">Chọn ảnh</label>
                        </div>
                        <small class="text-muted d-block mt-n1">PNG/JPG/GIF, tối đa 2MB</small>
                    </div>

                    <div class="col-lg-4 form-column-divider pl-lg-4 pr-lg-3 pb-3 pb-lg-0">
                        <h5 class="text-primary mb-3"><i class="fas fa-id-card-alt mr-2"></i> Thông tin cá nhân</h5>
                        
                        <div class="form-group">
                            <label class="required-label">Họ và tên <span class="text-danger">*</span></label>
                            <input name="ho_ten" type="text" class="form-control form-control-sm" required
                                placeholder="Nguyễn Văn A"
                                value="<?= htmlspecialchars($_POST['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Vui lòng nhập họ và tên.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required-label">Ngày sinh <span class="text-danger">*</span></label>
                            <input name="ngay_sinh" type="date" class="form-control form-control-sm"
                                max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required
                                value="<?= htmlspecialchars($_POST['ngay_sinh'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Giảng viên phải đủ 18 tuổi.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required-label">Giới tính <span class="text-danger">*</span></label>
                            <select name="gioi_tinh" class="form-control form-control-sm" required>
                                <option value="">-- Chọn Giới tính --</option>
                                <option value="Nam" <?= (($_POST['gioi_tinh'] ?? '') === 'Nam') ? 'selected' : '' ?>>Nam</option>
                                <option value="Nữ" <?= (($_POST['gioi_tinh'] ?? '') === 'Nữ') ? 'selected' : '' ?>>Nữ</option>
                                <option value="Khác" <?= (($_POST['gioi_tinh'] ?? '') === 'Khác') ? 'selected' : '' ?>>Khác</option>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn giới tính.</div>
                        </div>

                        <div class="form-group">
                            <label class="required-label">Địa chỉ</label>
                            <textarea name="dia_chi" class="form-control form-control-sm" rows="2"
                                placeholder="Số nhà, đường, xã/phường, quận/huyện, tỉnh/thành phố..."><?= htmlspecialchars($_POST['dia_chi'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="required-label">Email <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control form-control-sm" required
                                placeholder="email@fpt.edu.vn"
                                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Nhập địa chỉ email hợp lệ.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required-label">Số điện thoại</label>
                            <input name="so_dien_thoai" type="tel" class="form-control form-control-sm" pattern="[0-9]{10,11}"
                                placeholder="VD: 0987654321"
                                value="<?= htmlspecialchars($_POST['so_dien_thoai'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Số điện thoại không hợp lệ (10-11 chữ số).</div>
                        </div>
                    </div>

                    <div class="col-lg-4 form-column-divider pl-lg-4 pt-3 pt-lg-0">
                        <h5 class="text-primary mb-3"><i class="fas fa-briefcase mr-2"></i> Thông tin công việc</h5>
                        
                        <div class="form-group">
                            <label class="required-label">Khoa <span class="text-danger">*</span></label>
                            <select name="ma_khoa" class="form-control form-control-sm" required>
                                <option value="">-- Chọn Khoa --</option>
                                <?php foreach ($khoas as $khoa): ?>
                                    <option value="<?=htmlspecialchars($khoa['ma_khoa'],ENT_QUOTES,'UTF-8')?>"
                                        <?= (($_POST['ma_khoa'] ?? '') === $khoa['ma_khoa']) ? 'selected' : '' ?>>
                                        <?=htmlspecialchars($khoa['ten_khoa'],ENT_QUOTES,'UTF-8')?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn khoa.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required-label">Bằng cấp <span class="text-danger">*</span></label>
                            <select name="ma_bangcap" class="form-control form-control-sm" required>
                                <option value="">-- Chọn Bằng cấp --</option>
                                <?php foreach ($bangcaps as $b): ?>
                                    <option value="<?=htmlspecialchars($b['ma_bangcap'],ENT_QUOTES,'UTF-8')?>"
                                        <?= (($_POST['ma_bangcap'] ?? '') === $b['ma_bangcap']) ? 'selected' : '' ?>>
                                        <?=htmlspecialchars($b['ten_bangcap'],ENT_QUOTES,'UTF-8')?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Vui lòng chọn bằng cấp.</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="required-label">Ngày vào làm <span class="text-danger">*</span></label>
                            <input name="ngay_vao_lam" type="date" class="form-control form-control-sm" max="<?= date('Y-m-d') ?>"
                                required
                                value="<?= htmlspecialchars($_POST['ngay_vao_lam'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">Vui lòng chọn ngày vào làm.</div>
                        </div>

                        <div class="form-group">
                            <label class="required-label">Lương cơ bản (VND) <span class="text-danger">*</span></label>
                            <input name="luong_co_ban" type="number" step="1000" min="0" class="form-control form-control-sm"
                                    placeholder="VD: 10000000" required
                                    value="<?= htmlspecialchars($_POST['luong_co_ban'] ?? '', ENT_QUOTES, 'UTF-8' ) ?>">
                            <small class="form-text text-muted">Nhập số nguyên, không được để trống.</small>
                            <div class="invalid-feedback">Vui lòng nhập lương cơ bản (số nguyên dương).</div>
                        </div>

                        <div class="button-group-equal-width border-top mt-4">
                            <a href="index.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left"></i> Danh sách
                            </a>
                            <button type="reset" class="btn btn-info btn-block">
                                <i class="fas fa-undo"></i> Làm lại
                            </button>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Thêm Giảng viên
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-3 mx-auto" style="max-width: 900px;">
        <div class="alert alert-info shadow-sm small border-0" style="border-radius: 10px;">
            <i class="fas fa-info-circle mr-2"></i> 
            **Lưu ý về tài khoản:** Hệ thống sẽ tự động tạo tài khoản đăng nhập cho giảng viên mới.
            <br>Tên đăng nhập (Username) sẽ sinh từ Họ Tên (ví dụ: Nguyễn Văn A &rarr; **nva**); Mật khẩu mặc định là **1234**.
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
            const label = document.querySelector('.custom-file-label[for="avatar"]');
            const defaultLabelText = 'Chọn ảnh';

            avatar.addEventListener('change', function () {
                const file = this.files[0];
                
                if (!file) {
                    avatarPreview.src = DEFAULT_AVATAR_URL;
                    if (label) label.textContent = defaultLabelText;
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                const MAX_SIZE = 2 * 1024 * 1024; // 2MB
                
                // Client-side validation
                if (!allowedTypes.includes(file.type) || file.size > MAX_SIZE) {
                    alert('Lỗi: Chỉ chấp nhận JPG/PNG/GIF và kích thước tối đa 2MB.');
                    this.value = ''; // Clear file input
                    avatarPreview.src = DEFAULT_AVATAR_URL;
                    if (label) label.textContent = defaultLabelText;
                    return;
                }

                // Image preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Update file label
                if (label) {
                    // Cắt tên file dài
                    const fileName = file.name;
                    label.textContent = fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName;
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
            
            // Phone number cleanup (chỉ giữ lại số)
            const phone = document.querySelector('input[name="so_dien_thoai"]');
            if (phone) phone.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 11);
            });
            
            // Lương cơ bản cleanup (chỉ cho phép số nguyên)
            const luong = document.querySelector('input[name="luong_co_ban"]');
            if (luong) luong.addEventListener('input', function() {
                 // Xóa ký tự không phải số và đảm bảo nó là số nguyên
                 this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Date input max attribute for 'ngay_sinh'
            const ngaySinhInput = document.querySelector('input[name="ngay_sinh"]');
            if (ngaySinhInput) {
                const today = new Date();
                today.setFullYear(today.getFullYear() - 18);
                ngaySinhInput.setAttribute('max', today.toISOString().split('T')[0]);
            }
            // Date input max attribute for 'ngay_vao_lam'
            const ngayVaoLamInput = document.querySelector('input[name="ngay_vao_lam"]');
            if (ngayVaoLamInput) {
                const today = new Date();
                ngayVaoLamInput.setAttribute('max', today.toISOString().split('T')[0]);
            }

        }, false);
    })();
</script>