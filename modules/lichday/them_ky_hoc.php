<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: lichday_dinhky.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

function taoMaHK($conn)
{
    $sql = "SELECT ma_hk FROM hoc_ky ORDER BY ma_hk DESC LIMIT 1";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        $lastCode = $stmt->fetch(PDO::FETCH_ASSOC)['ma_hk'];
        $number = intval(substr($lastCode, 2)) + 1;
        return 'HK' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    return 'HK001';
}

try {
    // Format dates from dd/mm/yyyy to yyyy-mm-dd for database
    $ngay_bat_dau = DateTime::createFromFormat('d/m/Y', $_POST['ngay_bat_dau'])->format('Y-m-d');
    $ngay_ket_thuc = DateTime::createFromFormat('d/m/Y', $_POST['ngay_ket_thuc'])->format('Y-m-d');

    // Standardize input data
    $_POST['ten_hk'] = trim($_POST['ten_hk']);
    $_POST['nam_hoc'] = trim($_POST['nam_hoc']);

    // Check for existing semester in that year first
    $check_semester = "SELECT COUNT(*) FROM hoc_ky 
                      WHERE LOWER(ten_hk) = LOWER(:ten_hk) 
                      AND nam_hoc = :nam_hoc";

    $stmt_semester = $conn->prepare($check_semester);
    $stmt_semester->execute([
        ':ten_hk' => $_POST['ten_hk'],
        ':nam_hoc' => $_POST['nam_hoc']
    ]);

    if ($stmt_semester->fetchColumn() > 0) {
        header("Location: lichday_dinhky.php?error=" . urlencode("Đã tồn tại học kỳ " . $_POST['ten_hk'] . " trong năm học " . $_POST['nam_hoc']));
        exit();
    }

    // Validate dates
    if ($ngay_bat_dau >= $ngay_ket_thuc) {
        header("Location: lichday_dinhky.php?error=" . urlencode("Ngày bắt đầu phải trước ngày kết thúc!"));
        exit();
    }

    // Check for exact time period match
    $check_exact = "SELECT COUNT(*) FROM hoc_ky 
                   WHERE ngay_bat_dau = :ngay_bat_dau 
                   AND ngay_ket_thuc = :ngay_ket_thuc";

    $stmt_exact = $conn->prepare($check_exact);
    $stmt_exact->execute([
        ':ngay_bat_dau' => $ngay_bat_dau,
        ':ngay_ket_thuc' => $ngay_ket_thuc
    ]);

    if ($stmt_exact->fetchColumn() > 0) {
        header("Location: lichday_dinhky.php?error=" . urlencode("Đã tồn tại học kỳ với cùng thời gian bắt đầu và kết thúc!"));
        exit();
    }

    // Check for date overlap with existing semesters
    $check_overlap = "SELECT COUNT(*) FROM hoc_ky 
                     WHERE (:nam_hoc = nam_hoc)
                     AND (
                         (ngay_bat_dau <= :ngay_ket_thuc AND ngay_ket_thuc >= :ngay_bat_dau)
                         OR 
                         (:ngay_bat_dau BETWEEN ngay_bat_dau AND ngay_ket_thuc)
                         OR 
                         (:ngay_ket_thuc BETWEEN ngay_bat_dau AND ngay_ket_thuc)
                     )";

    $stmt_overlap = $conn->prepare($check_overlap);
    $stmt_overlap->execute([
        ':nam_hoc' => $_POST['nam_hoc'],
        ':ngay_bat_dau' => $ngay_bat_dau,
        ':ngay_ket_thuc' => $ngay_ket_thuc
    ]);

    if ($stmt_overlap->fetchColumn() > 0) {
        header("Location: lichday_dinhky.php?error=" . urlencode("Thời gian học kỳ bị trùng với học kỳ khác trong cùng năm học!"));
        exit();
    }

    $ma_hk = taoMaHK($conn);
    $sql = "INSERT INTO hoc_ky (ma_hk, ten_hk, nam_hoc, ngay_bat_dau, ngay_ket_thuc) 
            VALUES (:ma_hk, :ten_hk, :nam_hoc, :ngay_bat_dau, :ngay_ket_thuc)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':ma_hk' => $ma_hk,
        ':ten_hk' => $_POST['ten_hk'],
        ':nam_hoc' => $_POST['nam_hoc'],
        ':ngay_bat_dau' => $_POST['ngay_bat_dau'],
        ':ngay_ket_thuc' => $_POST['ngay_ket_thuc']
    ]);

    header("Location: lichday_dinhky.php?success=1");
} catch (PDOException $e) {
    header("Location: lichday_dinhky.php?error=" . urlencode("Thời gian học kỳ bị trùng với học kỳ khác trong cùng năm học!"));
}
