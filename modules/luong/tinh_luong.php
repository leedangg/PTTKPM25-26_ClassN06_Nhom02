<?php
session_start();
require_once '../../config/database.php';

if (isset($_POST['ma_gv']) && isset($_POST['ma_hk'])) {
    $database = new Database();
    $conn = $database->getConnection();

    try {
        // Lấy thông tin cơ bản của giáo viên
        $sql1 = "SELECT 
                gv.ho_ten,
                bc.he_so as he_so_gv,
                bc.he_so_luong,
                hk.luong_hocky,
                hk.ten_hk,
                hk.nam_hoc
                FROM giaovien gv
                JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                JOIN hoc_ky hk ON hk.ma_hk = :ma_hk
                WHERE gv.ma_gv = :ma_gv";

        $stmt1 = $conn->prepare($sql1);
        $stmt1->execute([
            ':ma_gv' => $_POST['ma_gv'],
            ':ma_hk' => $_POST['ma_hk']
        ]);
        $teacherInfo = $stmt1->fetch(PDO::FETCH_ASSOC);

        // Lấy chi tiết lương theo từng môn
        $sql2 = "SELECT 
                mh.ten_mon,
                ld.ten_lop_hoc,
                COUNT(ld.ma_lich) as so_buoi_day,
                SUM(ld.so_tiet) as tong_so_tiet,
                mh.he_so as he_so_mon,
                ld.he_so_lop,
                SUM(
                    ld.so_tiet * 
                    (mh.he_so + ld.he_so_lop) * 
                    :he_so_gv * 
                    :luong_hocky

                    
                ) as luong_mon
                FROM lich_day ld
                JOIN mon_hoc mh ON ld.ma_mon = mh.ma_mon
                JOIN giaovien gv ON ld.ma_gv = gv.ma_gv
                JOIN bangcap bc ON gv.ma_bangcap = bc.ma_bangcap
                JOIN hoc_ky hk ON ld.ma_hk = hk.ma_hk
                WHERE ld.ma_gv = :ma_gv 
                AND ld.ma_hk = :ma_hk
                GROUP BY mh.ma_mon, ld.ten_lop_hoc
                ORDER BY mh.ten_mon, ld.ten_lop_hoc";

        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute([
            ':ma_gv' => $_POST['ma_gv'],
            ':ma_hk' => $_POST['ma_hk'],
            ':he_so_gv' => $teacherInfo['he_so_gv'],
            ':luong_hocky' => $teacherInfo['luong_hocky']
        ]);
        $subjectDetails = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Tính tổng tiền
        $tongTien = 0;
        foreach ($subjectDetails as $subject) {
            $tongTien += $subject['luong_mon'];
        }

        if ($teacherInfo) {
            $_SESSION['salary_result'] = [
                'ten_gv' => $teacherInfo['ho_ten'],
                'hoc_ky' => $teacherInfo['ten_hk'] . ' ' . $teacherInfo['nam_hoc'],
                'he_so_gv' => $teacherInfo['he_so_gv'],
                'he_so_luong' => $teacherInfo['he_so_luong'],
                'chi_tiet_mon' => $subjectDetails,
                'tong_tien' => $tongTien
            ];
        } else {
            $_SESSION['error'] = "Không tìm thấy thông tin giảng viên";
        }

        // Lấy mã lương mới (tăng tự động)
        $stmtLuong = $conn->query("SELECT ma_luong FROM bang_luong ORDER BY ma_luong DESC LIMIT 1");
        $lastLuong = $stmtLuong->fetch(PDO::FETCH_ASSOC);
        if ($lastLuong) {
            $num = intval(substr($lastLuong['ma_luong'], 2)) + 1;
            $ma_luong = 'BL' . str_pad($num, 4, '0', STR_PAD_LEFT);
        } else {
            $ma_luong = 'BL0001';
        }

        // Tính tổng số tiết
        $tong_so_tiet = array_sum(array_column($subjectDetails, 'tong_so_tiet'));

        // Lưu vào bảng bang_luong
        $stmtInsert = $conn->prepare("INSERT INTO bang_luong (ma_luong, ma_gv, thang, nam, so_tiet, he_so_luong, thuc_lanh, trang_thai, ngay_lap, ghi_chu)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Chờ duyệt', CURDATE(), '')");
        $stmtInsert->execute([
            $ma_luong,
            $_POST['ma_gv'],
            date('n'), // tháng hiện tại
            date('Y'), // năm hiện tại
            $tong_so_tiet,
            $teacherInfo['he_so_luong'],
            $tongTien
        ]);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi truy vấn: " . $e->getMessage();
    }
}

header('Location: index.php');
exit();
