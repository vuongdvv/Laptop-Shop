<?php
session_start();

require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$userId     = $_SESSION['user_id'];
$cartItemId = (int)($_POST['cart_item_id'] ?? 0);
$quantity   = max(1, (int)($_POST['quantity'] ?? 1));

/* kiểm tra cart items của user */
$stmt = $conn->prepare("
    SELECT ci.id
    FROM cart_items ci
    INNER JOIN carts c ON ci.cart_id = c.id
    WHERE ci.id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $cartItemId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

/* cập nhật số lượng */
$stmt = $conn->prepare("
    UPDATE cart_items
    SET quantity = ?
    WHERE id = ?
");
$stmt->bind_param("ii", $quantity, $cartItemId);
$stmt->execute();


header("Location: index.php");
exit;
