<?php
session_start();

require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// validate input
if (
    !isset($_POST['cart_item_ids']) ||
    !is_array($_POST['cart_item_ids']) ||
    count($_POST['cart_item_ids']) === 0 ||
    empty($_POST['customer_name']) ||
    empty($_POST['phone']) ||
    empty($_POST['address'])
) {
    die("Dữ liệu checkout không hợp lệ");
}

$cartItemIds     = array_map('intval', $_POST['cart_item_ids']);
$customerName    = trim($_POST['customer_name']);
$customerPhone   = trim($_POST['phone']);
$customerAddress = trim($_POST['address']);
$paymentMethod   = $_POST['payment_method'] ?? 'cod';

// lấy cart
$stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();

if (!$cart) {
    die("Không tìm thấy giỏ hàng");
}

$cartId = $cart['id'];


$conn->begin_transaction();

try {

    // lấy cart items
    $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
    $types = str_repeat('i', count($cartItemIds)) . 'i';

    $sql = "
        SELECT product_id, variant_id, quantity, price
        FROM cart_items
        WHERE id IN ($placeholders)
        AND cart_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...array_merge($cartItemIds, [$cartId]));
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($items)) {
        throw new Exception("Không có sản phẩm hợp lệ");
    }

    // kiểm tra tồn kho
    foreach ($items as $item) {
        $stmt = $conn->prepare("
        SELECT stock 
        FROM products 
        WHERE id = ?
    ");
        $stmt->bind_param("i", $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        if (!$product || $product['stock'] <= 0) {
            throw new Exception("Sản phẩm đã hết hàng");
        }

        if ($product['stock'] < $item['quantity']) {
            throw new Exception("Số lượng sản phẩm không đủ");
        }
    }


    // tính tổng tiền

    $totalPrice = 0;
    foreach ($items as $item) {
        $totalPrice += $item['price'] * $item['quantity'];
    }

    // tạo order
    $stmt = $conn->prepare("
        INSERT INTO orders
        (user_id, customer_name, customer_phone, customer_address, total_price, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->bind_param(
        "isssd",
        $userId,
        $customerName,
        $customerPhone,
        $customerAddress,
        $totalPrice
    );
    $stmt->execute();
    $orderId = $stmt->insert_id;

    // tạo payment record
    $paymentStatus = 'pending';

    $stmt = $conn->prepare("
        INSERT INTO payments
        (order_id, method, amount, status, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isds", $orderId, $paymentMethod, $totalPrice, $paymentStatus);
    $stmt->execute();

    // order items
    $stmt = $conn->prepare("
        INSERT INTO order_items
        (order_id, product_id, variant_id, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $stmt->bind_param(
            "iiiid",
            $orderId,
            $item['product_id'],
            $item['variant_id'],
            $item['quantity'],
            $item['price']
        );
        $stmt->execute();
    }

    // trừ kho (chỉ COD)

    if ($paymentMethod === 'cod') {

        foreach ($items as $item) {
            $stmt = $conn->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ? AND stock >= ?
        ");
            $stmt->bind_param(
                "iii",
                $item['quantity'],
                $item['product_id'],
                $item['quantity']
            );
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new Exception("Sản phẩm không đủ hàng khi trừ kho");
            }
        }
    }
    // xóa cart items

    $stmt = $conn->prepare("
        DELETE FROM cart_items
        WHERE id IN ($placeholders)
    ");
    $stmt->bind_param(str_repeat('i', count($cartItemIds)), ...$cartItemIds);
    $stmt->execute();

    $conn->commit();

    // redirect theo payment
    if ($paymentMethod === 'vnpay') {
        header("Location: " . BASE_URL . "/payment/create_vnpay_payment.php?order_id=" . $orderId);
    } else {
        header("Location: success.php?order_id=" . $orderId);
    }
    exit;
} catch (Exception $e) {
    $conn->rollback();

    $_SESSION['error'] = $e->getMessage();

    header("Location: " . BASE_URL . "/cart/checkout.php");
    exit;
}
