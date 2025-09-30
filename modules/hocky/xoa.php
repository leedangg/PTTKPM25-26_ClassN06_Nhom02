<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : header('Location: index.php');

try {
    // Kiểm tra xem có lịch dạy không
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lich_day WHERE ma_hk = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Không thể xóa kỳ học đã có lịch dạy!");
    }

    $stmt = $conn->prepare("DELETE FROM hoc_ky WHERE ma_hk = ?");
    $stmt->execute([$id]);

    header('Location: index.php?success=3');
} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage());
}
