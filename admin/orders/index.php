<?php
session_start();

require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);

/* ===== GET ORDERS ===== */
$sql = "
    SELECT 
        o.id,
        o.customer_name,
        o.customer_phone,
        o.total_price,
        o.status,
        o.created_at,
        COUNT(oi.id) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id
    ORDER BY o.id DESC
";

$orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$currentPath = dirname($_SERVER['PHP_SELF']);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/asset/OrdersIndex.css">

</head>

<body>

    <div class="admin-wrapper">

        <!-- SIDEBAR -->
        <?php include dirname(__DIR__) . "/sidebar.php"; ?>

        <div class="admin-container">

            <h2 class="page-title">📦 Quản lý đơn hàng</h2>

            <?php if (empty($orders)): ?>
                <p>Chưa có đơn hàng nào.</p>
            <?php else: ?>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>SĐT</th>
                            <th>Số Sản Phẩm</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>

                                <td><?= e($order['customer_name']) ?></td>

                                <td><?= e($order['customer_phone']) ?></td>

                                <td><?= $order['total_items'] ?></td>

                                <td><?= formatPrice($order['total_price']) ?></td>

                                <td>
                                    <span class="order-status status-<?= e($order['status']) ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>

                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>

                                <td>
                                    <a href="detail.php?id=<?= $order['id'] ?>" class="btn-view">
                                        Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

            <?php endif; ?>

        </div>

</body>

</html>