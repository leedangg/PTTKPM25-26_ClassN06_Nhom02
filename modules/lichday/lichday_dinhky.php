<?php
session_start();
// Đảm bảo các file cấu hình và header tồn tại
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách giáo viên dựa trên role
$sql = "SELECT g.ma_gv, g.ho_ten, b.he_so as he_so_gv 
        FROM giaovien g 
        JOIN bangcap b ON g.ma_bangcap = b.ma_bangcap";
if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    $sql .= " WHERE g.ma_gv = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['ma_gv']]);
} else {
    $stmt = $conn->query($sql);
}
$giaoviens = $stmt->fetchAll();

// Lấy danh sách khoa
$khoas = $conn->query("SELECT ma_khoa, ten_khoa FROM khoa ORDER BY ten_khoa")->fetchAll();

// Chỉ lấy các môn học của khoa đầu tiên ban đầu (để tránh lỗi undefined trên JS)
$firstKhoa = $khoas[0]['ma_khoa'] ?? null;
$monhocs = [];
if ($firstKhoa) {
    $stmt = $conn->prepare("SELECT ma_mon, ten_mon, he_so FROM mon_hoc WHERE ma_khoa = ? OR ma_khoa IS NULL ORDER BY ten_mon");
    $stmt->execute([$firstKhoa]);
    $monhocs = $stmt->fetchAll();
}

// Lấy thông tin học kỳ hiện tại
$current_date = date('Y-m-d');
// Lấy danh sách học kỳ cho dropdown - Chỉ lấy học kỳ đang diễn ra
$hockys = $conn->query("SELECT * FROM hoc_ky 
                         WHERE trang_thai = 'Đang diễn ra'
                         OR (
                             CURRENT_DATE BETWEEN ngay_bat_dau AND ngay_ket_thuc
                             AND trang_thai != 'Đã kết thúc'
                         )
                         ORDER BY nam_hoc DESC, ngay_bat_dau DESC")->fetchAll();

$current_hk = null;
foreach ($hockys as $hk) {
    if ($current_date >= $hk['ngay_bat_dau'] && $current_date <= $hk['ngay_ket_thuc']) {
        $current_hk = $hk;
        break;
    }
}


function taoMaLichDK($conn)
{
    // Cải tiến hàm này để đảm bảo mã duy nhất
    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(ma_lich, 3, 4) AS UNSIGNED)), 0) as max_num FROM lich_day WHERE ma_lich LIKE 'LD%'";
    $result = $conn->query($sql)->fetch();
    $next_num = $result['max_num'] + 1;
    return 'LD' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}

function taoMaLich($conn)
{
    // Cải tiến hàm này để đảm bảo mã duy nhất cho từng buổi cụ thể
    $sql = "SELECT COALESCE(MAX(CAST(SUBSTRING(ma_lich, 2, 8) AS UNSIGNED)), 0) as max_num FROM lich_day WHERE ma_lich LIKE 'L%'";
    $result = $conn->query($sql)->fetch();
    $next_num = $result['max_num'] + 1;
    return 'L' . str_pad($next_num, 8, '0', STR_PAD_LEFT);
}

function tinhHeSoLop($soSV)
{
    if ($soSV < 20)
        return -0.3;
    if ($soSV < 30)
        return -0.2;
    if ($soSV < 40)
        return -0.1;
    if ($soSV < 50)
        return 0.0;
    if ($soSV < 60)
        return 0.1;
    if ($soSV < 70)
        return 0.2;
    if ($soSV < 80)
        return 0.3;
    if ($soSV < 90)
        return 0.4;
    if ($soSV < 100)
        return 0.5;
    if ($soSV < 110)
        return 0.6;
    if ($soSV < 120)
        return 0.7;
    return 0.7 + (floor(($soSV - 120) / 10) * 0.1);
}

