<?php
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . '/../includes/functions.php';

$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();


    if ($result->num_rows == 0) {
        session_destroy();
        header("Location:  Home.php?error=Tài khoản đã bị xóa");
        exit;
    }
}

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT SUM(ci.quantity) AS total_qty
        FROM carts c
        JOIN cart_items ci ON c.id = ci.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $cartCount = (int)($row['total_qty'] ?? 0);
}


$current_page = basename($_SERVER['PHP_SELF']);
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="<?= BASE_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/navbar.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php if (!empty($_SESSION['toast_success'])): ?>
        <div id="toast-success">
            <?= htmlspecialchars($_SESSION['toast_success']) ?>
        </div>
    <?php unset($_SESSION['toast_success']);
    endif; ?>
    <!-- HEADER -->
    <header class="site-header">
        <div class="container header-inner d-flex align-items-center justify-content-between">

            <!-- LOGO -->
            <a href="" class="logo d-flex align-items-center">
                <span class="logo-icon me-1">💻</span>
                <strong>TechStore</strong>
            </a>

            <!-- MENU -->
            <nav class="menu d-none d-md-flex align-items-center">
                <a href="<?= BASE_URL ?>/home.php"
                    class="<?= ($current_page == 'home.php') ? 'active' : '' ?>">
                    Trang chủ
                </a>

                <a href="<?= BASE_URL ?>/product/list.php"
                    class="<?= ($current_page == 'list.php') ? 'active' : '' ?>">
                    Sản phẩm
                </a>

                <a href="#"
                    class="<?= ($current_page == 'support.php') ? 'active' : '' ?>">
                    Hỗ trợ
                </a>

                <a href="<?= BASE_URL ?>/orders/index.php"
                    class="<?= ($current_page == 'index.php' && strpos($_SERVER['PHP_SELF'], 'orders') !== false) ? 'active' : '' ?>">
                    Đơn hàng
                </a>
            </nav>

            <!-- RIGHT -->
            <div class="header-right d-flex align-items-center gap-3">

                <!-- SEARCH -->
                <form action="<?= BASE_URL ?>/product/list.php" method="get" class="search-form">
                    <div class="search-box d-flex align-items-center">
                        <i class="fa fa-search"></i>
                        <input type="text"
                            name="keyword"
                            placeholder="Tìm kiếm..."
                            value="<?= isset($_GET['keyword']) ? e($_GET['keyword']) : '' ?>">
                    </div>
                </form>

                <!-- CART -->
                <a href="<?= BASE_URL ?>/cart/index.php" class="icon-btn position-relative">
                    <i class="fa fa-shopping-cart"></i>

                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>

                <!-- USER -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info d-flex align-items-center gap-2">
                        <a href="<?= BASE_URL ?>/user/profile.php">
                            <span class="hello-text">
                                Xin chào,
                                <?= htmlspecialchars($_SESSION['user_name']) ?>
                            </span>
                        </a>
                        <a href="<?= BASE_URL ?>/auth/logout.php" class="icon-btn">
                            <i class="fa fa-sign-out-alt"></i>
                        </a>

                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="icon-btn">
                        <i class="fa fa-user"></i>
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </header>
    <?php if (isset($_GET['success'])): ?> <div id="toast" class="toast-success">
            ✔ Lưu sản phẩm thành công </div>
    <?php endif; ?>


    <script>
        setTimeout(() => {
            const toast = document.getElementById('toast-success');
            if (toast) toast.remove();
        }, 3000);

        const searchInput = document.querySelector('input[name="keyword"]');

        searchInput.addEventListener('input', function() {
            if (this.value.trim() === '') {
                window.location.href = "<?= BASE_URL ?>/product/list.php";
            }
        });
    </script>