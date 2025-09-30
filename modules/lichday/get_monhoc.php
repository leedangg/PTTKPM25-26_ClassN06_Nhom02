<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$ma_khoa = $_GET['ma_khoa'] ?? '';

$sql = "SELECT ma_mon, ten_mon, he_so 
        FROM mon_hoc 
        WHERE ma_khoa = ? 
        ORDER BY ten_mon";

$stmt = $conn->prepare($sql);
$stmt->execute([$ma_khoa]);
$monhocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($monhocs);
