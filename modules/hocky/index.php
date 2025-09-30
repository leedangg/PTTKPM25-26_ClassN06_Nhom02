<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy tham số thông báo thành công (nếu có)
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

// Update query to get status and count of lich_day
$sql = "SELECT hk.*, 
        (SELECT COUNT(*) FROM lich_day WHERE ma_hk = hk.ma_hk) as so_lich_day
        FROM hoc_ky hk
        ORDER BY hk.nam_hoc DESC, hk.ngay_bat_dau DESC";

$hockys = $conn->query($sql)->fetchAll();


echo getHeader("Quản lý Kỳ học");
?>

<style>
    /* ------------------- Global Look & Feel ------------------- */
    body {
        background-color: #f4f7f6;
        /* Light background */
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container-custom {
        max-width: 1200px;
        margin-top: 30px;
    }

    /* Card Styling */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        /* Elegant shadow */
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(90deg, #17a2b8 0%, #138496 100%);
        /* Info Gradient */
        color: white;
        padding: 1.5rem;
        font-size: 1.2rem;
        font-weight: 700;
        border-bottom: none;
    }

    /* Table Styling */
    .table-bordered th,
    .table-bordered td {
        border-color: #dee2e6 !important;
    }

    .table-hover tbody tr:hover {
        background-color: #e9f5ff;
        /* Light blue hover effect */
        transition: background-color 0.2s;
    }

    .thead-light th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(45deg, #28a745 0%, #1e7e34 100%);
        /* Green for Add New */
        border: none;
        font-weight: 600;
        border-radius: 8px;
        padding: 8px 20px;
        transition: all 0.3s;
    }

    .btn-primary:hover {
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        transform: translateY(-1px);
    }

    .btn-sm {
        border-radius: 6px;
        margin: 2px;
    }

    /* Action Buttons */
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }

    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    /* Alert Styling */
    .alert-success {
        border-left: 5px solid #28a745;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(40, 167, 69, 0.2);
    }

    .alert-danger {
        border-left: 5px solid #dc3545;
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

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle mr-2"></i>
            <?php
            switch ($_GET['success']) {
                case 1:
                    echo "Thêm kỳ học mới thành công!";
                    break;
                case 2:
                    echo "Cập nhật kỳ học thành công!";
                    break;
                case 3:
                    echo "Xóa kỳ học thành công!";
                    break;
            }
            ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php elseif ($success_message): // Dùng cho thông báo redirect từ file khác ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i> Danh sách kỳ học</h5>
                <a href="them.php" class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus"></i> Thêm kỳ học mới
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Mã HK</th>
                            <th>Tên kỳ học</th>
                            <th>Năm học</th>
                            <th>Thời gian</th>
                            <th class="text-center">Số lịch dạy</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-center" style="width: 250px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hockys as $hk): ?>
                            <tr>
                                <td class="font-weight-bold"><?= $hk['ma_hk'] ?></td>
                                <td><?= htmlspecialchars($hk['ten_hk']) ?></td>
                                <td><?= htmlspecialchars($hk['nam_hoc']) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($hk['ngay_bat_dau'])) ?> -
                                    <?= date('d/m/Y', strtotime($hk['ngay_ket_thuc'])) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary p-2"><?= $hk['so_lich_day'] ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    // Logic xác định trạng thái và class badge
                                    $current_date = date('Y-m-d');
                                    $start_date = $hk['ngay_bat_dau'];
                                    $end_date = $hk['ngay_ket_thuc'];
                                    $status = $hk['trang_thai'] ?? 'Sắp diễn ra';

                                    if ($current_date < $start_date) {
                                        $status = 'Sắp diễn ra';
                                    } elseif ($current_date >= $start_date && $current_date <= $end_date) {
                                        $status = 'Đang diễn ra';
                                    } elseif ($current_date > $end_date) {
                                        $status = 'Đã kết thúc';
                                    }

                                    // Cập nhật trạng thái vào DB nếu cần (Tuy nhiên, phần này nên đặt trong logic tự động hoặc trigger)
                                    // Giả định trạng thái trong DB được cập nhật đúng. Sử dụng trạng thái từ DB nếu có.
                                
                                    $display_status = htmlspecialchars($hk['trang_thai'] ?? $status);

                                    $badge_class = match ($display_status) {
                                        'Sắp diễn ra' => 'info',
                                        'Đang diễn ra' => 'success',
                                        'Đã kết thúc' => 'secondary',
                                        default => 'info'
                                    };
                                    ?>
                                    <span class="badge badge-<?= $badge_class ?> p-2 shadow-sm">
                                        <?= $display_status ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="sua.php?id=<?= $hk['ma_hk'] ?>" class="btn btn-warning btn-sm text-white"
                                        title="Sửa thông tin">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>

                                    <?php if ($display_status === 'Sắp diễn ra'): ?>
                                        <a href="doi_trang_thai.php?id=<?= $hk['ma_hk'] ?>&action=start"
                                            class="btn btn-success btn-sm" title="Bắt đầu kỳ học này">
                                            <i class="fas fa-play"></i> Bắt đầu
                                        </a>
                                        <button onclick="xoaKyHoc('<?= $hk['ma_hk'] ?>')" class="btn btn-danger btn-sm"
                                            title="Xóa kỳ học">
                                            <i class="fas fa-trash-alt"></i> Xóa
                                        </button>
                                    <?php elseif ($display_status === 'Đang diễn ra'): ?>
                                        <a href="doi_trang_thai.php?id=<?= $hk['ma_hk'] ?>&action=end"
                                            class="btn btn-danger btn-sm" title="Kết thúc kỳ học này">
                                            <i class="fas fa-stop"></i> Kết thúc
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function xoaKyHoc(maHK) {
        if (confirm('⚠️ Cảnh báo! Bạn có chắc chắn muốn xóa kỳ học này không? Hành động này không thể hoàn tác.')) {
            window.location.href = `xoa.php?id=${maHK}`;
        }
    }
</script>

<?php echo getFooter(); ?>