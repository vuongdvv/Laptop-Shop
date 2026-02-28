<?php
session_start();
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = trim($_POST["email"]);
    $password = $_POST["password"];

    $user = null;

    // Kiểm tra input có chứa '@' hay không
    if (strpos($input, '@') !== false) {
        // Input là email → kiểm tra bảng users
        $stmt = $conn->prepare(
            "SELECT id, full_name, password, status 
             FROM users 
             WHERE email = ?"
        );
        $stmt->bind_param("s", $input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Kiểm tra user bị khóa
        if ($user && $user["status"] == 0) {
            $error = "Tài khoản của bạn đã bị khóa";
            $user = null;
        }
    } else {
        // Input là username → kiểm tra bảng admins
        $stmt = $conn->prepare(
            "SELECT id, name as full_name, password 
             FROM admins 
             WHERE username = ?"
        );
        $stmt->bind_param("s", $input);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    }

    // Không tồn tại 
    if (!$user) {
        if (empty($error)) {
            $error = "Tài khoản của bạn không tồn tại. Vui lòng đăng ký để tiếp tục";
        }
    }
    // Sai mật khẩu
    elseif (!password_verify($password, $user["password"])) {
        $error = "Mật khẩu không đúng";
    }
    // Thành công
    else {
        $_SESSION["user_id"]   = $user["id"];
        $_SESSION["user_name"] = $user["full_name"];

        header("Location: " . BASE_URL);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
</head>

<body>

    <div class="login-page">
        <div class="login-box">

            <h2>Đăng nhập</h2>
            <p class="login-desc">Chào mừng bạn quay lại TechStore</p>

            <?php if ($error): ?>
                <div class="alert"><?= $error ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Email hoặc Username </label>
                    <input type="text" name="email" required placeholder="user@email.com">
                </div>

                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-login">
                    Đăng nhập
                </button>
            </form>

            <div class="login-footer">
                <a href="<?= BASE_URL ?>">← Về trang chủ</a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="register-link">Đăng ký</a>
            </div>

        </div>
    </div>

</body>

</html>