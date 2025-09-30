<?php
session_start();
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
    $sql = "SELECT CONCAT('LD', LPAD(COALESCE(MAX(CAST(SUBSTRING_INDEX(SUBSTRING(ma_lich, 1, 6), 'LD', -1) AS UNSIGNED)) + 1, 1), 4, '0')) as ma_lich 
            FROM lich_day 
            WHERE ma_lich LIKE 'LD%'";
    $result = $conn->query($sql)->fetch();
    return $result['ma_lich'];
}

function taoMaLich($conn)
{
    $sql = "SELECT CONCAT('L', LPAD(COALESCE(MAX(CAST(SUBSTRING_INDEX(SUBSTRING(ma_lich, 1, 9), 'L', -1) AS UNSIGNED)) + 1, 1), 8, '0')) as ma_lich 
            FROM lich_day";
    $result = $conn->query($sql)->fetch();
    return $result['ma_lich'];
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

// Logic xử lý POST request (giữ nguyên logic gốc)
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
            !isset($_POST['phong_hoc']) || trim($_POST['phong_hoc']) === '' // Kiểm tra trường đơn phòng học
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
            $ma_lich_dk = $base_ma_lich_dk . '_' . ($i + 1); // Unique ID for each iteration
            $thu = (int) $_POST['thu_trong_tuan'][$i];
            $tiet_bat_dau = (int) $_POST['tiet_bat_dau'][$i];
            $so_tiet = (int) $_POST['so_tiet'][$i];

            // 1. Insert/Update the 'Master' Schedule entry (ma_lich_goc)
            // Note: The original code uses a repetitive INSERT. We'll simulate a 'master' entry first, 
            // then iterate through dates.
            $stmt = $conn->prepare("INSERT INTO lich_day 
                (ma_lich, ma_gv, ma_mon, ma_hk, thu_trong_tuan, tiet_bat_dau, so_tiet, 
                phong_hoc, so_buoi_tuan, so_sinh_vien, ngay_day, ten_lop_hoc, he_so_lop) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)");

            // The original logic was flawed by using a master schedule ID (LDxxxx_y) for an entry 
            // that also contained CURRENT_DATE for ngay_day. Let's assume the first entry (i=0) 
            // is the 'Master/Template' entry for simplicity of ID generation, though this is conceptually messy.
            // A dedicated 'lich_day_template' table would be better.

            // Use the first iteration (i=0) to insert the 'template' record if needed.
            if ($i == 0) {
                $ma_lich_template = $base_ma_lich_dk;
                $stmt->execute([
                    $ma_lich_template,
                    $ma_gv,
                    $ma_mon,
                    $ma_hk,
                    $thu,
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

            $j = 1; // Counter for unique individual schedule IDs
            while ($start_date <= $end_date) {
                $ma_lich = 'L' . str_pad($j, 8, '0', STR_PAD_LEFT); // Re-generate unique ID here or use a better function

                // Let's use the provided taoMaLich function which relies on MAX(CAST(SUBSTRING...))
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
                    $base_ma_lich_dk,
                    $so_sinh_vien,
                    $ten_lop_hoc,
                    $he_so_lop
                ]);

                // Move to the next week (7 days later)
                $start_date->modify('+7 days');
                $j++;
            }
        }

        // 3. Thêm hoặc cập nhật lớp học vào bảng lop_hoc
        // Mã lớp = tên lớp + mã môn (loại bỏ khoảng trắng, ký tự đặc biệt)
        $ma_lop = preg_replace('/[^A-Za-z0-9]/', '', $ten_lop_hoc) . $ma_mon;

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
        // Kiểm tra lỗi trùng mã lớp
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
        max-width: 1200px;
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
        background: linear-gradient(90deg, #007bff 0%, #0056b3 100%); /* Blue Gradient */
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
        border-color: #007bff;
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
    }
    .section-box h6 {
        color: #007bff;
        font-weight: 600;
        margin-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
    }

    /* Buổi học box */
    .buoi-hoc-box {
        border: 1px solid #1f4873ff; /* Primary border for emphasis */
        border-radius: 10px;
        padding: 1rem;
        background-color: #eaf3ff; /* Very light blue */
        margin-bottom: 1rem;
    }
    .buoi-hoc-box h6 {
        color: #0056b3;
        font-weight: 700;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid #b3d4ff;
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
                <div class="section-box">
                    <h6 class="text-primary"><i class="fas fa-info-circle"></i> Thông tin cơ bản</h6>
                    <div class="form-row">
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <div class="form-group">
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

                <div class="section-box">
                    <h6 class="text-primary"><i class="fas fa-users"></i> Thông tin Lớp học & Quy đổi</h6>
                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group">
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
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-graduation-cap"></i> Tên Lớp Học <span class="text-danger">*</span></label>
                                <input type="text" name="ten_lop_hoc" id="ten_lop_hoc" class="form-control"
                                       value="<?= htmlspecialchars($_POST['ten_lop_hoc'] ?? 'N01') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-users"></i> Số sinh viên <span class="text-danger">*</span></label>
                                <input type="number" name="so_sinh_vien" id="so_sinh_vien" class="form-control"
                                       value="<?= htmlspecialchars($_POST['so_sinh_vien'] ?? '40') ?>" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="coefficient-card">
                                <h6><i class="fas fa-chart-bar"></i> Hệ số Quy đổi Giờ chuẩn</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        Giáo viên: <span id="he_so_gv" class="value-display">1.0</span>
                                    </div>
                                    <div class="col-md-3">
                                        Học phần: <span id="mon_hoc" class="value-display">1.0</span>
                                    </div>
                                    <div class="col-md-3">
                                        Lớp (SV): <span id="he_so_lop" class="value-display">0.0</span>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        Tổng hệ số: <span id="total_coefficient" class="value-total">1.00</span>
                                    </div>
                                </div>
                               
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-box">
                    <h6 class="text-primary"><i class="fas fa-calendar-check"></i> Chi tiết Lịch Dạy</h6>
                    <div class="form-row">
                         <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-day"></i> Số buổi/tuần <span class="text-danger">*</span></label>
                                <input type="number" name="so_buoi_tuan" class="form-control" min="1" max="6" value="1"
                                    id="so_buoi_tuan" onchange="taoThuTrongTuan(this.value)" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="form-group">
                                <label><i class="fas fa-door-open"></i> Phòng học chung <span class="text-danger">*</span></label>
                                <input type="text" name="phong_hoc" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['phong_hoc'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            </div>
                    </div>
                    
                    <div class="row" id="thu_trong_tuan_container">
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
            col.className = 'col-md-6'; // Chia làm 2 cột
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
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-hourglass-end"></i> Số tiết <span class="text-danger">*</span></label>
                                <input type="number" name="so_tiet[]" class="form-control" min="1" max="6" value="3" required>
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
            // Sử dụng AJAX để tải môn học (giả sử bạn có file get_monhoc.php)
            fetch(`get_monhoc.php?ma_khoa=${maKhoa}`) 
                .then(response => response.json())
                .then(data => {
                    select.innerHTML = '<option value="">-- Chọn môn học --</option>';
                    data.forEach(mon => {
                        const option = document.createElement('option');
                        option.value = mon.ma_mon;
                        option.text = mon.ten_mon;
                        // Lưu hệ số vào data-attribute
                        option.dataset.heso = mon.he_so || 1.0; 
                        select.add(option);
                    });
                    // Cập nhật hệ số môn học nếu có môn được chọn
                    if (data.length > 0) {
                         document.getElementById('mon_hoc').textContent = data[0].he_so;
                         updateTotalCoefficient();
                    } else {
                        document.getElementById('mon_hoc').textContent = '1.0';
                        updateTotalCoefficient();
                    }
                })
                .catch(error => console.error('Error loading subjects:', error));
        } else {
            select.innerHTML = '<option value="">-- Chọn môn học --</option>';
        }
    });
    
    // 3. Cập nhật hệ số khi thay đổi Giáo viên
    document.querySelector('select[name="ma_gv"]').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const heSoGV = selectedOption.dataset.heso || 1.0;
        document.getElementById('he_so_gv').textContent = parseFloat(heSoGV).toFixed(1);
        updateTotalCoefficient();
    });

    // 4. Cập nhật hệ số khi thay đổi Môn học
    document.getElementById('select-monhoc').addEventListener('change', function () {
        const selectedOption = this.options[this.selectedIndex];
        const heSoMon = selectedOption.dataset.heso || 1.0;
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
        taoThuTrongTuan(document.getElementById('so_buoi_tuan').value); 
        
        // Khởi tạo hệ số GV
        const selectedGV = document.querySelector('select[name="ma_gv"]').selectedOptions[0];
        if (selectedGV) {
            document.getElementById('he_so_gv').textContent = (selectedGV.dataset.heso || 1.0).toFixed(1);
        }
        
        // Khởi tạo hệ số Lớp và Tổng
        const soSV = parseInt(document.getElementById('so_sinh_vien').value) || 40;
        document.getElementById('he_so_lop').textContent = tinhHeSoLop(soSV).toFixed(1);
        
        // Khởi tạo hệ số Môn học (Cần kích hoạt load môn học ban đầu nếu có)
        const maKhoaInit = document.getElementById('select-khoa').value;
        if (maKhoaInit) {
             document.getElementById('select-khoa').dispatchEvent(new Event('change'));
        }
        
        // Khởi tạo tổng
        updateTotalCoefficient();
    });
</script>

<?php echo getFooter(); ?>