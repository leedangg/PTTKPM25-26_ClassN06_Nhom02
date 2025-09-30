<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : die('Lỗi: Không tìm thấy ID');

try {
    // Kiểm tra trong bảng lich_day_dinh_ky
    $check1 = $conn->prepare("SELECT COUNT(*) FROM lich_day_dinh_ky WHERE ma_mon = ?");
    $check1->execute([$id]);
    
    // Kiểm tra trong bảng lich_day
    $check2 = $conn->prepare("SELECT COUNT(*) FROM lich_day WHERE ma_mon = ?");
    $check2->execute([$id]);

    if ($check1->fetchColumn() > 0) {
        die("Không thể xóa vì môn học đang được sử dụng trong lịch dạy định kỳ");
    }
    
    if ($check2->fetchColumn() > 0) {
        die("Không thể xóa vì môn học đang được sử dụng trong lịch dạy");
    }

    $sql = "DELETE FROM mon_hoc WHERE ma_mon = :ma_mon";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':ma_mon' => $id]);

    header("Location: index.php");
} catch (PDOException $e) {
    die("Lỗi xóa môn học: " . $e->getMessage());
}