// Logic xử lý POST request (đã giữ nguyên logic gốc)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Kiểm tra quyền thêm lịch
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher' && $_POST['ma_gv'] !== $_SESSION['ma_gv']) {
            throw new Exception("Bạn không có quyền thêm lịch cho giáo viên khác!");
        }

        // Bắt buộc điền tất cả trường
        $requiredFields = [
            'ma_gv',
            'ma_mon',
            'ma_hk',
            'so_sinh_vien',
            'ten_lop_hoc',
            'thu_trong_tuan',
            'tiet_bat_dau',
            'so_tiet',
            'phong_hoc'
        ];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || (is_array($_POST[$field]) ? in_array('', $_POST[$field], true) : trim($_POST[$field]) === '')) {
                throw new Exception("Vui lòng điền đầy đủ thông tin!");
            }
        }
        // Kiểm tra số lượng các trường mảng phải bằng nhau
        $count = count($_POST['thu_trong_tuan']);
        if (
            $count == 0 ||
            $count != count($_POST['tiet_bat_dau']) ||
            $count != count($_POST['so_tiet']) ||
            !isset($_POST['phong_hoc']) || trim($_POST['phong_hoc']) === '' 
        ) {
            throw new Exception("Vui lòng điền đầy đủ thông tin cho tất cả các buổi!");
        }

        $conn->beginTransaction();

        // Validate foreign keys
        $stmt = $conn->prepare("SELECT COUNT(*) FROM giaovien WHERE ma_gv = ?");
        $stmt->execute([$_POST['ma_gv']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Giảng viên không tồn tại!");
        }

        $stmt = $conn->prepare("SELECT COUNT(*) FROM mon_hoc WHERE ma_mon = ?");
        $stmt->execute([$_POST['ma_mon']]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception("Môn học không tồn tại!");
        }

        $stmt = $conn->prepare("SELECT * FROM hoc_ky WHERE ma_hk = ?");
        $stmt->execute([$_POST['ma_hk']]);
        $hk_info = $stmt->fetch();
        if (!$hk_info) {
            throw new Exception("Học kỳ không tồn tại!");
        }

        // Generate IDs outside the loop
        $base_ma_lich_dk = taoMaLichDK($conn);
        $count = count($_POST['thu_trong_tuan']);

        // Calculate he_so_lop once
        $so_sinh_vien = (int) $_POST['so_sinh_vien'] ?? 40;
        $he_so_lop = tinhHeSoLop($so_sinh_vien);
        $ten_lop_hoc = $_POST['ten_lop_hoc'];
        $phong_hoc = $_POST['phong_hoc'];
        $ma_mon = $_POST['ma_mon'];
        $ma_hk = $_POST['ma_hk'];
        $ma_gv = $_POST['ma_gv'];


        for ($i = 0; $i < $count; $i++) {
            $thu = (int) $_POST['thu_trong_tuan'][$i];
            $tiet_bat_dau = (int) $_POST['tiet_bat_dau'][$i];
            $so_tiet = (int) $_POST['so_tiet'][$i];
            
            // 1. Insert the 'Master/Template' Schedule entry (ma_lich_goc)
            // Chỉ chạy 1 lần để tạo record template
            if ($i == 0) {
                $ma_lich_template = $base_ma_lich_dk;
                $stmt = $conn->prepare("INSERT INTO lich_day 
                    (ma_lich, ma_gv, ma_mon, ma_hk, thu_trong_tuan, tiet_bat_dau, so_tiet, 
                    phong_hoc, so_buoi_tuan, so_sinh_vien, ngay_day, ten_lop_hoc, he_so_lop) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)"); 
                $stmt->execute([
                    $ma_lich_template,
                    $ma_gv,
                    $ma_mon,
                    $ma_hk,
                    $thu, // Lấy thông tin buổi đầu tiên làm đại diện cho template
                    $tiet_bat_dau,
                    $so_tiet,
                    $phong_hoc,
                    $count,
                    $so_sinh_vien,
                    $ten_lop_hoc,
                    $he_so_lop
                ]);
            }

            // 2. Create individual lich_day entries for the semester dates
            $start_date = new DateTime($hk_info['ngay_bat_dau']);
            $end_date = new DateTime($hk_info['ngay_ket_thuc']);

            // Find the first occurrence of the correct day of the week
            while ($start_date->format('N') != $thu) {
                $start_date->modify('+1 day');
            }

            while ($start_date <= $end_date) {
                // Tận dụng hàm taoMaLich để có mã duy nhất
                $ma_lich = taoMaLich($conn); 

                $sql = "INSERT INTO lich_day 
                        (ma_lich, ma_gv, ma_mon, ma_hk, ngay_day, 
                        tiet_bat_dau, so_tiet, phong_hoc, ma_lich_goc, so_sinh_vien, ten_lop_hoc, he_so_lop) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $ma_lich,
                    $ma_gv,
                    $ma_mon,
                    $ma_hk,
                    $start_date->format('Y-m-d'),
                    $tiet_bat_dau,
                    $so_tiet,
                    $phong_hoc,
                    $base_ma_lich_dk, // Dùng mã template đã tạo
                    $so_sinh_vien,
                    $ten_lop_hoc,
                    $he_so_lop
                ]);

                // Move to the next week (7 days later)
                $start_date->modify('+7 days');
            }
        }

        // 3. Thêm hoặc cập nhật lớp học vào bảng lop_hoc
        // Mã lớp = tên lớp + mã môn (loại bỏ khoảng trắng, ký tự đặc biệt)
        $ma_lop = preg_replace('/[^A-Za-z0-9]/', '', $ten_lop_hoc) . $ma_mon;

        $stmtCheckLop = $conn->prepare("SELECT COUNT(*) FROM lop_hoc WHERE ma_lop = ?");
        $stmtCheckLop->execute([$ma_lop]);
        
        if ($stmtCheckLop->fetchColumn() > 0) {
             throw new Exception("Lớp học với tên **" . htmlspecialchars($ten_lop_hoc) . "** đã tồn tại. Vui lòng chọn tên khác!");
        }

        $stmtInsert = $conn->prepare("INSERT INTO lop_hoc (ma_lop, ten_lop, so_sinh_vien, ma_mon, ma_hk) VALUES (?, ?, ?, ?, ?)");
        $stmtInsert->execute([$ma_lop, $ten_lop_hoc, $so_sinh_vien, $ma_mon, $ma_hk]);

        $conn->commit();
        $_SESSION['success_message'] = "Lập lịch dạy cho lớp **" . htmlspecialchars($ten_lop_hoc) . "** thành công!";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        if (
            ($e instanceof PDOException || $e instanceof Exception)
            && strpos($e->getMessage(), 'Duplicate entry') !== false
            && strpos($e->getMessage(), 'for key \'PRIMARY\'') !== false
        ) {
            $error = "Lỗi: Lớp đã tồn tại, hãy thay đổi tên lớp để tạo lớp mới!";
        } else {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
}

