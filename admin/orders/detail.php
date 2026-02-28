<?php
session_start();

require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header("Location: index.php");
    exit;
}


/* ==============================
   LẤY ORDER + PAYMENT
============================== */
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

if (!$order) {
    header("Location: index.php");
    exit;
}


/* ==============================
   LABEL ORDER STATUS
============================== */
$orderLabels = [
    'pending'   => 'Chờ xử lý',
    'shipped'   => 'Đang giao hàng',
    'completed' => 'Hoàn thành',
    'cancel'    => 'Đã huỷ đơn',
    'failed'    => 'Thanh toán thất bại'
];

$orderStatusLabel = $orderLabels[$order['status']] ?? 'Không xác định';


/* ==============================
   LABEL PAYMENT STATUS
============================== */
$paymentLabels = [
    'pending' => 'Chờ thanh toán',
    'success' => 'Thanh toán thành công',
    'failed'  => 'Thanh toán thất bại'
];

$paymentStatusLabel = $paymentLabels[$order['payment_status']] ?? 'Không xác định';


/* ==============================
   LẤY SẢN PHẨM TRONG ĐƠN
============================== */
$stmt = $conn->prepare("
    SELECT
        oi.quantity,
        oi.price,
        p.name,
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


/* ==============================
   ALLOWED NEXT ORDER STATUS
   (ADMIN CHỈ ĐỔI ORDER STATUS)
============================== */

$allowedNextStatus = [
    'pending'   => ['shipped', 'cancel'],
    'paid'      => ['shipped'],
    'shipped'   => ['completed'],
    'completed' => [],
    'cancel'    => [],
    'failed'    => []
];

$statusClass = [
    'pending' => 'gray',
    'paid' => 'green',
    'shipped' => 'orange',
    'completed' => 'blue',
    'cancel' => 'red',
    'failed' => 'red'
];
/* ==============================
   AUTO SYNC: Nếu payment failed
   thì order phải cancel
============================== */
if ($order['payment_status'] === 'failed' && $order['status'] !== 'failed') {
    $update = $conn->prepare("UPDATE orders SET status = 'failed' WHERE id = ?");
    $update->bind_param("i", $orderId);
    $update->execute();
    $order['status'] = 'failed';
}


$currentPath = dirname($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="vi">


<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $order['id'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/asset/OrdersDetail.css">
</head>

<body>

    <div class="admin-wrapper">

        <!-- SIDEBAR -->
        <?php include dirname(__DIR__) . "/sidebar.php"; ?>

        <!-- CONTENT -->
        <div class="admin-container">

            <div class="breadcrumbs">
                <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> >
                <a href="<?= BASE_URL ?>/admin/orders/index.php">Đơn hàng</a> >
                <span>Chi tiết đơn hàng </span>



                <h2 class="page-title">Chi tiết đơn hàng #<?= $order['id'] ?></h2>

                <!-- THÔNG TIN KHÁCH -->
                <div class="box">
                    <div class="info-row"><strong>Khách hàng:</strong> <?= e($order['customer_name']) ?></div>
                    <div class="info-row"><strong>SĐT:</strong> <?= e($order['customer_phone']) ?></div>
                    <div class="info-row"><strong>Địa chỉ:</strong> <?= e($order['customer_address'] . ', ' . $order['city']) ?></div>
                    <div class="info-row"><strong>Ngày tạo:</strong> <?= $order['created_at'] ?></div>
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

                <!-- SẢN PHẨM -->
                <div class="box">
                    <table>
                        <thead>
                            <tr>

                                <th class="product">Sản phẩm</th>
                                <th class="price">Giá</th>
                                <th class="quantity">Số lượng</th>
                                <th class="subtotal">Tạm tính</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>

                                <tr>
                                    <td class="product">
                                        <img src="<?= e($item['image_url'])
                                                        ? BASE_URL . '/assets/images/products/' . $item['image_url']
                                                        : BASE_URL . '/assets/images/products/no-image.png'; ?>">
                                        <?= e($item['name']) ?>
                                    </td>
                                    <td><?= formatPrice($item['price']) ?></td>
                                    <td class="quantity"><?= $item['quantity'] ?></td>
                                    <td><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="total">
                        Tổng tiền: <?= formatPrice($order['total_price']) ?>
                    </div>
                </div>

                <!-- CẬP NHẬT TRẠNG THÁI -->
                <div class="box">
                    <form action="update_status.php" method="post">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                        <select name="status">
                            <?php
                            $current = $order['status'];
                            $nextStatuses = $allowedNextStatus[$current] ?? [];

                            $labels = [
                                'pending'   => 'Đơn mới tạo',
                                'paid'      => 'Đã thanh toán',
                                'shipped'   => 'Đang giao',
                                'completed' => 'Hoàn thành',
                                'failed'    => 'Thanh toán thất bại',
                                'cancel'  => 'Đã huỷ đơn hàng'
                            ];

                            foreach ($labels as $value => $label):
                                $disabled = '';

                                if ($value !== $current && !in_array($value, $allowedNextStatus[$current])) {
                                    $disabled = 'disabled';
                                }
                            ?>
                                <option value="<?= $value ?>"
                                    <?= $value == $current ? 'selected' : '' ?>
                                    <?= $disabled ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-save">Cập nhật</button>
                    </form>


                </div>

            </div>

        </div>

</body>

</html>