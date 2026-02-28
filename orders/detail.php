<?php
session_start();

require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/includes/functions.php";

/* KIỂM TRA ĐĂNG NHẬP */
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$userId  = $_SESSION['user_id'];
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    header("Location: index.php");
    exit;
}

/* LẤY ĐƠN HÀNG (CHỈ CỦA USER) */
$stmt = $conn->prepare("
    SELECT 
        o.*,
        p.method AS payment_method,
        p.status AS payment_status,
        a.city AS city
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.id
    LEFT JOIN addresses a ON a.user_id = o.user_id
    WHERE o.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

$orderLabels = [
    'pending'   => 'Chờ xử lý',
    'shipped'   => 'Đang giao hàng',
    'completed' => 'Hoàn thành',
    'cancel'    => 'Đã huỷ đơn'
];

$orderStatusLabel = $orderLabels[$order['status']] ?? 'Không xác định';


/* ==============================
   LABEL PAYMENT STATUS
============================== */
$paymentLabels = [
    'pending' => 'Chờ thanh toán',
    'success' => 'Thanh toán thành công',
    'cancel'  => 'Thanh toán thất bại'
];

$paymentStatusLabel = $paymentLabels[$order['payment_status']] ?? 'Thanh toán thất bại';

if (!$order) {
    header("Location: index.php");
    exit;
}

/* LẤY SẢN PHẨM TRONG ĐƠN */
$stmt = $conn->prepare("
    SELECT 
        oi.quantity,
        oi.price,
        p.name,
        p.slug,
        pi.image_url
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi 
        ON pi.product_id = p.id AND pi.is_main = 1
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>



<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order['id'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/cart.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/checkout.css">


</head>

<body>

    <?php include dirname(__DIR__) . "/includes/header.php"; ?>

    <div class="container">

        <div class="breadcrumbs">
            <a href="<?= BASE_URL ?>/Home.php">Trang Chủ</a> >
            <a href="<?= BASE_URL ?>/orders/index.php">Đơn hàng của tôi</a> >
            <span>Chi tiết đơn hàng </span>
        </div>

        <h2 class="page-title">🧾 Chi tiết đơn hàng #<?= $order['id'] ?></h2>

        <!-- THÔNG TIN ĐƠN -->
        <div class="order-info-box">
            <div class="info-row"><strong>Khách hàng:</strong> <?= e($order['customer_name']) ?></div>
            <div class="info-row"><strong>SĐT:</strong> <?= e($order['customer_phone']) ?></div>
            <div class="info-row"><strong>Địa chỉ:</strong> <?= e($order['customer_address'] . ', ' . $order['city']) ?></div>
            <div class="info-row"><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>

            <div class="info-row">
                <strong>Thanh toán:</strong><?= strtoupper(e($order['payment_method'])) ?>
            </div>
            <div class="info-row">
                <strong>Trạng thái:</strong>
                <?php
                $statusMap = [
                    'pending'   => ['status-gray',  'Chờ xử lý'],
                    'paid'      => ['status-green', 'Đã thanh toán'],
                    'shipped'   => ['status-orange', 'Đang giao'],
                    'completed' => ['status-blue',  'Hoàn tất'],
                    'cancel'    => ['status-red',   'Đã huỷ đơn'],
                    'failed'    => ['status-red',   'Thanh toán thất bại'],
                ];

                [$class, $text] = $statusMap[$order['status']] ?? ['status-gray', 'Chờ xử lý'];
                ?>

                <span class="status-badge <?= $class ?>">
                    <?= $text ?>
                </span>

            </div>
        </div>

        <!-- DANH SÁCH SẢN PHẨM -->
        <table class="cart-table">
            <thead>
                <tr>

                    <td class="data-label">Sản phẩm</td>
                    <td data-label="Giá">Giá</td>
                    <td data-label="SL">SL</td>
                    <td data-label="Tổng tiền">Tổng tiền</td>
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
                            <img src="<?= $image ?>" alt="<?= e($item['name']) ?>">
                            <a href="<?= BASE_URL ?>/product/detail.php?slug=<?= e($item['slug']) ?>">
                                <?= e($item['name']) ?>
                            </a>
                        </td>

                        <td><?= formatPrice($item['price']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td><?= formatPrice($subTotal) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- TỔNG TIỀN -->
        <div class="cart-summary">
            <h3>Tổng cộng: <?= formatPrice($order['total_price']) ?></h3>
        </div>

    </div>



</body>

</html>