echo getHeader("Lập lịch dạy");
?>

<style>
    /* ------------------- Global Look & Feel ------------------- */
    body {
        background-color: #f4f7f6; /* Light background */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .container {
        max-width: 1400px; /* Tăng max-width để form rộng rãi hơn */
    }

    /* Card Styling */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); /* Elegant shadow */
        overflow: hidden;
        margin-bottom: 25px;
    }

    .card-header {
        background: linear-gradient(90deg, #013b79ff 0%, #0056b3 100%); /* Blue Gradient */
        color: white;
        padding: 1.25rem 1.5rem;
        font-size: 1.25rem;
        font-weight: 700;
        border-bottom: none;
    }

    /* Input & Select Styling */
    .form-control, .custom-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus, .custom-select:focus {
        border-color: #003977ff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Section Box Styling */
    .section-box {
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 1.5rem;
        background-color: #fff;
        margin-bottom: 20px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        height: 100%; 
    }
    .section-box h6 {
        color: #003771ff;
        font-weight: 600;
        margin-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
    }
    
    /* NEW STYLING FOR DAY SCHEDULE */
    .schedule-column-divider {
        border-left: 1px solid #e9ecef;
        padding-left: 1.5rem; 
    }
    .schedule-column-divider.col-md-6 {
        padding-right: 0.75rem; 
        
    }

    /* Buổi học box */
    .buoi-hoc-box {
        border: 1px solid #b3d4ff; 
        border-radius: 10px;
        padding: 1rem;
        background-color: #f7fbff; /* Very light blue */
        margin-bottom: 1rem;
        transition: box-shadow 0.3s, border-color 0.3s;
    }
    .buoi-hoc-box:hover {
        border-color: #007bff;
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.1);
    }
    .buoi-hoc-box h6 {
        color: #0056b3;
        font-weight: 700;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 0.25rem;
    }

    /* Coefficient Box */
    .coefficient-card {
       
        background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); /* Teal Gradient */
        color: white;
        border-radius: 10px;
        padding: 1.25rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        height: 100%;
        margin-top: 15px;
    }
    .coefficient-card h6 {
        font-weight: 700;
        border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        padding-bottom: 5px;
        margin-bottom: 10px;
    }
    .coefficient-card .value-display {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .coefficient-card .value-total {
        font-size: 1.5rem;
        font-weight: 800;
        color: #ffc107; /* Yellow for total highlight */
    }

    /* BỐ CỤC 2 CỘT CHÍNH 35%/65% */
    .left-column {
        flex: 0 0 40%; /* 35% chiều rộng */
        max-width: 40%; 
        padding-right: 15px; 
    }
    .right-column {
        flex: 0 0 60%; /* 60% chiều rộng */
        max-width: 60%;
        padding-left: 15px;
    }

    /* Điều chỉnh trên màn hình nhỏ */
    @media (max-width: 991.98px) {
        .left-column, .right-column {
            flex: 0 0 100%;
            max-width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }
        .schedule-column-divider {
             border-left: none;
             padding-left: 0.75rem; /* Reset padding */
        }
        .schedule-column-divider.col-md-6 {
             padding-right: 0.75rem;
        }
    }
    
    /* Buttons */
    .btn-primary {
        background: linear-gradient(45deg, #28a745 0%, #1e7e34 100%); /* Green Gradient for Save */
        border: none;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .btn-primary:hover {
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        transform: translateY(-1px);
    }
    .btn-secondary {
        border-radius: 8px;
        transition: all 0.3s;
    }
    .btn-secondary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.4);
    }
</style>

<div class="container mt-5">
    <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-calendar-alt mr-2"></i> Lập lịch dạy cho Học kỳ mới
        </div>

        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    
                    <div class="col-lg-5 left-column">
                        
                        <div class="section-box" style="height : 38%;">
                            <h6 class="text-primary"><i class="fas fa-info-circle"></i> Thông tin cơ bản</h6>
                            <div class="form-row">
                                <div class="col-md-12">
                                     <div class="form-group">
                                        <label><i class="fas fa-user"></i> Giáo viên <span class="text-danger">*</span></label>
                                        <select name="ma_gv" class="form-control custom-select" required>
                                            <option value="">-- Chọn giáo viên --</option>
                                            <?php foreach ($giaoviens as $gv): ?>
                                                    <option value="<?= $gv['ma_gv'] ?>" 
                                                        <?= (isset($_SESSION['ma_gv']) && $gv['ma_gv'] == $_SESSION['ma_gv']) ? 'selected' : '' ?>
                                                        data-heso="<?= $gv['he_so_gv'] ?? 1.0 ?>">
                                                        <?= htmlspecialchars($gv['ho_ten']) ?>
                                                    </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-building"></i> Khoa <span class="text-danger">*</span></label>
                                        <select name="ma_khoa" class="form-control custom-select" id="select-khoa" required>
                                            <option value="">-- Chọn khoa --</option>
                                            <?php foreach ($khoas as $khoa): ?>
                                                    <option value="<?= $khoa['ma_khoa'] ?>" <?= ($firstKhoa == $khoa['ma_khoa'] && !isset($_POST['ma_khoa'])) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($khoa['ten_khoa']) ?>
                                                    </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label><i class="fas fa-book"></i> Môn học <span class="text-danger">*</span></label>
                                        <select name="ma_mon" class="form-control custom-select" id="select-monhoc" required>
                                            <option value="">-- Chọn môn học --</option>
                                            <?php foreach ($monhocs as $mon): ?>
                                                    <option value="<?= $mon['ma_mon'] ?>" data-heso="<?= $mon['he_so'] ?? 1.0 ?>">
                                                        <?= htmlspecialchars($mon['ten_mon']) ?>
                                                    </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-box" style="height : 60%;">
                            <h6 class="text-primary"><i class="fas fa-users"></i> Thông tin Lớp học & Quy đổi</h6>
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-users"></i> Số sinh viên <span class="text-danger">*</span></label>
                                        <input type="number" name="so_sinh_vien" id="so_sinh_vien" class="form-control"
                                                value="<?= htmlspecialchars($_POST['so_sinh_vien'] ?? '40') ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-graduation-cap"></i> Tên Lớp Học <span class="text-danger">*</span></label>
                                        <input type="text" name="ten_lop_hoc" id="ten_lop_hoc" class="form-control"
                                                value="<?= htmlspecialchars($_POST['ten_lop_hoc'] ?? 'N01') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        
                                                <label><i class="fas fa-calendar"></i> Học kỳ <span class="text-danger">*</span></label>
                                        <select name="ma_hk" class="form-control custom-select" required>
                                            <option value="">-- Chọn học kỳ --</option>
                                            <?php foreach ($hockys as $hk): ?>
                                                    <option value="<?= $hk['ma_hk'] ?>" 
                                                        <?= ($current_hk && $hk['ma_hk'] == $current_hk['ma_hk']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($hk['ten_hk']) ?> - <?= $hk['nam_hoc'] ?>
                                                    </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="coefficient-card" style="height : 40%;">
                                <h6><i class="fas fa-chart-bar" style="color: #00346cff; "></i> Hệ số Quy đổi Giờ chuẩn</h6>
                                <div class="row">
                                    <div class="col-6">
                                        Hệ số GV: <span id="he_so_gv" class="value-display">1.0</span>
                                    </div>
                                    <div class="col-6">
                                        Hệ số HP: <span id="mon_hoc" class="value-display">1.0</span>
                                    </div>
                                    <div class="col-6 mt-2">
                                        Hệ số Lớp: <span id="he_so_lop" class="value-display">0.0</span>
                                    </div>
                                    <div class="col-6 text-right mt-2">
                                        Tổng HS: <span id="total_coefficient" class="value-total">1.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="col-lg-7 right-column">
                        <div class="section-box">
                            <h6 class="text-primary"><i class="fas fa-calendar-check"></i> Chi tiết Lịch Dạy</h6>
                            <div class="form-row mb-3 align-items-end">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-calendar-day"></i> Số buổi/tuần <span class="text-danger">*</span></label>
                                        <input type="number" name="so_buoi_tuan" class="form-control" min="1" max="6" value="1"
                                            id="so_buoi_tuan" onchange="taoThuTrongTuan(this.value)" required>
                                        <div class="invalid-feedback">Số buổi phải từ 1 đến 6.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-door-open"></i> Phòng học chung <span class="text-danger">*</span></label>
                                        <input type="text" name="phong_hoc" class="form-control" 
                                                value="<?= htmlspecialchars($_POST['phong_hoc'] ?? '') ?>" required>
                                        <div class="invalid-feedback">Vui lòng nhập phòng học.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row" id="thu_trong_tuan_container">
                                </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                        <i class="fas fa-save mr-2"></i> Lập lịch
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
    // ------------------- JAVASCRIPT LOGIC -------------------

    // Hàm tính hệ số lớp dựa trên số sinh viên (PHP function clone)
    function tinhHeSoLop(soSV) {
        if (soSV < 20) return -0.3;
        if (soSV < 30) return -0.2;
        if (soSV < 40) return -0.1;
        if (soSV < 50) return 0.0;
        if (soSV < 60) return 0.1;
        if (soSV < 70) return 0.2;
        if (soSV < 80) return 0.3;
        if (soSV < 90) return 0.4;
        if (soSV < 100) return 0.5;
        if (soSV < 110) return 0.6;
        if (soSV < 120) return 0.7;
        return 0.7 + (Math.floor((soSV - 120) / 10) * 0.1);
    }

    // Hàm cập nhật tổng hệ số
    function updateTotalCoefficient() {
        const heSoGV = parseFloat(document.getElementById('he_so_gv').textContent) || 0;
        const heSoMon = parseFloat(document.getElementById('mon_hoc').textContent) || 0;
        const heSoLop = parseFloat(document.getElementById('he_so_lop').textContent) || 0;

        // Công thức: Hệ số GV * (Hệ số HP + Hệ số Lớp)
        const totalCoefficient = heSoGV * (heSoMon + heSoLop);
        document.getElementById('total_coefficient').textContent = totalCoefficient.toFixed(2);
    }

    // 1. Hàm tạo động các buổi học
    function taoThuTrongTuan(soBuoi) {
        const container = document.getElementById('thu_trong_tuan_container');
        container.innerHTML = '';

        const soBuoiInt = parseInt(soBuoi);
        if (isNaN(soBuoiInt) || soBuoiInt < 1) return;

        for (let i = 1; i <= soBuoiInt; i++) {
            const col = document.createElement('div');
            // Sử dụng col-md-6 để tạo 2 cột nhỏ bên trong CỘT PHẢI
            col.className = 'col-md-6'; 
            
            // Áp dụng lớp CSS chia cột cho phần tử thứ hai, thứ tư, thứ sáu...
            if (i % 2 === 0 && soBuoiInt > 1) {
                 col.classList.add('schedule-column-divider');
            }

            col.innerHTML = `
                <div class="buoi-hoc-box">
                    <h6 class="mb-3">Buổi ${i}</h6>
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-day"></i> Thứ <span class="text-danger">*</span></label>
                                <select name="thu_trong_tuan[]" class="form-control custom-select" required>
                                    <option value="2">Thứ 2</option>
                                    <option value="3">Thứ 3</option>
                                    <option value="4">Thứ 4</option>
                                    <option value="5">Thứ 5</option>
                                    <option value="6">Thứ 6</option>
                                    <option value="7">Thứ 7</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-hourglass-start"></i> Tiết BD <span class="text-danger">*</span></label>
                                <input type="number" name="tiet_bat_dau[]" class="form-control" min="1" max="15" value="1" required>
                                <div class="invalid-feedback">Tiết bắt đầu phải từ 1 đến 15.</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label><i class="fas fa-clock"></i> Số tiết <span class="text-danger">*</span></label>
                                <input type="number" name="so_tiet[]" class="form-control" min="1" max="6" value="3" required>
                                <div class="invalid-feedback">Số tiết phải từ 1 đến 6.</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(col);
        }
    }

    // 2. AJAX Load Môn học theo Khoa
    document.getElementById('select-khoa').addEventListener('change', function () {
        const maKhoa = this.value;
        const select = document.getElementById('select-monhoc');
        select.innerHTML = '<option value="">-- Tải môn học... --</option>';

        if (maKhoa) {
            // Lưu ý: Cần có file `get_monhoc.php` để xử lý AJAX này
            fetch(`get_monhoc.php?ma_khoa=${maKhoa}`) 
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    select.innerHTML = '<option value="">-- Chọn môn học --</option>';
                    data.forEach(mon => {
                        const option = document.createElement('option');
                        option.value = mon.ma_mon;
                        option.text = mon.ten_mon;
                        option.dataset.heso = mon.he_so || 1.0; 
                        select.add(option);
                    });
                    
                    // Cập nhật hệ số môn học nếu có môn được chọn (mặc định chọn cái đầu tiên)
                    if (data.length > 0) {
                        select.value = data[0].ma_mon; // Tự động chọn môn đầu tiên
                        document.getElementById('mon_hoc').textContent = parseFloat(data[0].he_so || 1.0).toFixed(1);
                    } else {
                        document.getElementById('mon_hoc').textContent = '1.0';
                    }
                    updateTotalCoefficient();
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    select.innerHTML = '<option value="">-- Lỗi tải môn học --</option>';
                    document.getElementById('mon_hoc').textContent = '1.0';
                    updateTotalCoefficient();
                });
        } else {
            select.innerHTML = '<option value="">-- Chọn môn học --</option>';
            document.getElementById('mon_hoc').textContent = '1.0';
            updateTotalCoefficient();
        }
    });
    
    // 3. Cập nhật hệ số khi thay đổi Giáo viên
    document.querySelector('select[name="ma_gv"]').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const heSoGV = selectedOption ? (selectedOption.dataset.heso || 1.0) : 1.0;
        document.getElementById('he_so_gv').textContent = parseFloat(heSoGV).toFixed(1);
        updateTotalCoefficient();
    });

    // 4. Cập nhật hệ số khi thay đổi Môn học
    document.getElementById('select-monhoc').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const heSoMon = selectedOption ? (selectedOption.dataset.heso || 1.0) : 1.0;
        document.getElementById('mon_hoc').textContent = parseFloat(heSoMon).toFixed(1);
        updateTotalCoefficient();
    });

    // 5. Cập nhật hệ số khi thay đổi Số sinh viên
    document.getElementById('so_sinh_vien').addEventListener('input', function () {
        const soSV = parseInt(this.value) || 0;
        const heSoLop = tinhHeSoLop(soSV);
        document.getElementById('he_so_lop').textContent = heSoLop.toFixed(1);
        updateTotalCoefficient();
    });

    // 6. Khởi tạo
    document.addEventListener('DOMContentLoaded', function () {
        // Khởi tạo các buổi học (mặc định 1)
        const soBuoiInput = document.getElementById('so_buoi_tuan');
        taoThuTrongTuan(soBuoiInput.value); 
        
        // Khởi tạo hệ số GV
        const selectedGV = document.querySelector('select[name="ma_gv"]').selectedOptions[0];
        if (selectedGV) {
            document.getElementById('he_so_gv').textContent = parseFloat(selectedGV.dataset.heso || 1.0).toFixed(1);
        }
        
        // Khởi tạo hệ số Lớp 
        const soSV = parseInt(document.getElementById('so_sinh_vien').value) || 40;
        document.getElementById('he_so_lop').textContent = tinhHeSoLop(soSV).toFixed(1);
        
        // Kích hoạt load môn học ban đầu để cập nhật hệ số môn và tổng
        const maKhoaInit = document.getElementById('select-khoa').value;
        if (maKhoaInit) {
              // Cần đợi 1 chút để DOM sẵn sàng cho AJAX
              setTimeout(() => {
                   document.getElementById('select-khoa').dispatchEvent(new Event('change'));
              }, 100);
        }
        
        // Cần gọi lại cập nhật tổng để đảm bảo tính đúng
        updateTotalCoefficient();
        
        // Bootstrap form validation
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
    });
</script>

<?php echo getFooter(); ?>