<?php
session_start();

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/functions.php";

/* ===== CHECK LOGIN & ADMIN PERMISSION ===== */
requireAdminAccess($conn);

/* ===== HELPER FUNCTION ===== */
function getSingleValue($conn, $sql)
{
    return $conn->query($sql)->fetch_row()[0] ?? 0;
}

/* ===== DASHBOARD STATS ===== */
$totalOrders = getSingleValue($conn, "
    SELECT COUNT(*) 
    FROM orders 
    WHERE status IN ('paid', 'completed')
");

$totalRevenue = getSingleValue($conn, "
    SELECT SUM(total_price) 
    FROM orders 
    WHERE status IN ('paid', 'completed')
");

$totalProducts = getSingleValue($conn, "SELECT COUNT(*) FROM products");

$totalUsers = getSingleValue($conn, "SELECT COUNT(*) FROM users");

/* ===== CURRENT PAGE ===== */
$currentPage = basename($_SERVER['PHP_SELF']);

/* ===== REVENUE 7 DAYS ===== */
$revenueByDay = [];

$sqlRevenueByDate = "
    SELECT SUM(total_price) 
    FROM orders 
    WHERE DATE(created_at) = '%s'
    AND status IN ('paid', 'completed')
";

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query = sprintf($sqlRevenueByDate, $date);
    $revenueByDay[] = getSingleValue($conn, $query);
}

/* ===== TOP PRODUCTS ===== */
$topProductsSql = "
    SELECT 
        p.id,
        p.name,
        c.name AS category_name,
        MAX(CASE WHEN pi.is_main = 1 THEN pi.image_url END) AS image_url,
        COUNT(od.product_id) AS total_sold
    FROM order_items od
    JOIN orders o ON od.order_id = o.id
    JOIN products p ON od.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id
    WHERE o.status IN ('paid', 'completed')
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 4
";

$topProducts = $conn->query($topProductsSql);

/* ===== RECENT ORDERS ===== */
$recentOrdersSql = "
    SELECT 
        o.id,
        u.full_name AS customer,
        (
            SELECT p.name
            FROM order_items od
            JOIN products p ON od.product_id = p.id
            WHERE od.order_id = o.id
            LIMIT 1
        ) AS product_name,
        o.total_price,
        o.status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 4
";

$recentOrders = $conn->query($recentOrdersSql);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="asset/Dashboard.css">

</head>

<body>

    <div class="admin-wrapper">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <h2>💻 Tech Admin</h2>

            <a href="dashboard.php"
                class="<?= (strpos($currentPage, 'dashboard.php') !== false && strpos($currentPage, 'orders') === false && strpos($currentPage, 'products') === false) ? 'active' : '' ?>">
                <i class="fa fa-chart-line"></i> Trang chủ
            </a>

            <a href="orders/index.php"
                class="<?= (strpos($currentPage, 'orders') !== false) ? 'active' : '' ?>">
                <i class="fa fa-receipt"></i> Quản lý đơn hàng
            </a>

            <a href="products/product.php"
                class="<?= (strpos($currentPage, 'products') !== false) ? 'active' : '' ?>">
                <i class="fa-solid fa-list"></i> Quản lý sản phẩm
            </a>
            <a href="<?= BASE_URL ?>/admin/categories/brand_category.php"
                class="<?= (strpos($currentPath, '/admin/categories') !== false) ? 'active' : '' ?>">
                <i class="fa fa-folder"></i> Quản lý thương hiệu
            </a>

            <a href="customers/customer.php"
                class="<?= (strpos($currentPage, 'customers') !== false) ? 'active' : '' ?>">
                <i class="fa fa-users"></i> Quản lý khách hàng
            </a>

            <a href="admin_auth/logout.php" style="color:#f87171;">
                <i class="fa fa-sign-out-alt"></i> Đăng xuất
            </a>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main">

            <div class="topbar">
                <h1>Xin chào, <?= htmlspecialchars($_SESSION['admin_name']) ?></h1>

            </div>

            <div class="dashboard">

                <div class="card" onclick="window.location.href='orders/index.php'">
                    <i class="fa fa-receipt"></i>
                    <h3>Tổng đơn hàng</h3>
                    <div class="value"><?= $totalOrders ?></div>
                </div>

                <div class="card">
                    <i class="fa fa-money-bill-wave"></i>
                    <h3>Doanh thu</h3>
                    <div class="value"><?= number_format($totalRevenue, 0, ',', '.') ?>₫</div>
                </div>

                <div class="card" onclick="window.location.href='products/product.php'">
                    <i class="fa fa-box"></i>
                    <h3>Sản phẩm</h3>
                    <div class="value"><?= $totalProducts ?></div>
                </div>

                <div class="card" onclick="window.location.href='customers/customer.php'">
                    <i class="fa fa-users"></i>
                    <h3>Khách hàng</h3>
                    <div class="value"><?= $totalUsers ?></div>
                </div>

            </div>
            <div class="dashboard-extended">

                <!-- Revenue Overview -->
                <div class="card-box revenue-card">
                    <div class="card-header">
                        <div>
                            <h3>Tổng quan về doanh thu</h3>
                            <p>Doanh thu 7 ngày gần nhất</p>
                        </div>
                    </div>

                    <canvas id="revenueChart" height="100"></canvas>
                </div>


                <!-- Top Selling -->
                <div class="card-box top-card">
                    <h3>Sản phẩm bán chạy nhất</h3>
                    <p class="sub">Laptop phổ biến nhất trong tuần</p>

                    <?php while ($row = $topProducts->fetch_assoc()): ?>
                        <div class="top-item">
                            <div class="product-info">

                                <div class="product-image">
                                    <?php if (!empty($row['image_url'])): ?>
                                        <img src="../assets/images/products/?= htmlspecialchars($row['image_url']) ?>" alt="">
                                    <?php else: ?>
                                        <img src="../assets/images/products/no-image.png" alt="">
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                                    <div class="category">
                                        <?= htmlspecialchars($row['category_name'] ?? 'Chưa phân loại') ?>
                                    </div>
                                </div>

                            </div>

                            <div class="sales">
                                <?= $row['total_sold'] ?>
                                <span>Bán ra</span>
                            </div>
                        </div>

                    <?php endwhile; ?>


                </div>

            </div>


            <!-- Recent Orders -->
            <div class="card-box recent-card">

                <div class="card-header">
                    <div>
                        <h3>Đơn đặt hàng gần đây</h3>
                        <p>Danh sách khách hàng mua hàng mới nhất</p>
                    </div>

                </div>

                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>MÃ ĐƠN HÀNG</th>
                            <th>KHÁCH HÀNG</th>
                            <th>SẢN PHẨM</th>
                            <th>GIÁ</th>
                            <th>TRẠNG THÁI</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td>#ORD-<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['customer']) ?></td>
                                <td><?= htmlspecialchars($order['product_name']) ?></td>
                                <td><?= number_format($order['total_price'], 0, ',', '.') ?>₫</td>
                                <td>
                                    <span class="status-badge <?= strtolower($order['status']) ?>">
                                        <?= strtoupper($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders/detail.php?id=<?= $order['id'] ?>" class="btn-view">
                                        Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            </div>



        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const revenueData = <?= json_encode($revenueByDay) ?>;

        const ctx = document.getElementById('revenueChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Doanh thu',
                    data: revenueData,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + '₫';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>