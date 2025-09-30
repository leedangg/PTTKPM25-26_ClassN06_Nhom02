-- Tạo database
CREATE DATABASE IF NOT EXISTS quanly_giangvien;
USE quanly_giangvien;

-- Drop all functions and triggers first
DROP FUNCTION IF EXISTS remove_dau;
DROP FUNCTION IF EXISTS tinh_so_tiet_quy_doi;
DROP FUNCTION IF EXISTS tinh_he_so_lop;
DROP TRIGGER IF EXISTS after_giaovien_insert;
DROP TRIGGER IF EXISTS before_lich_day_insert;

-- Create functions
DELIMITER //
CREATE FUNCTION remove_dau(str VARCHAR(100)) 
RETURNS VARCHAR(100)
BEGIN
    SET str = LOWER(str);
    SET str = REPLACE(str, 'á', 'a');
    SET str = REPLACE(str, 'à', 'a');
    SET str = REPLACE(str, 'ả', 'a');
    SET str = REPLACE(str, 'ã', 'a');
    SET str = REPLACE(str, 'ạ', 'a');
    SET str = REPLACE(str, 'ă', 'a');
    SET str = REPLACE(str, 'ắ', 'a');
    SET str = REPLACE(str, 'ằ', 'a');
    SET str = REPLACE(str, 'ẳ', 'a');
    SET str = REPLACE(str, 'ẵ', 'a');
    SET str = REPLACE(str, 'ặ', 'a');
    SET str = REPLACE(str, 'â', 'a');
    SET str = REPLACE(str, 'ấ', 'a');
    SET str = REPLACE(str, 'ầ', 'a');
    SET str = REPLACE(str, 'ẩ', 'a');
    SET str = REPLACE(str, 'ẫ', 'a');
    SET str = REPLACE(str, 'ậ', 'a');
    SET str = REPLACE(str, 'đ', 'd');
    SET str = REPLACE(str, 'é', 'e');
    SET str = REPLACE(str, 'è', 'e');
    SET str = REPLACE(str, 'ẻ', 'e');
    SET str = REPLACE(str, 'ẽ', 'e');
    SET str = REPLACE(str, 'ẹ', 'e');
    SET str = REPLACE(str, 'ê', 'e');
    SET str = REPLACE(str, 'ế', 'e');
    SET str = REPLACE(str, 'ề', 'e');
    SET str = REPLACE(str, 'ể', 'e');
    SET str = REPLACE(str, 'ễ', 'e');
    SET str = REPLACE(str, 'ệ', 'e');
    SET str = REPLACE(str, 'í', 'i');
    SET str = REPLACE(str, 'ì', 'i');
    SET str = REPLACE(str, 'ỉ', 'i');
    SET str = REPLACE(str, 'ĩ', 'i');
    SET str = REPLACE(str, 'ị', 'i');
    SET str = REPLACE(str, 'ó', 'o');
    SET str = REPLACE(str, 'ò', 'o');
    SET str = REPLACE(str, 'ỏ', 'o');
    SET str = REPLACE(str, 'õ', 'o');
    SET str = REPLACE(str, 'ọ', 'o');
    SET str = REPLACE(str, 'ô', 'o');
    SET str = REPLACE(str, 'ố', 'o');
    SET str = REPLACE(str, 'ồ', 'o');
    SET str = REPLACE(str, 'ổ', 'o');
    SET str = REPLACE(str, 'ỗ', 'o');
    SET str = REPLACE(str, 'ộ', 'o');
    SET str = REPLACE(str, 'ơ', 'o');
    SET str = REPLACE(str, 'ớ', 'o');
    SET str = REPLACE(str, 'ờ', 'o');
    SET str = REPLACE(str, 'ở', 'o');
    SET str = REPLACE(str, 'ỡ', 'o');
    SET str = REPLACE(str, 'ợ', 'o');
    SET str = REPLACE(str, 'ú', 'u');
    SET str = REPLACE(str, 'ù', 'u');
    SET str = REPLACE(str, 'ủ', 'u');
    SET str = REPLACE(str, 'ũ', 'u');
    SET str = REPLACE(str, 'ụ', 'u');
    SET str = REPLACE(str, 'ư', 'u');
    SET str = REPLACE(str, 'ứ', 'u');
    SET str = REPLACE(str, 'ừ', 'u');
    SET str = REPLACE(str, 'ử', 'u');
    SET str = REPLACE(str, 'ữ', 'u');
    SET str = REPLACE(str, 'ự', 'u');
    SET str = REPLACE(str, 'ý', 'y');
    SET str = REPLACE(str, 'ỳ', 'y');
    SET str = REPLACE(str, 'ỷ', 'y');
    SET str = REPLACE(str, 'ỹ', 'y');
    SET str = REPLACE(str, 'ỵ', 'y');
    
    -- Remove spaces and special characters
    SET str = REPLACE(str, ' ', '');
    SET str = REPLACE(str, '-', '');
    SET str = REPLACE(str, '.', '');
    RETURN str;
