<?php
session_start();

require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/includes/functions.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];


$vStmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
$vStmt->bind_param("i", $userId);
$vStmt->execute();
$variant = $vStmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["full_name"]);
    $phone = trim($_POST["phone"]);
    $city = trim($_POST["city"]);
    $addressdetail = trim($_POST["address_detail"]);

    if (empty($name)) {
        $error = "Vui lòng nhập họ tên.";
    } elseif (empty($phone)) {
        $error = "Vui lòng nhập số điện thoại.";
    } elseif (empty($city)) {
        $error = "Vui lòng nhập tỉnh/thành phố.";
    } elseif (empty($addressdetail)) {
        $error = "Vui lòng nhập địa chỉ chi tiết.";
    } else {

        if ($variant) {
            $stmt = $conn->prepare("
                UPDATE addresses 
                SET full_name = ?, phone = ?, city = ?, address_detail = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("ssssi", $name, $phone, $city, $addressdetail, $userId);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO addresses (user_id, full_name, phone, city, address_detail) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issss", $userId, $name, $phone, $city, $addressdetail);
        }

        if ($stmt->execute()) {
            header("Location: " . BASE_URL . "user/profile.php?success=1");
            exit;
        } else {
            $error = "Có lỗi xảy ra. Vui lòng thử lại.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/profile.css">
    <title>Cập nhật thông tin</title>
</head>

<body>
    <?php include dirname(__DIR__) . "/includes/header.php"; ?>

    <div class="main-container">
        <h2>Thông tin cá nhân</h2>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Họ tên</label>
                <input type="text" name="full_name"
                    value="<?= htmlspecialchars($variant['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="text" name="phone"
                    value="<?= htmlspecialchars($variant['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Tỉnh/Thành phố</label>
                <input type="text" name="city"
                    value="<?= htmlspecialchars($variant['city'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Địa chỉ chi tiết</label>
                <textarea name="address_detail"><?= htmlspecialchars($variant['address_detail'] ?? '') ?></textarea>
            </div>

            <?php if (isset($error)): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>

            <button type="submit" class="btn-submit">Cập nhật</button>

            <button type="button" class="back" onclick="window.location.href='<?= BASE_URL ?>/Home.php'"><span>Quay lại</span></button>
        </form>

        <?php if (isset($_GET['success'])): ?>
            <div id="toast" class="toast-success">
                Cập nhật thông tin thành công!
            </div>
        <?php endif; ?>
</body>
<Script>
    setTimeout(function() {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.remove();

        }
        if (window.location.search.includes('success')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }

    }, 3000);
</Script>

</html>