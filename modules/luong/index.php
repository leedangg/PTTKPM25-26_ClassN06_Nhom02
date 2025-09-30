<?php
require_once '../../config/database.php';
require_once '../header.php';

// Khởi tạo session nếu chưa có để dùng cho $_SESSION['salary_result']
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$conn = $database->getConnection();

// Get teachers list
$sql = "SELECT gv.ma_gv, gv.ho_ten FROM giaovien gv ORDER BY gv.ho_ten";
$teachers = $conn->query($sql)->fetchAll();

// Get semesters list
$sql = "SELECT * FROM hoc_ky ORDER BY nam_hoc DESC, ngay_bat_dau DESC";
$semesters = $conn->query($sql)->fetchAll();

echo getHeader("Tính lương giảng viên");
?>

<style>
    /* ------------------- Global Look & Feel ------------------- */
    body {
        background-color: #f4f7f6;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container-custom {
        max-width: 900px;
        /* Tăng chiều rộng cho bảng kết quả */
        margin-top: 30px;
    }

    /* Card Styling */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(90deg, #1e3c72 0%, #2a5298 100%);
        /* Deep Blue Gradient */
        color: white;
        padding: 1.5rem;
        font-size: 1.4rem;
        font-weight: 700;
        border-bottom: none;
        display: flex;
        align-items: center;
    }

    /* Form & Input Styling */
    .form-control,
    .custom-select {
        border-radius: 8px;
        height: 45px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus,
    .custom-select:focus {
        border-color: #2a5298;
        box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
    }

    .form-group label {
        font-weight: 600;
        color: #343a40;
        margin-bottom: 0.5rem;
    }

    /* Button */
    .btn-primary {
        background: linear-gradient(45deg, #007bff 0%, #0056b3 100%);
        border: none;
        font-weight: 600;
        border-radius: 8px;
        padding: 10px 30px;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        transform: translateY(-1px);
    }

    /* Alert Styling */
    .alert-danger {
        border-left: 5px solid #dc3545;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(220, 53, 69, 0.2);
    }

    /* Result Table Styling (Summary) */
    .summary-table th {
        background-color: #e9ecef;
        font-weight: 700;
        color: #495057;
    }

    /* Result Table Styling (Detail) */
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .table-striped thead th {
        background-color: #007bff;
        color: white;
        font-weight: 600;
        border-color: #007bff !important;
    }

    /* Total Footer Styling */
    .total-footer {
        background-color: #f8f9fa;
        /* Light background for footer */
        border-top: 2px solid #2a5298 !important;
    }

    .badge-total-money {
        background-color: #28a745;
        /* Green for total money */
        color: white;
        font-size: 1.1em !important;
        padding: 0.5em 1em;
        border-radius: 6px;
        font-weight: bold;
    }
</style>

<div class="container-custom mx-auto">
    <div class="card shadow">
        <div class="card-header">
            <i class="fas fa-calculator mr-2"></i>
            Tính lương giảng viên
            <img src="<?= $root_path ?>assets/images/tinhluong.jpg" alt="Calculator" class="header-image ml-auto"
                style="height: 40px; border-radius: 50%; box-shadow: 0 0 5px rgba(0,0,0,0.5);">
        </div>

        <div class="card-body p-4">
            <form method="POST" action="tinh_luong.php">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ma_gv"><i class="fas fa-user-tie mr-1"></i> Giáo viên</label>
                            <select name="ma_gv" id="ma_gv" class="custom-select" required>
                                <option value="">-- Chọn giáo viên --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['ma_gv'] ?>">
                                        <?= htmlspecialchars($teacher['ho_ten']) ?> (Mã:
                                        <?= htmlspecialchars($teacher['ma_gv']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ma_hk"><i class="fas fa-calendar-alt mr-1"></i> Học kỳ</label>
                            <select name="ma_hk" id="ma_hk" class="custom-select" required>
                                <option value="">-- Chọn học kỳ --</option>
                                <?php foreach ($semesters as $semester): ?>
                                    <option value="<?= $semester['ma_hk'] ?>">
                                        <?= htmlspecialchars($semester['ten_hk'] . ' - ' . $semester['nam_hoc']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary shadow">
                        <i class="fas fa-money-check-alt mr-2"></i> Tính lương
                    </button>
                </div>
            </form>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mt-4 shadow-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['salary_result'])): ?>
                <div class="mt-5 border-top pt-4">
                    <h4 class="text-info mb-4"><i class="fas fa-chart-line mr-2"></i> Tóm tắt kết quả tính lương</h4>

                    <div class="row">
                        <div class="col-12">
                            <table class="table table-bordered summary-table shadow-sm">
                                <tr>
                                    <th style="width: 25%"><i class="fas fa-user"></i> Giáo viên:</th>
                                    <td><?= htmlspecialchars($_SESSION['salary_result']['ten_gv']) ?></td>
                                    <th style="width: 25%"><i class="fas fa-calendar-check"></i> Học kỳ:</th>
                                    <td><?= htmlspecialchars($_SESSION['salary_result']['hoc_ky']) ?></td>
                                </tr>
                                <tr>
                                    <th><i class="fas fa-star-half-alt"></i> Hệ số GV:</th>
                                    <td><?= number_format($_SESSION['salary_result']['he_so_gv'], 1) ?></td>
                                    <th><i class="fas fa-coins"></i> Tổng lương (Tạm tính):</th>
                                    <td>
                                        <span class="badge-total-money">
                                            <?= number_format($_SESSION['salary_result']['tong_tien'], 0, ',', '.') ?> VNĐ
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3 text-secondary"><i class="fas fa-table mr-2"></i> Chi tiết lương theo môn học:</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered shadow-sm">
                            <thead>
                                <tr>
                                    <th style="width: 20%">Môn học</th>
                                    <th style="width: 15%">Lớp học</th>
                                    <th class="text-center">Số buổi</th>
                                    <th class="text-center">Tổng tiết</th>
                                    <th class="text-center">Hệ số Môn</th>
                                    <th class="text-center">Hệ số Lớp</th>
                                    <th class="text-right" style="width: 20%">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $currentMon = '';
                                foreach ($_SESSION['salary_result']['chi_tiet_mon'] as $mon):
                                    // Dùng rowClass để tạo dải màu nếu muốn phân biệt môn học
                                    $rowClass = ($currentMon === $mon['ten_mon']) ? 'table-light' : '';
                                    $currentMon = $mon['ten_mon'];
                                    ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td><?= htmlspecialchars($mon['ten_mon']) ?></td>
                                        <td><?= htmlspecialchars($mon['ten_lop_hoc']) ?></td>
                                        <td class="text-center"><?= $mon['so_buoi_day'] ?></td>
                                        <td class="text-center font-weight-bold"><?= $mon['tong_so_tiet'] ?></td>
                                        <td class="text-center"><?= number_format($mon['he_so_mon'], 1) ?></td>
                                        <td class="text-center"><?= number_format($mon['he_so_lop'], 1) ?></td>
                                        <td class="text-right text-success font-weight-bold">
                                            <?= number_format($mon['luong_mon'], 0, ',', '.') ?> VNĐ
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-footer font-weight-bold">
                                    <td colspan="6" class="text-right h5 pt-3">
                                        <i class="fas fa-hand-holding-usd mr-2 text-primary"></i> TỔNG LƯƠNG NHẬN:
                                    </td>
                                    <td class="text-right h5 pt-3">
                                        <span class="badge-total-money">
                                            <?= number_format($_SESSION['salary_result']['tong_tien'], 0, ',', '.') ?> VNĐ
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php unset($_SESSION['salary_result']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php echo getFooter(); ?>