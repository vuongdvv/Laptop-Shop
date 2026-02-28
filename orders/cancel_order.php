<?php
session_start();
require_once dirname(__DIR__) . "/config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Bạn chưa đăng nhập"
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$orderId = (int)($_POST['order_id'] ?? 0);

if ($orderId <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Đơn hàng không hợp lệ"
    ]);
    exit;
}

/* Kiểm tra đơn hàng */
$stmt = $conn->prepare("
    SELECT status FROM orders 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Không tìm thấy đơn hàng"
    ]);
    exit;
}

/* Chỉ cho hủy khi chưa giao */
if (!in_array($result['status'], ['pending'])) {
    echo json_encode([
        "success" => false,
        "message" => "Không thể hủy đơn này"
    ]);
    exit;
}

/* Cập nhật trạng thái */
$conn->begin_transaction();

try {

    /* =========================
       LẤY SẢN PHẨM TRONG ĐƠN
    ========================= */
    $stmt = $conn->prepare("
        SELECT product_id, quantity
        FROM order_items
        WHERE order_id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    /* =========================
       HOÀN LẠI KHO
    ========================= */
    foreach ($items as $item) {
        $stmt = $conn->prepare("
            UPDATE products
            SET stock = stock + ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ii",
            $item['quantity'],
            $item['product_id']
        );
        $stmt->execute();
    }

    /* =========================
       CẬP NHẬT TRẠNG THÁI
    ========================= */
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'cancel' 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();

    $conn->commit();

    echo json_encode([
        "success" => true
    ]);
} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "Lỗi khi hủy đơn"
    ]);
}
