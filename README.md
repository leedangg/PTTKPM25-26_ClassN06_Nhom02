# 🎓 Hệ thống Quản lý Giảng viên

Hệ thống quản lý thông tin giảng viên, lịch giảng dạy và tính toán lương cho các trường đại học/cao đẳng.

## 📋 Mục lục
- [Giới thiệu](#giới-thiệu)
- [Tính năng](#tính-năng)
- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Cài đặt](#cài-đặt)
- [Cấu trúc Database](#cấu-trúc-database)
- [Tài khoản mặc định](#tài-khoản-mặc-định)
- [Screenshots](#screenshots)
- [Đóng góp](#đóng-góp)
- [License](#license)

## 📖 Giới thiệu

Hệ thống Quản lý Giảng viên là một ứng dụng web được phát triển nhằm số hóa quy trình quản lý giảng viên tại các trường đại học. Hệ thống giúp:
- Quản lý thông tin giảng viên, khoa, môn học
- Xếp lịch giảng dạy và theo dõi buổi dạy
- Tính toán lương tự động dựa trên số tiết giảng và hệ số
- Phân quyền rõ ràng cho admin, giảng viên và kế toán

## ✨ Tính năng

### 👨‍💼 Admin
- ✅ Quản lý thông tin giảng viên (thêm, sửa, xóa)
- ✅ Quản lý khoa, môn học, bằng cấp
- ✅ Quản lý học kỳ và lịch giảng dạy
- ✅ Phân quyền người dùng
- ✅ Xem báo cáo tổng hợp

### 👨‍🏫 Giảng viên
- ✅ Xem lịch giảng dạy của mình
- ✅ Cập nhật thông tin cá nhân
- ✅ Xem bảng lương theo tháng
- ✅ Điểm danh buổi dạy

### 💰 Kế toán
- ✅ Xem và duyệt bảng lương
- ✅ Tính toán lương tự động
- ✅ Xuất báo cáo lương
- ✅ Theo dõi thanh toán

## 🛠️ Công nghệ sử dụng

- **Backend:** PHP 7.4+
- **Database:** MySQL 8.0
- **Server:** XAMPP (Apache + MySQL)
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework CSS:** Bootstrap 5
- **Icons:** Font Awesome

## 💻 Yêu cầu hệ thống

- XAMPP 8.0 trở lên (hoặc LAMP/WAMP)
- PHP 7.4 trở lên
- MySQL 8.0 trở lên
- Web browser hiện đại (Chrome, Firefox, Edge)
- Dung lượng ổ cứng: tối thiểu 500MB

## 🚀 Cài đặt

### Bước 1: Clone repository

```bash
git clone https://github.com/yourusername/quanly-giangvien.git
cd quanly-giangvien
```

### Bước 2: Cài đặt XAMPP

1. Tải và cài đặt XAMPP từ [https://www.apachefriends.org](https://www.apachefriends.org)
2. Khởi động XAMPP Control Panel
3. Start Apache và MySQL

### Bước 3: Copy project vào thư mục htdocs

```bash
# Windows
copy project vào C:\xampp\htdocs\quanly-giangvien

# Linux/Mac
cp -r project /opt/lampp/htdocs/quanly-giangvien
```

### Bước 4: Tạo Database

1. Truy cập phpMyAdmin: `http://localhost/phpmyadmin`
2. Tạo database mới tên `quanly_giangvien`
3. Import file SQL:
   - Click vào database `quanly_giangvien`
   - Chọn tab "Import"
   - Chọn file `database/quanly_giangvien.sql`
   - Click "Go"

### Bước 5: Cấu hình kết nối Database

Mở file `config/database.php` và cấu hình:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quanly_giangvien');
?>
```

### Bước 6: Chạy ứng dụng

Truy cập: `http://localhost/quanly-giangvien`

## 🗄️ Cấu trúc Database

Hệ thống sử dụng 10 bảng chính:

```
├── khoa              # Quản lý khoa
├── bangcap           # Quản lý bằng cấp và hệ số lương
├── hoc_ky            # Quản lý học kỳ
├── mon_hoc           # Quản lý môn học
├── giaovien          # Quản lý giảng viên
├── users             # Quản lý tài khoản đăng nhập
├── lop_hoc           # Quản lý lớp học
├── lich_day          # Lịch giảng dạy
├── buoi_day          # Buổi dạy thực tế
└── bang_luong        # Bảng lương giảng viên
```

**Xem chi tiết:** [Database Schema](docs/database-schema.md)

## 🔑 Tài khoản mặc định

| Vai trò | Username | Password |
|---------|----------|----------|
| Admin | admin | 1234 |
| Giảng viên | teacher | teacher |
| Kế toán | ketoan | ketoan |

⚠️ **Lưu ý:** Vui lòng đổi mật khẩu sau lần đăng nhập đầu tiên!

## 🤝 Đóng góp

Mọi đóng góp đều được chào đón! Để đóng góp:

1. Fork repository
2. Tạo branch mới (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 📝 Cấu trúc thư mục

```
quanly-giangvien/
│
├── assets/              # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
│
├── database.php 
│   
│
│
├── includes/            # File PHP dùng chung
│   ├── header.php
│   ├── footer.php
│   └── functions.php
│
├── modules/             # Các module chức năng
│   ├──  khoa              # Quản lý khoa
│   ├── bangcap           # Quản lý bằng cấp và hệ số lương
│   ├──  hoc_ky            # Quản lý học kỳ
│   ├──  mon_hoc           # Quản lý môn học
│   ├──  giaovien          # Quản lý giảng viên
│   ├──  lop_hoc           # Quản lý lớp học
│   ├──  lich_day          # Lịch giảng dạy
│   ├── buoi_day          # Buổi dạy thực tế
│   └──  bang_luong        # Bảng lương giảng viên
│
├── docs/                # Tài liệu
│
├── index.php            # Trang chủ
├── login.php            # Trang đăng nhập
└── README.md
```



---

⭐ Nếu thấy project hữu ích, hãy cho mình một star nhé! ⭐
