<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";

session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];


if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL);
    exit;
}

$productId = (int)$_GET['id'];
$variantId = isset($_GET['variant']) ? (int)$_GET['variant'] : null;
$buyNow    = isset($_GET['buy_now']);
$redirect  = $_GET['redirect'] ?? null;

// lấy sản phẩm
$stmt = $conn->prepare("
    SELECT id, price, sale_price
    FROM products
    WHERE id = ? AND status = 1
    LIMIT 1
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: " . BASE_URL);
    exit;
}

$price = !empty($product['sale_price'])
    ? $product['sale_price']
    : $product['price'];

/* =========================
TÌM HOẶC TẠO CART
========================= */
$stmt = $conn->prepare("
    SELECT id
    FROM carts
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();

if (!$cart) {
    $stmt = $conn->prepare("
        INSERT INTO carts (user_id, created_at)
        VALUES (?, NOW())
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $cartId = $stmt->insert_id;
} else {
    $cartId = $cart['id'];
}


$stmt = $conn->prepare("
    SELECT id, quantity
    FROM cart_items
    WHERE cart_id = ?
      AND product_id = ?
      AND (variant_id <=> ?)
    LIMIT 1
");
$stmt->bind_param("iii", $cartId, $productId, $variantId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if ($item) {

    $stmt = $conn->prepare("
        UPDATE cart_items
        SET quantity = quantity + 1
        WHERE id = ?
    ");
    $stmt->bind_param("i", $item['id']);
    $stmt->execute();

    $cartItemId = $item['id'];
} else {

    $stmt = $conn->prepare("
        INSERT INTO cart_items
            (cart_id, product_id, variant_id, quantity, price)
        VALUES (?, ?, ?, 1, ?)
    ");
    $stmt->bind_param("iiid", $cartId, $productId, $variantId, $price);
    $stmt->execute();

    $cartItemId = $stmt->insert_id;
}


if ($buyNow) {
    $_SESSION['buy_now_item'] = $cartItemId;
    header("Location: " . BASE_URL . "/cart/checkout.php?mode=buy_now");
    exit;
}


$_SESSION['toast_success'] = '✔ Đã thêm sản phẩm vào giỏ hàng';

if ($redirect === 'detail' && !empty($_GET['slug'])) {
    header("Location: " . BASE_URL . "/product/detail.php?slug=" . urlencode($_GET['slug']));
} else {
    header("Location: " . BASE_URL . "/product/list.php");
}
exit;
