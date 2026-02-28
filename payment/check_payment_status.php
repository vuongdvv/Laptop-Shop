<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";


header('Content-Type: application/json');

$orderId = (int) ($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Order không hợp lệ'
    ]);
    exit;
}


$stmt = $conn->prepare("
    SELECT p.status, o.status AS order_status
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    WHERE p.order_id = ?
    AND p.method = 'vnpay'
    LIMIT 1
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Không tìm thấy payment'
    ]);
    exit;
}


if ($result['status'] === 'success' && $result['order_status'] === 'paid') {

    echo json_encode([
        'success' => true,
        'status'  => 'success',
        'redirect' => BASE_URL . "/cart/success.php?order_id=" . $orderId
    ]);
    exit;
}

if ($result['status'] === 'failed') {

    echo json_encode([
        'success' => true,
        'status'  => 'failed',
        'message' => 'Thanh toán thất bại'
    ]);
    exit;
}


echo json_encode([
    'success' => true,
    'status'  => 'pending'
]);
