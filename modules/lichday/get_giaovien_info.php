<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$ma_gv = $_GET['ma_gv'] ?? '';

$sql = "SELECT g.ma_gv, g.ho_ten, b.he_so as he_so_gv 
        FROM giaovien g 
        JOIN bangcap b ON g.ma_bangcap = b.ma_bangcap
        WHERE g.ma_gv = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$ma_gv]);
$result = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode([
    'he_so_gv' => $result['he_so_gv'] ?? 1.0
]);
