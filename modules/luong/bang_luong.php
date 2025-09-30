<?php
session_start();
require_once '../../config/database.php';

if (isset($_POST['ma_gv']) && isset($_POST['ma_hk'])) {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // 1. Lấy thông tin cơ bản của giáo viên
        $sql = "SELECT 
                    gv.ho_ten,
                    gv.luong_co_ban,     -- Lương cơ bản
                    bc.he_so AS he_so_luong, -- Hệ số bằng cấp
                    hk.ten_hk,
                    hk.nam_hoc
                FROM giaovien gv
                JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                JOIN hoc_ky hk ON hk.ma_hk = :ma_hk
                WHERE gv.ma_gv = :ma_gv";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':ma_gv' => $_POST['ma_gv'],
            ':ma_hk' => $_POST['ma_hk']
        ]);

        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacher || !$teacher['luong_co_ban']) {
            $_SESSION['error'] = "Không tìm thấy giáo viên hoặc lương cơ bản chưa thiết lập.";
            header('Location: index.php');
            exit();
        }

        // 2. Tính lương tổng
        $luong_cb = $teacher['he_so_luong'] * $teacher['luong_co_ban'];
        $tong_thuc_nhan = $luong_cb * 1.195;

        // 3. Lấy mã lương mới
        $stmtLuong = $conn->query("SELECT ma_luong FROM bang_luong ORDER BY ma_luong DESC LIMIT 1");
        $lastLuong = $stmtLuong->fetch(PDO::FETCH_ASSOC);
        $ma_luong = $lastLuong ? 'BL' . str_pad(intval(substr($lastLuong['ma_luong'], 2)) + 1, 4, '0', STR_PAD_LEFT) : 'BL0001';

        // 4. Lưu vào bang_luong
        $stmtInsert = $conn->prepare("INSERT INTO bang_luong 
            (ma_luong, ma_gv, thang, nam, so_tiet, he_so_luong, thuc_lanh, trang_thai, ngay_lap, ghi_chu)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ duyệt', CURDATE(), ?)");
        $stmtInsert->execute([
            $ma_luong,
            $_POST['ma_gv'],
            date('n'),
            date('Y'),
            0,
            $teacher['he_so_luong'],
            $tong_thuc_nhan,
            "Lương cơ bản: {$teacher['luong_co_ban']}, Hệ số bằng cấp: {$teacher['he_so_luong']}"
        ]);

        // 5. Lưu session để hiển thị
        $_SESSION['salary_result'] = [
            'ten_gv' => $teacher['ho_ten'],
            'hoc_ky' => $teacher['ten_hk'] . ' ' . $teacher['nam_hoc'],
            'he_so_luong' => $teacher['he_so_luong'],
            'luong_co_ban' => $teacher['luong_co_ban'],
            'tong_thuc_nhan' => $tong_thuc_nhan
        ];

    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi truy vấn: " . $e->getMessage();
    }
}

header('Location: index.php');
exit();
