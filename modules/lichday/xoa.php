<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

try {
    // Bắt đầu transaction
    $conn->beginTransaction();

    // Xóa các bản ghi dạy thay liên quan
    $sql = "DELETE FROM day_thay WHERE ma_lich = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Xóa các bản ghi điểm danh liên quan
    $sql = "DELETE FROM diem_danh WHERE ma_lich = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    // Sau đó mới xóa lịch dạy
    $sql = "DELETE FROM lich_day WHERE ma_lich = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);

    $conn->commit();
    header("Location: index.php");
} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi xóa lịch dạy: " . $e->getMessage());
}
