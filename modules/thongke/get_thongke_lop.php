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
    // Get statistics per subject
    $sql = "SELECT 
                m.ma_mon,
                m.ten_mon,
                COUNT(DISTINCT l.ten_lop_hoc) as so_lop,
                SUM(l.so_sinh_vien) as tong_sv,
                AVG(l.so_sinh_vien) as tb_sv_lop,
                AVG(l.he_so_lop) as tb_he_so
            FROM lich_day l
            JOIN mon_hoc m ON l.ma_mon = m.ma_mon
            WHERE l.ma_hk = ?
            GROUP BY m.ma_mon, m.ten_mon
            ORDER BY m.ten_mon";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$_GET['ma_hk']]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate summary
    $summary = [
        'tong_lop' => 0,
        'tong_sv' => 0,
        'tb_sv' => 0,
        'tb_he_so' => 0
    ];

    foreach ($details as $row) {
        $summary['tong_lop'] += $row['so_lop'];
        $summary['tong_sv'] += $row['tong_sv'];
    }

    if ($summary['tong_lop'] > 0) {
        $summary['tb_sv'] = $summary['tong_sv'] / $summary['tong_lop'];
        $summary['tb_he_so'] = array_sum(array_column($details, 'tb_he_so')) / count($details);
    }

    echo json_encode([
        'details' => $details,
        'summary' => $summary
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
