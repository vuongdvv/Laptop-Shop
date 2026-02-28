<?php
session_start();

require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/* ===== FILTER ===== */

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 5;
$offset = ($page - 1) * $limit;

/* ===== STATS ===== */
$totalProduct = $conn->query("SELECT COUNT(*) as total FROM products")
    ->fetch_assoc()['total'];

$outOfStock = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock = 0")
    ->fetch_assoc()['total'];

$lowStockThreshold = 5;

$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM products
    WHERE stock > 0 AND stock <= ?
");
$stmt->bind_param("i", $lowStockThreshold);
$stmt->execute();
$lowStock = $stmt->get_result()->fetch_assoc()['total'];

$where = "WHERE p.name LIKE ?";
$params = ["%$keyword%"];
$types = "s";

// Lọc sắp hết hàng / hết hàng
if (isset($_GET['low_stock'])) {
    $where .= " AND p.stock > 0 AND p.stock <= ?";
    $params[] = $lowStockThreshold;
    $types .= "i";
} elseif (isset($_GET['out_stock'])) {
    $where .= " AND p.stock = 0";
}

/* ===== COUNT FILTERED ===== */
$countSql = "SELECT COUNT(*) as total FROM products p $where";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$start = $totalRows > 0 ? $offset + 1 : 0;
$end = min($offset + $limit, $totalRows);

/* ===== PRODUCT LIST ===== */
$sql = "
SELECT p.*, b.name as brand_name, c.name as category_name, img.image_url
FROM products p
LEFT JOIN brands b ON p.brand_id = b.id
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN product_images img ON img.product_id = p.id AND img.is_main = 1
$where
ORDER BY p.created_at DESC
LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>




<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <div class="admin-wrapper">

        <!-- SIDEBAR -->

        <?php include dirname(__DIR__) . "/sidebar.php"; ?>


        <!-- MAIN -->
        <div class="admin-container">

            <div class="admin-layout">

                <!-- HEADER -->
                <div class="page-header">
                    <div>
                        <h1>Quản lý sản phẩm</h1>
                        <p>Quản lý kho hàng và danh mục sản phẩm</p>
                    </div>
                    <div class="header-actions">

                        <a href="addproduct.php" class="btn-primary">
                            + Thêm sản phẩm mới
                        </a>
                    </div>
                </div>

                <!-- STATS -->

                <div class="stats-grid">

                    <!-- TỔNG SẢN PHẨM (CLICK = RESET FILTER) -->
                    <a href="product.php" class="stat-card-link <?= (!isset($_GET['low_stock']) && !isset($_GET['out_stock'])) ? 'active-card' : '' ?>">
                        <div class="stat-card">
                            <div class="stat-icon blue"><i class="fa fa-box"></i></div>
                            <div>
                                <div class="stat-title">Tổng sản phẩm</div>
                                <div class="stat-number"><?= $totalProduct ?></div>
                            </div>
                        </div>
                    </a>



                    <!-- SẮP HẾT HÀNG -->
                    <a href="?low_stock=1" class="stat-card-link <?= isset($_GET['low_stock']) ? 'active-card' : '' ?>">
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fa fa-exclamation"></i>
                            </div>
                            <div>
                                <div class="stat-title">Sắp hết hàng</div>
                                <div class="stat-number"><?= $lowStock ?></div>
                            </div>
                        </div>
                    </a>
                    <!-- HẾT HÀNG -->
                    <a href="?out_stock=1" class="stat-card-link <?= isset($_GET['out_stock']) ? 'active-card' : '' ?>">
                        <div class="stat-card">
                            <div class="stat-icon red">
                                <i class="fa fa-exclamation"></i>
                            </div>
                            <div>
                                <div class="stat-title">Hết hàng</div>
                                <div class="stat-number"><?= $outOfStock ?></div>
                            </div>
                        </div>
                    </a>




                </div>

                <!-- TABLE -->
                <div class="table-wrapper">

                    <form method="GET" class="table-filter">
                        <input type="text" name="keyword"
                            placeholder="Tìm kiếm laptop..." autocomplete="off"
                            value="<?= htmlspecialchars($keyword) ?>">
                        <button type="submit">Tìm kiếm</button>
                    </form>

                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá bán</th>
                                <th>Giá gốc</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <?php
                                $image = !empty($row['image_url'])
                                    ? BASE_URL . "/assets/images/products/" . $row['image_url']
                                    : BASE_URL . "/assets/images/products/no-image.png";

                                if ($row['stock'] == 0) {
                                    $status = '<span class="badge badge-red">Hết hàng</span>';
                                } elseif ($row['stock'] <= 5) {
                                    $status = '<span class="badge badge-orange">Sắp hết (' . $row['stock'] . ')</span>';
                                } else {
                                    $status = '<span class="badge badge-green">Còn hàng (' . $row['stock'] . ')</span>';
                                }
                                ?>

                                <tr>
                                    <td><img src="<?= $image ?>" class="product-img"></td>

                                    <td>
                                        <div class="product-name">
                                            <?= htmlspecialchars($row['name']) ?>
                                            <small><?= $row['brand_name'] ?></small>
                                        </div>
                                    </td>

                                    <td><span class="category-tag"><?= $row['category_name'] ?></span></td>

                                    <td class="price">
                                        <?= number_format($row['sale_price'] ?? $row['price']) ?>đ
                                    </td>

                                    <td>
                                        <?= $row['sale_price'] ? number_format($row['price']) . 'đ' : '' ?>
                                    </td>

                                    <td><?= $status ?></td>

                                    <td class="actions">
                                        <a href="editproduct.php?id=<?= $row['id'] ?>" class="btn-edit">Sửa</a>
                                        <button type="button" class="btn-delete"
                                            onclick="openDeleteModal(<?= $row['id'] ?>)">
                                            Xóa
                                        </button>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        </tbody>
                    </table>

                    <!-- PAGINATION -->
                    <div class="pagination-wrapper">

                        <div class="pagination-info">
                            Hiển thị <?= $start ?>–<?= $end ?> trên <?= $totalRows ?> sản phẩm
                        </div>

                        <div class="pagination">
                            <?php
                            // Loại bỏ 'page' parameter từ $_GET để tránh dupplicate
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $queryString = http_build_query($queryParams);
                            ?>

                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= $queryString ? '&' . $queryString : '' ?>">&laquo;</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?><?= $queryString ? '&' . $queryString : '' ?>"
                                    class="<?= ($i == $page) ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?><?= $queryString ? '&' . $queryString : '' ?>">&raquo;</a>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Xác nhận xóa</h3>
            <p>Bạn có chắc muốn xóa sản phẩm này không?</p>

            <form method="POST" action="deleteproduct.php">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id" id="deleteId">

                <div class="modal-actions">
                    <button type="button" onclick="closeDeleteModal()">Hủy</button>
                    <button type="submit">Xóa</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDeleteModal(id) {
            document.getElementById("deleteId").value = id;
            document.getElementById("deleteModal").style.display = "flex";
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").style.display = "none";
        }
    </script>

</body>

</html>