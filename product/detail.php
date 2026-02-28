<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/includes/functions.php";



$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($_GET['slug'])) {
    header("Location: " . BASE_URL);
    exit;
}


$category = isset($_GET['category']) ? trim($_GET['category']) : 0;

if (!empty($category)) {


    header("Location: " . BASE_URL);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        p.*,
        b.name AS brand_name,
        c.name AS category_name,
        c.slug AS category_slug
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ? AND p.status = 1
    LIMIT 1
");

$stmt->bind_param("s", $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: " . BASE_URL);
    exit;
}


$stmt = $conn->prepare("
    SELECT image_url, is_main
    FROM product_images
    WHERE product_id = ?
    ORDER BY is_main DESC, id ASC
");
$stmt->bind_param("i", $product['id']);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* BIẾN THỂ */
$stmt = $conn->prepare("
    SELECT *
    FROM product_variants
    WHERE product_id = ?
");
$stmt->bind_param("i", $product['id']);
$stmt->execute();
$variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>



<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= e($product['name']) ?> - <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/productdetail.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/productlist.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">
</head>

<body>

    <?php include dirname(__DIR__) . "/includes/header.php"; ?>

    <div class="container product-detail">


        <div class="breadcrumb">
            <a href="<?= BASE_URL ?>">Trang chủ</a> /
            <a href="<?= BASE_URL ?>/product/list.php">Sản phẩm</a> /
            <a href="<?= BASE_URL ?>/product/list.php?category=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a> /
            <span><?= e($product['name']) ?></span>
        </div>

        <div class="product-detail-grid">


            <div class="product-gallery">
                <?php
                $mainImage = $images[0]['image_url'] ?? null;
                ?>
                <img class="main-image"
                    src="<?= $mainImage ? BASE_URL . '/assets/images/products/' . $mainImage : BASE_URL . '/assets/images/products/no-image.png' ?>">


            </div>


            <div class="product-summary">
                <h1><?= e($product['name']) ?></h1>


                <div class="meta"> <span>Thương hiệu: <b><?= e($product['brand_name']) ?></b></span>
                    <span>Danh mục: <?= e($product['category_name']) ?></span>
                </div>
                <div class="stock-info">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="in-stock">Còn hàng (<?= round($product['stock']) ?>)</span>
                    <?php else: ?>
                        <span class="out-of-stock">Hết hàng (<?= round($product['stock']) ?>)</span>
                    <?php endif; ?>
                </div>

                <div class="price-box">
                    <div>
                        <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                            <?php
                            $discountPercent = round(
                                (1 - ($product['sale_price'] / $product['price'])) * 100
                            );
                            ?>
                            <div class="old-price-row">
                                <span class="old-price"><?= formatPrice($product['price']) ?></span>
                                <span class="discount-badge">-<?= $discountPercent ?>%</span>
                            </div>

                            <div class="new-price">
                                <?= formatPrice($product['sale_price']) ?>
                            </div>
                        <?php else: ?>
                            <div class="new-price">
                                <?= formatPrice($product['price']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($variants): ?> <div class="variants">
                        <h4>Cấu hình:</h4> <?php foreach ($variants as $v): ?>
                            <div class="variant">
                                <span><?= e($v['cpu']) ?> / <?= e($v['ram']) ?> / <?= e($v['ssd']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div> <?php endif; ?>

                <div class="product-actions-detail">
                    <a href="<?= BASE_URL ?>/cart/add.php?id=<?= $product['id'] ?>&redirect=slug=<?= $product['slug'] ?>"
                        class="btn-buy">
                        <i class="fa fa-cart-plus"></i> Thêm vào giỏ hàng
                    </a>

                    <a href="<?= BASE_URL ?>/cart/add.php?id=<?= $product['id'] ?>&buy_now=1"
                        class="btn btn-primary">
                        Mua ngay
                    </a>
                </div>
            </div>

        </div>



        <div class="product-extra">

            <div class="extra-grid">

                <div class="spec-box">
                    <h3>Cấu hình chi tiết</h3>

                    <table class="spec-table">
                        <?php if (!empty($variants)): ?>
                            <?php

                            $v = $variants[0];
                            ?>
                            <tr>
                                <td>CPU</td>
                                <td><?= e($v['cpu']) ?></td>
                            </tr>
                            <tr>
                                <td>RAM</td>
                                <td><?= e($v['ram']) ?></td>
                            </tr>
                            <tr>
                                <td>Ổ cứng</td>
                                <td><?= e($v['ssd']) ?></td>
                            </tr>
                            <tr>
                                <td>Card đồ họa</td>
                                <td><?= e($v['gpu']) ?></td>
                            </tr>
                            <tr>
                                <td>Màn hình</td>
                                <td><?= e($v['screen']) ?></td>
                            </tr>
                            <tr>
                                <td>Pin</td>
                                <td><?= e($v['pin']) ?></td>
                            </tr>
                            <tr>
                                <td>Hệ điều hành</td>
                                <td><?= e($v['he_dieu_hanh']) ?></td>
                            </tr>
                            <tr>
                                <td>Kích thước</td>
                                <td><?= e($v['kich_thuoc']) ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">Đang cập nhật cấu hình</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>


                <div class="desc-box">
                    <h3>Mô tả ngắn</h3>

                    <p class="short-desc">
                        <?= nl2br(e(mb_strimwidth($product['description'], 0, 300, '...'))) ?>
                    </p>

                    <div class="promo-box">
                        <div class="promo-title">
                            <i class="fa fa-gift"></i> Ưu đãi đặc biệt
                        </div>
                        <ul>
                            <li>Tặng balo laptop cao cấp</li>
                            <li>Tặng chuột không dây chính hãng</li>
                            <li>Giảm giá khi nâng cấp RAM / SSD</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
    </div>
    <?php include dirname(__DIR__) . "/includes/footer.php"; ?>
</body>

</html>