END //

CREATE FUNCTION tinh_so_tiet_quy_doi(so_tiet INT, ma_mon VARCHAR(10), so_sv INT) 
RETURNS INT
BEGIN
    DECLARE he_so DECIMAL(3,1);
    DECLARE so_tiet_quy_doi INT;

    -- Lấy hệ số của môn học
    SELECT m.he_so INTO he_so
    FROM mon_hoc m
    WHERE m.ma_mon = ma_mon;

    -- Tính số tiết quy đổi
    SET so_tiet_quy_doi = so_tiet * he_so / so_sv;

    RETURN so_tiet_quy_doi;
END //

CREATE FUNCTION tinh_he_so_lop(so_sv INT) 
RETURNS DECIMAL(3,1)
BEGIN
    DECLARE he_so DECIMAL(3,1);

    -- Tính hệ số lớp dựa trên số sinh viên
    IF so_sv <= 20 THEN
        SET he_so = 1.5;
    ELSEIF so_sv <= 40 THEN
        SET he_so = 1.2;
    ELSE
        SET he_so = 1.0;
    END IF;

    RETURN he_so;
END //
DELIMITER ;

-- Drop tables in correct dependency order
DROP TABLE IF EXISTS thanh_toan_luong;
DROP TABLE IF EXISTS bang_luong;
DROP TABLE IF EXISTS diem_danh;
DROP TABLE IF EXISTS day_thay;
DROP TABLE IF EXISTS buoi_day;
DROP TABLE IF EXISTS lich_day CASCADE;
DROP TABLE IF EXISTS lich_day_dinh_ky;
DROP TABLE IF EXISTS lop_hoc;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS giaovien;
DROP TABLE IF EXISTS mon_hoc;
DROP TABLE IF EXISTS bangcap;
DROP TABLE IF EXISTS hoc_ky;
DROP TABLE IF EXISTS khoa;

