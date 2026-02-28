<?php
session_start();

require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboard.php");
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$newStatus  = trim($_POST['status'] ?? '');

if ($orderId <= 0 || $newStatus === '') {
    die("Dữ liệu không hợp lệ");
}

/* ==============================
   LẤY TRẠNG THÁI HIỆN TẠI + PAYMENT
============================== */
$stmt = $conn->prepare("
    SELECT o.status, p.method AS payment_method
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Đơn hàng không tồn tại");
}

$currentStatus = $order['status'];
$paymentMethod = $order['payment_method'] ?? 'cod';


if ($paymentMethod === 'cod') {

    $allowedNextStatus = [
        'pending'   => ['shipped', 'cancel'],
        'shipped'   => ['completed'],
        'completed' => [],
        'failed'    => [],
        'cancel'    => []
    ];
} else {

    $allowedNextStatus = [
        'pending' => ['paid', 'cancel'],
        'paid'    => ['shipped'],
        'shipped' => ['completed'],
        'completed' => [],
        'failed'    => [],
        'cancel'    => []
    ];
}


if (!isset($allowedNextStatus[$currentStatus])) {
    die("Trạng thái hiện tại không hợp lệ");
}

if ($newStatus !== $currentStatus && !in_array($newStatus, $allowedNextStatus[$currentStatus])) {
    die("Không được phép chuyển trạng thái này");
}


$stmt = $conn->prepare("
    UPDATE orders
    SET status = ?
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("si", $newStatus, $orderId);
$stmt->execute();


header("Location: detail.php?id=" . $orderId);
exit;
