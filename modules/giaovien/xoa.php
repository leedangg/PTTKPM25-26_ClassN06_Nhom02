<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

try {
    $conn->beginTransaction();

    // Xóa các bản ghi liên quan trong bảng bang_luong
    $stmt = $conn->prepare("DELETE FROM bang_luong WHERE ma_gv = ?");
    $stmt->execute([$id]);

    // Xóa giảng viên
    $stmt = $conn->prepare("DELETE FROM giaovien WHERE ma_gv = ?");
    $stmt->execute([$id]);

    $conn->commit();
    header("Location: index.php");
} catch (PDOException $e) {
    $conn->rollBack();
    die("Lỗi xóa giáo viên: " . $e->getMessage());
}
