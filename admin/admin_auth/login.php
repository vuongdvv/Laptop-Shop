<?php
session_start();

require_once dirname(__DIR__) . "/../config/config.php";
require_once dirname(__DIR__) . "/../config/database.php";

$error = "";

// Kiểm tra các thông báo lỗi từ URL
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error = "Bạn không còn là admin. Quyền của bạn đã bị thay đổi hoặc tài khoản đã bị khóa.";
    }
}

if (isset($_GET['message'])) {
    if ($_GET['message'] === 'role_changed') {
        $error = "Role của bạn đã bị thay đổi. Vui lòng đăng nhập lại với tài khoản admin.";
    }
}


if (isset($_SESSION['admin_id'])) {
    header("Location: ../dashboard.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ thông tin";
    } else {
        // Kiểm tra trong bảng admins
        $stmt = $conn->prepare("
            SELECT id, username, password, name, 'admin_table' as source
            FROM admins
            WHERE username = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        // Nếu không tìm thấy trong admins, kiểm tra bảng users với role=admin
        if (!$admin) {
            $stmt = $conn->prepare("
                SELECT id, email as username, password, full_name as name, 'users_table' as source
                FROM users
                WHERE (email = ? OR full_name = ?)
                AND role = 'admin'
                AND status = 1
                LIMIT 1
            ");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
        }

        if (!$admin || !password_verify($password, $admin['password'])) {
            $error = "Tài khoản hoặc mật khẩu không đúng";
        } else {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_source'] = $admin['source']; // Đánh dấu nguồn để dùng khi đăng xuất
            header("Location: ../dashboard.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background: #f1f5f9;
            font-family: Arial, sans-serif;
        }

        .login-box {
            width: 360px;
            margin: 120px auto;
            background: #fff;
            padding: 28px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 340px;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .btn-login {
            width: 360px;
            padding: 10px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #1e40af;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 8px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .login-footer {
            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            text-decoration: none;
        }

        .login-footer a {
            color: #2563eb;
            text-decoration: none;
            margin: 0 auto;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-box">
        <h2><i class="fa fa-lock"></i> Admin Login</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Tên đăng nhập / Email</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required>
            </div>

            <button class="btn-login" type="submit">
                Đăng nhập
            </button>
        </form>
        <div class="login-footer">
            <a href="<?= BASE_URL ?>admin/register.php" class="register-link">Đăng ký tài khoản Admin</a>

        </div>
    </div>

</body>

</html>