<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = $_GET['id'] ?? '';
$action = $_GET['action'] ?? '';

if ($id && $action) {
    try {
        if ($action === 'start') {
            // Kiểm tra nếu đã có học kỳ đang diễn ra
            $stmtCheck = $conn->query("SELECT COUNT(*) FROM hoc_ky WHERE trang_thai = 'Đang diễn ra'");
            if ($stmtCheck->fetchColumn() > 0) {
                $error = "Có học kỳ đang diễn ra";
                include 'index.php';
                exit();
            }
            $trang_thai = 'Đang diễn ra';
        } else {
            $trang_thai = 'Đã kết thúc';
        }

        $sql = "UPDATE hoc_ky SET trang_thai = :trang_thai WHERE ma_hk = :ma_hk";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':trang_thai' => $trang_thai,
            ':ma_hk' => $id
        ]);

        header("Location: index.php?success=4"); // 4 = Status changed
        exit();
    } catch (PDOException $e) {
        $error = $e->getMessage();
        include 'index.php';
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
