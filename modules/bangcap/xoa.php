<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

try {
    // Kiểm tra xem bằng cấp có đang được sử dụng không
    $check = $conn->prepare("SELECT COUNT(*) FROM giaovien WHERE ma_bangcap = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        die("Không thể xóa vì bằng cấp đang được sử dụng");
    }

    $stmt = $conn->prepare("DELETE FROM bangcap WHERE ma_bangcap = ?");
    $stmt->execute([$id]);

    header("Location: index.php");
} catch (PDOException $e) {
    die("Lỗi xóa bằng cấp: " . $e->getMessage());
}
