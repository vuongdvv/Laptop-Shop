<?php
session_start();
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["email"]);
    $phone     = trim($_POST["phone"]);
    $password  = $_POST["password"];
    $confirm   = $_POST["confirm"];

    // 1 Kiểm tra xác nhận mật khẩu
    if ($password !== $confirm) {
        $error = "Mật khẩu xác nhận không khớp";
    }
    //  Kiểm tra full_name trùng
    else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE full_name = ?");
        $stmt->bind_param("s", $full_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Tên người dùng đã tồn tại";
        }
        //  Kiểm tra email trùng
        else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Email đã được sử dụng";
            }
            //  Kiểm tra phone trùng
            else {
                $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->bind_param("s", $phone);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = "Số điện thoại đã được sử dụng";
                }
                //  Thêm user mới
                else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);

                    $stmt = $conn->prepare(
                        "INSERT INTO users (full_name, email, password, phone, role, status)
                         VALUES (?, ?, ?, ?, 'user', 1)"
                    );
                    $stmt->bind_param("ssss", $full_name, $email, $hash, $phone);

                    if ($stmt->execute()) {
                        //  Tự động đăng nhập
                        $_SESSION["user_id"]   = $stmt->insert_id;
                        $_SESSION["user_name"] = $full_name;

                        //  Chuyển về trang chủ
                        header("Location: " . BASE_URL);
                        exit;
                    } else {
                        $error = "Có lỗi xảy ra, vui lòng thử lại";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng ký - <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/register.css">
</head>

<body>

    <div class="register-page">
        <div class="register-box">

            <h2>Đăng ký</h2>
            <p class="register-desc">Tạo tài khoản mới tại TechStore</p>

            <?php if ($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="full_name" required placeholder="Nguyễn Văn A">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="example@gmail.com">
                </div>

                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" required placeholder="0123456789">
                </div>

                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label>Xác nhận mật khẩu</label>
                    <input type="password" name="confirm" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-register">
                    Đăng ký
                </button>
            </form>

            <div class="register-footer">
                <p>Đã có tài khoản?</p>
                <a href="<?= BASE_URL ?>/auth/login.php">Đăng nhập</a>
            </div>

        </div>
    </div>

</body>

</html>