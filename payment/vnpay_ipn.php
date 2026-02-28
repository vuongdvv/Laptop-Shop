<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . "/../config/database.php";
$vnpay = require __DIR__ . "/../config/vnpay.php";



$returnData = [
    "RspCode" => "99",
    "Message" => "Unknown error"
];

/* =========================
   1. KIỂM TRA HASH
========================= */
if (!isset($_GET['vnp_SecureHash'])) {
    $returnData["Message"] = "Missing SecureHash";
    echo json_encode($returnData);
    exit;
}

$vnpSecureHash = $_GET['vnp_SecureHash'];

$inputData = [];

foreach ($_GET as $key => $value) {
    if (
        substr($key, 0, 4) === "vnp_" &&
        $key !== "vnp_SecureHash" &&
        $key !== "vnp_SecureHashType"
    ) {
        $inputData[$key] = $value;
    }
}

ksort($inputData);

$hashData = '';
$i = 0;

foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $hashData .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$calculatedHash = hash_hmac('sha512', $hashData, $vnpay['vnp_HashSecret']);

if ($calculatedHash !== $vnpSecureHash) {
    echo json_encode([
        "RspCode" => "97",
        "Message" => "Invalid signature"
    ]);
    exit;
}

/* =========================
   2. LẤY DỮ LIỆU
========================= */
$txnRef = $_GET['vnp_TxnRef'];
$orderIdParts = explode('_', $txnRef);
$orderId = isset($orderIdParts[0]) ? (int)$orderIdParts[0] : 0;

if (!$orderId) {
    $returnData["RspCode"] = "01";
    $returnData["Message"] = "Invalid TxnRef";
    echo json_encode($returnData);
    exit;
}

$vnpAmount     = $_GET['vnp_Amount'] / 100;
$responseCode  = $_GET['vnp_ResponseCode'];
$txnStatus     = $_GET['vnp_TransactionStatus'];
$transactionNo = $_GET['vnp_TransactionNo'];



/* =========================
   3. CẬP NHẬT ĐƠN HÀNG + PAYMENT + TRỪ KHO
========================= */

mysqli_begin_transaction($conn);

try {

    $stmt = $conn->prepare("
        SELECT id, total_price, status 
        FROM orders 
        WHERE id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new Exception("Order not found");
    }

    if ((int)$order['total_price'] !== (int)$vnpAmount) {
        throw new Exception("Invalid amount");
    }

    if ($order['status'] !== 'pending') {
        throw new Exception("Order already confirmed");
    }

    if ($responseCode === '00' && $txnStatus === '00') {
        $newStatus = 'paid';
        $paymentStatus = 'success';

        // TRỪ KHO
        $itemStmt = $conn->prepare("
            SELECT product_id, quantity 
            FROM order_items 
            WHERE order_id = ?
        ");
        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();
        $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($items as $item) {
            $stockStmt = $conn->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ? AND stock >= ?
            ");
            $stockStmt->bind_param(
                "iii",
                $item['quantity'],
                $item['product_id'],
                $item['quantity']
            );
            $stockStmt->execute();

            if ($stockStmt->affected_rows == 0) {
                throw new Exception("Product out of stock");
            }
        }
    } elseif ($responseCode === '24') {
        $newStatus = 'cancel';
        $paymentStatus = 'cancel';
    } else {
        $newStatus = 'failed';
        $paymentStatus = 'failed';
    }

    // UPDATE ORDERS
    $updateOrder = $conn->prepare("
        UPDATE orders 
        SET status = ?, 
            vnp_transaction_no = ?
        WHERE id = ?
    ");
    $updateOrder->bind_param("ssi", $newStatus, $transactionNo, $orderId);
    $updateOrder->execute();

    // UPDATE PAYMENTS
    $payload = json_encode($_GET);

    $updatePayment = $conn->prepare("
        UPDATE payments
        SET status = ?,
            transaction_code = ?,
            response_payload = ?,
            updated_at = NOW()
        WHERE order_id = ?
    ");
    $updatePayment->bind_param(
        "sssi",
        $paymentStatus,
        $transactionNo,
        $payload,
        $orderId
    );
    $updatePayment->execute();

    mysqli_commit($conn);
} catch (Exception $e) {



    mysqli_rollback($conn);

    $returnData["RspCode"] = "99";
    $returnData["Message"] = "Stock update failed";
    echo json_encode($returnData);
    exit;
}


$returnData["RspCode"] = "00";
$returnData["Message"] = "Confirm Success";

echo json_encode($returnData);
