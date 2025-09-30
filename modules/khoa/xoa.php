<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

try {
    // Kiểm tra xem khoa có giảng viên không
    $check = $conn->prepare("SELECT COUNT(*) FROM giaovien WHERE ma_khoa = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        die("Không thể xóa vì khoa đang có giảng viên");
    }

    $stmt = $conn->prepare("DELETE FROM khoa WHERE ma_khoa = ?");
    $stmt->execute([$id]);

    header("Location: index.php");
} catch (PDOException $e) {
    die("Lỗi xóa khoa: " . $e->getMessage());
}
