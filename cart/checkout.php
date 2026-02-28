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
$mode   = $_GET['mode'] ?? null;

$userAddress = null;

if ($userId) {
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userAddress = $stmt->get_result()->fetch_assoc();
}

$stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();

if (!$cart) {
    header("Location: index.php");
    exit;
}


if ($mode === 'buy_now' && isset($_SESSION['buy_now_item'])) {

    $cartItemId = (int)$_SESSION['buy_now_item'];

    $stmt = $conn->prepare("
        SELECT 
            ci.id AS cart_item_id,
            ci.quantity,
            ci.price,
            p.name,
            p.slug,
            pi.image_url
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_images pi 
            ON pi.product_id = p.id AND pi.is_main = 1
        WHERE ci.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $cartItemId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    unset($_SESSION['buy_now_item']);
} else {


    $stmt = $conn->prepare("
        SELECT 
            ci.id AS cart_item_id,
            ci.quantity,
            ci.price,
            p.name,
            p.slug,
            pi.image_url
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_images pi 
            ON pi.product_id = p.id AND pi.is_main = 1
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $cart['id']);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}



if (empty($items)) {
    header("Location: Home.php");
    exit;
}

/* =========================
   4️⃣ TÍNH TỔNG TIỀN
========================= */
$totalPrice = 0;
foreach ($items as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cart.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/checkout.css">
</head>

<body>

    <?php include dirname(__DIR__) . "/includes/header.php"; ?>


    <div class="container">
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="toast-error">
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <h2 class="page-title">💳 Thanh toán</h2>


        <form action="<?= BASE_URL ?>/cart/process_checkout.php" method="post" id="checkoutForm">

            <div class="checkout-wrapper">

                <!-- ================= LEFT: DANH SÁCH SẢN PHẨM ================= -->
                <div class="checkout-left">

                    <table class="cart-table">
                        <thead>
                            <tr>
                                <td data-label="Sản phẩm">Sản Phẩm</td>
                                <td data-label="Số lượng">Số lượng</td>
                                <td data-label="Giá">Giá</td>


                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $image = !empty($item['image_url'])
                                    ? BASE_URL . "/assets/images/products/" . $item['image_url']
                                    : BASE_URL . "/assets/images/products/no-image.png";

                                $subTotal = $item['price'] * $item['quantity'];
                                ?>
                                <tr>

                                    <td class="cart-product">
                                        <a href="<?= BASE_URL ?>/product/detail.php?slug=<?= urlencode($item['slug']) ?>"
                                            class="cart-product-link">
                                            <img src="<?= $image ?>" alt="<?= e($item['name']) ?>">
                                            <?= e($item['name']) ?>
                                        </a>
                                    </td>
                                    <td><?= $item['quantity'] ?></td>

                                    <td><?= formatPrice($subTotal) ?></td>


                                </tr>

                                <input type="hidden" name="cart_item_ids[]" value="<?= $item['cart_item_id'] ?>">
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="<?= BASE_URL ?>/cart/index.php" class="cart-continue-shopping">← Trở lại giỏ hàng</a>
                </div>

                <!-- ================= RIGHT: THÔNG TIN THANH TOÁN ================= -->
                <div class="checkout-right">

                    <h3>Thông tin người nhận</h3>

                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" name="customer_name"
                            value="<?= htmlspecialchars($userAddress['full_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone"
                            value="<?= htmlspecialchars($userAddress['phone'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Tỉnh/Thành phố</label>
                        <input type="text" name="city"
                            value="<?= htmlspecialchars($userAddress['city'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <textarea name="address" rows="3" required><?= htmlspecialchars($userAddress['address_detail'] ?? '') ?></textarea>
                    </div>

                    <h3>Phương thức thanh toán</h3>

                    <div class="payment-method">
                        <label>
                            <input type="radio" name="payment_method" value="cod" checked>
                            Thanh toán khi nhận hàng (COD)
                        </label>
                    </div>

                    <div class="payment-method">
                        <label>
                            <input type="radio" name="payment_method" value="vnpay">
                            Thanh toán bằng VNPAY
                        </label>
                    </div>

                    <div class="cart-summary">
                        <h3>
                            Tổng thanh toán:
                            <span id="totalPrice">0₫</span>
                        </h3>

                        <button type="submit" class="btn-checkout" id="checkoutBtn">
                            Xác nhận thanh toán
                        </button>
                    </div>

                </div>

            </div>

        </form>

    </div>

    <?php include dirname(__DIR__) . "/includes/footer.php"; ?>

    <script>
        const totalEl = document.getElementById('totalPrice');
        totalEl.textContent = '<?= number_format($totalPrice, 0, ',', '.') ?>₫';
    </script>

</body>


</html>