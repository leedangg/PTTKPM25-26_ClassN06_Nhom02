<?php
require_once '../../config/database.php';
require_once '../header.php';

$database = new Database();
$conn = $database->getConnection();

// Biến $root_path sẽ không cần thiết cho ảnh nữa, nhưng giữ lại phòng trường hợp
// các tài nguyên khác (CSS, JS) hoặc hàm getHeader/getFooter cần đến nó.
// Nếu các file được require ở trên đã định nghĩa $root_path, bạn có thể bỏ qua phần này.
// Để tối giản, tôi sẽ giả định nó đã được định nghĩa ở đâu đó hoặc không cần thiết.

function taoMaBangCap($conn)
{
    $sql = "SELECT ma_bangcap FROM bangcap ORDER BY ma_bangcap DESC LIMIT 1";
    $stmt = $conn->query($sql);
    if ($stmt->rowCount() > 0) {
        $last = $stmt->fetch(PDO::FETCH_ASSOC)['ma_bangcap'];
        $num = intval(substr($last, 2)) + 1;
        return 'BC' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
    return 'BC001';
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $ten = trim($_POST['ten_bangcap'] ?? '');
        $he_so = floatval($_POST['he_so'] ?? 0);
        $he_so_luong = isset($_POST['he_so_luong']) && $_POST['he_so_luong'] !== ''
            ? floatval($_POST['he_so_luong'])
            : null;

        if (empty($ten) || $he_so <= 0) {
            $error = "Vui lòng nhập đầy đủ Tên và Hệ số Giảng dạy hợp lệ.";
        } else {
            // Kiểm tra trùng tên bằng cấp
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM bangcap WHERE LOWER(TRIM(ten_bangcap)) = LOWER(TRIM(:ten))");
            $stmtCheck->execute([':ten' => $ten]);
            if ($stmtCheck->fetchColumn() > 0) {
                $error = "Bằng cấp đã tồn tại!";
            } else {
                $ma = taoMaBangCap($conn);
                $stmt = $conn->prepare("INSERT INTO bangcap (ma_bangcap, ten_bangcap, he_so_luong, he_so) 
                                        VALUES (:ma, :ten, :luong, :heso)");
                $stmt->execute([
                    ':ma' => $ma,
                    ':ten' => $ten,
                    ':luong' => $he_so_luong,
                    ':heso' => $he_so
                ]);
                header("Location: index.php");
                exit();
            }
        }
    } catch (Exception $e) {
        $error = "Lỗi hệ thống: " . $e->getMessage();
    } catch (PDOException $e) {
        $error = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
    }
}

echo getHeader("Thêm Bằng cấp");
// Giả định $root_path đã được định nghĩa trong header.php hoặc config, nếu không,
// bạn cần tự định nghĩa nó nếu cần cho các tài nguyên khác (CSS, JS)
// Ví dụ: $root_path = '/';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $root_path ?? '' ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* ... CSS của bạn ... */
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(145deg, #ffffff, #f9fafb);
            overflow: hidden;
        }

        .card-header {
            font-size: 1.25rem;
            font-weight: 600;
            background: linear-gradient(to right, #007bff, #0056b3);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 32px;
            padding: 2rem 1rem;
        }

        .img-container {
            flex: 0 0 auto;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .img-container img {
            width: 180px;
            height: 180px;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            padding: 10px;
            background: #fff;
            margin-bottom: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .img-container img:hover {
            transform: translateY(-5px) scale(1.04);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .form-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
            align-items: center;
        }

        .form-list li {
            width: 100%;
            max-width: 400px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.5rem;
            font-size: 0.95rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.18);
        }

        .form-label {
            font-weight: 500;
            color: #343a40;
            font-size: 0.95rem;
            margin-bottom: 0.3rem;
        }

        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-size: 0.95rem;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-size: 0.95rem;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
            justify-content: center;
        }

        .text-center {
            margin-top: 1.5rem;
        }

        @media (max-width: 900px) {
            .form-container {
                flex-direction: column;
                align-items: center;
                gap: 18px;
                padding: 1rem;
            }

            .img-container img {
                width: 120px;
                height: 120px;
            }

            .form-list li {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="card mb-4 mx-auto" style="max-width: 700px;">
            <div class="card-header">
                <i class="fas fa-plus-circle mr-2"></i> Thêm bằng cấp mới
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <div class="form-container">
                    <div class="img-container">
                        <img src="<?= $root_path ?? '../../' ?>assets/img/bangcap2.jpg" alt="Graduate certificate"
                            onerror="this.onerror=null;this.src='<?= $root_path ?? '../../' ?>assets/img/graduate-certificate.png';">
                        <img src="<?= $root_path ?? '../../' ?>assets/img/bangcap1.webp" alt="Degree hat"
                            onerror="this.onerror=null;this.src='<?= $root_path ?? '../../' ?>assets/img/degree-hat.png';">
                    </div>
                    <div class="form-content">
                        <form method="POST">
                            <ul class="form-list">
                                <li>
                                    <label for="ten_bangcap" class="form-label"><i
                                            class="fas fa-graduation-cap mr-1"></i> Tên bằng cấp</label>
                                    <input type="text" name="ten_bangcap" id="ten_bangcap" class="form-control"
                                        placeholder="VD: Cử nhân CNTT" required
                                        value="<?= htmlspecialchars($_POST['ten_bangcap'] ?? '') ?>">
                                </li>
                                <li>
                                    <label for="he_so" class="form-label"><i class="fas fa-calculator mr-1"></i> Hệ số
                                        giảng dạy</label>
                                    <input type="number" name="he_so" id="he_so" class="form-control" min="1.0"
                                        max="3.0" step="0.1" required
                                        value="<?= htmlspecialchars($_POST['he_so'] ?? '') ?>">
                                </li>
                                <li>
                                    <label for="he_so_luong" class="form-label"><i
                                            class="fas fa-money-bill-wave mr-1"></i> Hệ số lương</label>
                                    <input type="number" name="he_so_luong" id="he_so_luong" class="form-control"
                                        min="1.0" max="5.0" step="0.01" placeholder="Không bắt buộc"
                                        value="<?= htmlspecialchars($_POST['he_so_luong'] ?? '') ?>">
                                </li>
                            </ul>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Lưu
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo getFooter(); ?>
</body>

</html>