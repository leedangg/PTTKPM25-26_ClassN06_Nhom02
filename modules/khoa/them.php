<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

function taoMaKhoa($conn)
{
    $sql = "SELECT ma_khoa FROM khoa ORDER BY ma_khoa DESC LIMIT 1";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['ma_khoa'];
        $number = intval(substr($lastCode, 1)) + 1;
        return 'K' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    return 'K001';
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Bắt buộc điền đầy đủ thông tin
        if (!isset($_POST['ten_khoa']) || trim($_POST['ten_khoa']) === '') {
            throw new Exception("Vui lòng điền đầy đủ thông tin!");
        }
        // Kiểm tra tên khoa đã tồn tại chưa (không phân biệt hoa thường)
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM khoa WHERE LOWER(ten_khoa) = LOWER(?)");
        $stmtCheck->execute([trim($_POST['ten_khoa'])]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "Khoa đã tồn tại!";
        } else {
            $ma_khoa = taoMaKhoa($conn);
            $sql = "INSERT INTO khoa (ma_khoa, ten_khoa) VALUES (:ma_khoa, :ten_khoa)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':ma_khoa' => $ma_khoa,
                ':ten_khoa' => $_POST['ten_khoa']
            ]);
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

echo getHeader("Thêm Khoa");
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>Thêm khoa mới</h5>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="form-group">
                <label><i class="fas fa-building mr-2"></i>Tên khoa</label>
                <input type="text" name="ten_khoa" class="form-control shadow-sm" placeholder="Nhập tên khoa" required>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save mr-2"></i>Thêm khoa
                </button>
                <a href="index.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
            </div>
        </form>
    </div>
</div>

<?php echo getFooter(); ?>