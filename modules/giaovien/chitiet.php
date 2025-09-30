<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

// Lấy thông tin chi tiết giảng viên
$sql = "SELECT gv.*, k.ten_khoa, bc.ten_bangcap, bc.he_so_luong, gv.luong_co_ban, u.password as mat_khau
        FROM giaovien gv
        LEFT JOIN khoa k ON gv.ma_khoa = k.ma_khoa
        LEFT JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
        LEFT JOIN users u ON gv.ma_gv = u.ma_gv
        WHERE gv.ma_gv = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$giaovien = $stmt->fetch(PDO::FETCH_ASSOC);

echo getHeader("Chi tiết giảng viên");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0"><i class="fas fa-user"></i> Thông tin chi tiết giảng viên</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <?php
                    $avatarUrl = !empty($giaovien['avatar'])
                        ? "/assets/img/giaovien/" . htmlspecialchars($giaovien['avatar'], ENT_QUOTES, 'UTF-8')
                        : "/assets/img/avatar-placeholder.png";
                    ?>
                    <img src="<?= $avatarUrl ?>" alt="Ảnh đại diện" class="img-thumbnail mb-3"
                        style="width: 200px; height: 200px; object-fit: cover;">
                </div>
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Họ và tên</th>
                                    <td><?= htmlspecialchars($giaovien['ho_ten'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th>Giới tính</th>
                                    <td><?= htmlspecialchars($giaovien['gioi_tinh'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th>Ngày sinh</th>
                                    <td><?= !empty($giaovien['ngay_sinh']) ? date('d/m/Y', strtotime($giaovien['ngay_sinh'])) : '' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($giaovien['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại</th>
                                    <td><?= htmlspecialchars($giaovien['so_dien_thoai'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Khoa</th>
                                    <td><?= htmlspecialchars($giaovien['ten_khoa'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th>Bằng cấp</th>
                                    <td><?= htmlspecialchars($giaovien['ten_bangcap'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Lương cơ bản</th>
                                    <td><?= !empty($giaovien['luong_co_ban']) ? number_format($giaovien['luong_co_ban'] ?? 0, 0) : '' ?>
                                        VNĐ</td>
                                </tr>
                                <tr>
                                    <th>Ngày vào làm</th>
                                    <td><?= !empty($giaovien['ngay_vao_lam']) ? date('d/m/Y', strtotime($giaovien['ngay_vao_lam'])) : '' ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ</th>
                                    <td><?= nl2br(htmlspecialchars($giaovien['dia_chi'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Mật khẩu</th>
                                    <td><?= htmlspecialchars($giaovien['mat_khau'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- Hiển thị mật khẩu -->
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="sua.php?id=<?= htmlspecialchars($giaovien['ma_gv'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    class="btn btn-warning btn-sm" title="Sửa thông tin">
                    <i class="fas fa-edit"></i> Sửa
                </a>
                <a href="index.php" class="btn btn-secondary btn-sm" title="Quay lại danh sách">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>
</div>