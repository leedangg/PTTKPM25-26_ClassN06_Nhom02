<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if (!isset($_GET['ma_hk'])) {
    echo json_encode(['error' => 'Missing ma_hk parameter']);
    exit;
}

try {
    // Thống kê số lớp và tổng số sinh viên dựa trên bảng lop_hoc
    $sql = "SELECT 
                m.ma_mon,
                m.ten_mon,
                COUNT(lh.ma_lop) as so_lop,
                SUM(lh.so_sinh_vien) as tong_sv,
                GROUP_CONCAT(lh.ten_lop ORDER BY lh.ten_lop) as danh_sach_lop
            FROM lop_hoc lh
            JOIN mon_hoc m ON lh.ma_mon = m.ma_mon
            WHERE lh.ma_hk = :ma_hk
            GROUP BY m.ma_mon, m.ten_mon
            ORDER BY m.ten_mon";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['ma_hk' => $_GET['ma_hk']]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary
    $summary = [
        'tong_lop' => 0,
        'tong_sv' => 0
    ];

    foreach ($details as $row) {
        $summary['tong_lop'] += $row['so_lop'];
        $summary['tong_sv'] += $row['tong_sv'];
    }

    echo json_encode([
        'details' => $details,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