-- Create base tables first (no foreign keys)
CREATE TABLE khoa (
    ma_khoa VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ten_khoa VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bangcap (
    ma_bangcap VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ten_bangcap VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    he_so_luong DECIMAL(10,2) ,
    he_so DECIMAL(3,1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hoc_ky (
    ma_hk VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ten_hk VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    ngay_bat_dau DATE NOT NULL,
    ngay_ket_thuc DATE NOT NULL, 
    nam_hoc VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    luong_hocky DECIMAL(18,2) NOT NULL DEFAULT 0,
    trang_thai ENUM('Sắp diễn ra', 'Đang diễn ra', 'Đã kết thúc') DEFAULT 'Sắp diễn ra',
    UNIQUE KEY unique_semester_year (ten_hk, nam_hoc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tables with single foreign key dependencies
CREATE TABLE mon_hoc (
    ma_mon VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ten_mon VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    so_tiet INT NOT NULL,
    so_tin_chi INT NOT NULL DEFAULT 2,
    mo_ta TEXT,
    ma_khoa VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    he_so DECIMAL(3,1) DEFAULT 1.0,
    FOREIGN KEY (ma_khoa) REFERENCES khoa(ma_khoa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE giaovien (
    
    ma_gv VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ho_ten VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    gioi_tinh ENUM('Nam', 'Nữ') NOT NULL,
    ngay_sinh DATE,
    dia_chi TEXT,
    email VARCHAR(100) UNIQUE,
    so_dien_thoai VARCHAR(15),
    ma_khoa VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_bangcap VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ngay_vao_lam DATE,
    luong_co_ban DECIMAL(18,2) NULL DEFAULT NULL, -- NEW: lương cơ bản (có thể NULL)
    avatar VARCHAR(255) NULL, -- NEW: tên file ảnh/avatar (nullable)
    trang_thai TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ma_khoa) REFERENCES khoa(ma_khoa),
    FOREIGN KEY (ma_bangcap) REFERENCES bangcap(ma_bangcap)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table after giaovien
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci UNIQUE NOT NULL,
    password VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    ma_gv VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci UNIQUE,
    role ENUM('admin', 'teacher', 'accountant') NOT NULL DEFAULT 'teacher',
    active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (ma_gv) REFERENCES giaovien(ma_gv) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create lich_day_dinh_ky after all its dependencies exist
CREATE TABLE lich_day_dinh_ky (
    ma_lich_dk VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ma_gv VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_mon VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_hk VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    thu_trong_tuan INT NOT NULL,
    tiet_bat_dau INT NOT NULL,
    so_tiet INT NOT NULL,
    phong_hoc VARCHAR(20),
    so_buoi_tuan INT NOT NULL DEFAULT 1,
    so_sinh_vien INT DEFAULT 40,
    FOREIGN KEY (ma_gv) REFERENCES giaovien(ma_gv) ON DELETE SET NULL,
    FOREIGN KEY (ma_mon) REFERENCES mon_hoc(ma_mon) ON DELETE CASCADE,
    FOREIGN KEY (ma_hk) REFERENCES hoc_ky(ma_hk) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Lớp học
CREATE TABLE lop_hoc (
    ma_lop VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ten_lop VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    so_sinh_vien INT DEFAULT 40,
    ma_mon VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_hk VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    FOREIGN KEY (ma_mon) REFERENCES mon_hoc(ma_mon) ON DELETE CASCADE,
    FOREIGN KEY (ma_hk) REFERENCES hoc_ky(ma_hk) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Lịch dạy (đã được hợp nhất với bảng Lớp học)
CREATE TABLE lich_day (
    ma_lich VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ma_gv VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_mon VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_hk VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ngay_day DATE NOT NULL,
    tiet_bat_dau INT NOT NULL,
    so_tiet INT NOT NULL,
    phong_hoc VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ten_lop VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    ten_lop_hoc VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    so_sinh_vien INT DEFAULT 40,
    thu_trong_tuan INT,
    so_buoi_tuan INT DEFAULT 1,
    la_lich_dinh_ky BOOLEAN DEFAULT FALSE,
    loai_lich ENUM('Định kỳ', 'Thường', 'Bù', 'Thay') DEFAULT 'Thường',
    trang_thai ENUM('Đã dạy', 'Chưa điểm danh', 'Vắng', 'Nghỉ', 'Bù', 'Thay', 'Đã có lịch bù') 
        DEFAULT 'Chưa điểm danh',
    ma_lich_goc VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ghi_chu TEXT,
    he_so_lop DECIMAL(3,1) DEFAULT 0,
    FOREIGN KEY (ma_gv) REFERENCES giaovien(ma_gv) ON DELETE SET NULL,
    FOREIGN KEY (ma_mon) REFERENCES mon_hoc(ma_mon) ON DELETE CASCADE,
    FOREIGN KEY (ma_hk) REFERENCES hoc_ky(ma_hk) ON DELETE CASCADE,
    FOREIGN KEY (ma_lich_goc) REFERENCES lich_day(ma_lich) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Buổi dạy
CREATE TABLE buoi_day (
    ma_buoi VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ma_gv VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_mon VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ngay_day DATE NOT NULL,
    tiet_bat_dau INT NOT NULL,
    so_tiet INT NOT NULL, 
    phong_hoc VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    trang_thai ENUM('Đã dạy', 'Chưa điểm danh', 'Vắng', 'Bù', 'Thay') DEFAULT 'Chưa điểm danh',
    ghi_chu TEXT,
    FOREIGN KEY (ma_gv) REFERENCES giaovien(ma_gv) ON DELETE SET NULL,
    FOREIGN KEY (ma_mon) REFERENCES mon_hoc(ma_mon) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Điểm danh
CREATE TABLE diem_danh (
    ma_diem_danh VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ma_lich VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    trang_thai ENUM('Có mặt', 'Vắng mặt', 'Dạy bù', 'Dạy thay') NOT NULL,
    ghi_chu TEXT,
    nguoi_day_thay VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ngay_diem_danh TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ma_lich) REFERENCES lich_day(ma_lich),
    FOREIGN KEY (nguoi_day_thay) REFERENCES giaovien(ma_gv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Dạy thay
CREATE TABLE day_thay (
    ma_day_thay VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ma_lich VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ma_gv_thay VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    ly_do TEXT,  
    ngay_dang_ky TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ma_lich) REFERENCES lich_day(ma_lich),
    FOREIGN KEY (ma_gv_thay) REFERENCES giaovien(ma_gv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng Bảng lương
CREATE TABLE bang_luong (
    ma_luong VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci PRIMARY KEY,
    ma_gv VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    thang INT NOT NULL,
    nam INT NOT NULL,
    so_tiet INT DEFAULT 0,
    he_so_luong DECIMAL(10,2),
    thuc_lanh DECIMAL(20,2),
    trang_thai ENUM('Chờ duyệt', 'Đã duyệt', 'Đã thanh toán') DEFAULT 'Chờ duyệt',
    ngay_lap DATE,
    ghi_chu TEXT,
    FOREIGN KEY (ma_gv) REFERENCES giaovien(ma_gv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update the users table password field
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL;

-- Update the trigger to generate username as remove_dau(ho_ten) + ma_gv + @teacher.edu.vn
DELIMITER //
CREATE TRIGGER after_giaovien_insert 
AFTER INSERT ON giaovien
FOR EACH ROW
BEGIN
    INSERT IGNORE INTO users (username, password, ma_gv, role) 
    VALUES (
        CONCAT(remove_dau(NEW.ho_ten), NEW.ma_gv, '@teacher.edu.vn'),
        '1234', -- Plain password for first login
        NEW.ma_gv,
        'teacher'
    );
END //
DELIMITER ;

-- Corrected trigger for before_lich_day_insert
DELIMITER //
CREATE TRIGGER before_lich_day_insert
BEFORE INSERT ON lich_day
FOR EACH ROW
BEGIN
    -- Set default values for new lesson records
    IF NEW.so_buoi_tuan IS NULL THEN
        SET NEW.so_buoi_tuan = 1;
    END IF;
    IF NEW.so_sinh_vien IS NULL THEN
        SET NEW.so_sinh_vien = 40;
    END IF;
    IF NEW.he_so_lop IS NULL THEN
        SET NEW.he_so_lop = 0;
    END IF;
END //
DELIMITER ;

-- Sample data
INSERT INTO khoa (ma_khoa, ten_khoa) VALUES
('K001', 'Công nghệ thông tin'),
('K002', 'Kinh tế'),
('K003', 'Ngoại ngữ'),
('K004', 'Cơ khí'),
('K005', 'Điện - Điện tử');

INSERT INTO bangcap (ma_bangcap, ten_bangcap, he_so) VALUES 
('BC001', 'Giáo sư',  2.5),
('BC002', 'Phó Giáo sư', 2.0),
('BC003', 'Tiến sĩ', 1.7),
('BC004', 'Thạc sĩ', 1.5),
('BC005', 'Cử nhân', 1.3);

INSERT INTO giaovien (ma_gv, ho_ten, gioi_tinh, ngay_sinh, dia_chi, email, luong_co_ban, so_dien_thoai, ma_khoa, ma_bangcap, ngay_vao_lam) VALUES
('GV001', 'Lê Văn A', 'Nam', '2004-12-21', 'Bắc Ninh', 'teacher1@gmail.com', 1000000, '0987654321', 'K001', 'BC001', '2025-05-25'),
('GV002', 'Nguyễn Thị B', 'Nữ', '2004-03-26', 'Bắc Ninh', 'teacher2@gmail.com', 1200000, '0123456789', 'K004', 'BC001', '2025-05-25');

INSERT INTO users (username, password, role) VALUES
('admin', '1234', 'admin'), -- password: admin
('teacher', '1234', 'teacher'), -- password: teacher
('ketoan', '1234', 'accountant'); -- password: ketoan

-- Cập nhật mật khẩu trong bảng users thành dạng plaintext
UPDATE users
SET password = '1234'
WHERE username = 'admin';

UPDATE users
SET password = 'teacher'
WHERE username = 'teacher';

UPDATE users
SET password = 'ketoan'
WHERE username = 'ketoan';