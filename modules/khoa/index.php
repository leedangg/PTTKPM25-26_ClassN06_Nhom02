<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Lấy danh sách khoa
$sql = "SELECT * FROM khoa ORDER BY ma_khoa";
$stmt = $conn->query($sql);
$khoas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo getHeader("Quản lý Khoa");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <a href="them.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm Khoa
            </a>
        </div>

        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($khoas as $khoa): ?>
                    <tr>
                        <td><?= htmlspecialchars($khoa['ma_khoa']) ?></td>
                        <td><?= htmlspecialchars($khoa['ten_khoa']) ?></td>
                        <td>
                            <a href="sua.php?id=<?= $khoa['ma_khoa'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <a href="xoa.php?id=<?= $khoa['ma_khoa'] ?>"
                                onclick="return confirm('Bạn có chắc muốn xóa khoa này?')" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo getFooter(); ?>