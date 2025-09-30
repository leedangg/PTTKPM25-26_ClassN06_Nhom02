<?php
require_once '../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$ma_mon = $_GET['ma_mon'] ?? '';

$sql = "SELECT COALESCE(he_so, 1.0) as he_so 
        FROM mon_hoc 
        WHERE ma_mon = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$ma_mon]);
$result = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode(['he_so' => $result['he_so'] ?? 1.0]);
