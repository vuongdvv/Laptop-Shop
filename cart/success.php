<?php
require_once dirname(__DIR__) . "/config/config.php";
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đặt hàng thành công</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cart.css">
</head>
<style>
    .container {
        text-align: center;
        padding: 50px;
        gap: 20px;
    }
</style>

<body>

    <?php include dirname(__DIR__) . "/includes/header.php"; ?>

    <div class="container">
        <h2>🎉 Đặt hàng thành công!</h2>
        <p>Cảm ơn bạn đã mua sắm tại <strong><?= SITE_NAME ?></strong></p>
        <a href="<?= BASE_URL ?>/product/list.php" class="btn-continue-shopping">
            Tiếp tục mua sắm
        </a>
    </div>

</body>

</html>