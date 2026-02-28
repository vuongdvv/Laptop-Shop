<?php
include 'config/database.php';
include 'includes/header.php';

/* ====== FUNCTION: GET PRODUCTS ====== */
function getProducts($conn, $brandFilter = 0)
{
    $sql = "
    SELECT 
        p.id,
        p.name,
        p.slug,
        p.price,
        p.sale_price,
        p.rating,
        b.name AS brand_name,
        img.image_url,
        v.cpu,
        v.ram,
        v.ssd
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN product_images img 
        ON img.product_id = p.id AND img.is_main = 1
    LEFT JOIN product_variants v 
        ON v.product_id = p.id
    WHERE p.stock > 0
    ";

    // filter brand
    if ($brandFilter > 0) {
        $sql .= " AND p.brand_id = $brandFilter ";
    }

    // order + limit
    $sql .= " ORDER BY p.id DESC LIMIT 4";

    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}


/* ====== FUNCTION: GET BRANDS ====== */
function getBrands($conn)
{
    $sql = "
    SELECT 
        id, 
        name, 
        logo_image 
    FROM brands
    ";

    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}


/* ====== USE FUNCTION ====== */
$brandFilter = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;

$products = getProducts($conn, $brandFilter);
$brands   = getBrands($conn);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>TechStore - Laptop chính hãng</title>
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/productlist.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <main>


        <div class="home-banner">
            <div class="banner-content">
            </div>
        </div>


        <div class="brands">

            <h2>Thương hiệu</h2>

            <div class="brand-list">
                <a href="?" class="brand-item <?= ($brandFilter == 0) ? 'active' : '' ?>">
                    Tất cả
                </a>
                <?php foreach ($brands as $brand): ?>

                    <?php
                    $logoPath = !empty($brand['logo_image'])
                        ? BASE_URL . 'assets/images/brands/' . $brand['logo_image']
                        : BASE_URL . 'assets/images/brands/no-image.png';
                    ?>

                    <a href="?brand=<?= $brand['id'] ?>"
                        class="brand-item <?= ($brandFilter == $brand['id']) ? 'active' : '' ?>">

                        <img src="<?= $logoPath ?>"
                            alt="<?= e($brand['name']) ?>">

                    </a>

                <?php endforeach; ?>
            </div>
        </div>

        <div class="categories">
            <h2>Danh mục</h2>

            <div class="category-list">
                <a href="<?= BASE_URL ?>/product/list.php?category=gaming" class="category-item">Gaming</a>
                <a href="<?= BASE_URL ?>/product/list.php?category=van-phong" class="category-item">Văn phòng</a>
                <a href="<?= BASE_URL ?>/product/list.php?category=mong-nhe" class="category-item">Mỏng nhẹ</a>
                <a href="<?= BASE_URL ?>/product/list.php?category=cao-cap" class="category-item">Cao cấp</a>
                <a href="<?= BASE_URL ?>/product/list.php?category=laptop-ai" class="category-item">Laptop AI</a>
            </div>
        </div>


        <div class="featured-products">
            <div class="section-header">
                <h2>Sản phẩm nổi bật</h2>
                <a href="product/list.php">Xem tất cả</a>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $product): ?>

                    <?php
                    $imagePath = !empty($product['image_url'])
                        ? BASE_URL . '/assets/images/products/' . $product['image_url']
                        : BASE_URL . '/assets/images/products/no-image.png';
                    ?>

                    <div class="product-card">

                        <div class=" product-label">
                            <div class="product-rating">
                                <span class="star">★</span>
                                <span class="rating-number"><?= number_format($product['rating'], 1) ?></span>
                            </div>
                            <a href="<?= BASE_URL ?>/product/detail.php?slug=<?= e($product['slug']) ?>" class="product-thumb">
                                <img src="<?= $imagePath ?>" alt="<?= e($product['name']) ?>">
                            </a>
                        </div>

                        <div class="product-info">


                            <div class="product-brand">
                                <?= strtoupper(e($product['brand_name'])) ?>
                            </div>


                            <h3 class="product-name">
                                <?= e($product['name']) ?>
                            </h3>


                            <div class="product-tags">
                                <?php if (!empty($product['cpu'])): ?>
                                    <span><?= e($product['cpu']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($product['ram'])): ?>
                                    <span><?= e($product['ram']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($product['ssd'])): ?>
                                    <span><?= e($product['ssd']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="product-price-box">
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

                            <div class="product-actions">
                                <a href="<?= BASE_URL ?>/cart/add.php?id=<?= $product['id'] ?>&redirect=list"
                                    class="cart-icon">
                                    <i class="fa fa-cart-plus"></i>
                                </a>

                                <a href="<?= BASE_URL ?>/cart/add.php?id=<?= $product['id'] ?>&buy_now=1"
                                    class="btn btn-primary">
                                    Mua ngay
                                </a>
                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>
            </div>
        </div>

        <div class="promotion">
            <div class="promo-box">
                <div class="promo-content">
                    <h2>Mùa Tự Trường<br>Giảm giá tới 20%</h2>
                    <p>Ưu đãi laptop cho học sinh – sinh viên trong thời gian có hạn.</p>
                    <a href="product/list.php" class="btn-outline-white">Xem khuyến mãi</a>
                </div>
                <div class="promo-image">
                    <img src="<?= BASE_URL ?>/assets/images/products/banner-promo.jpg" alt="">
                </div>
            </div>
        </div>


        <section class="services">
            <div class="service-item">
                <i class="fa fa-shield"></i>
                <h4>Bảo hành chính hãng</h4>
                <p>100% laptop chính hãng</p>
            </div>

            <div class="service-item">
                <i class="fa fa-truck"></i>
                <h4>Miễn phí vận chuyển</h4>
                <p>Toàn quốc – Nhanh chóng</p>
            </div>

            <div class="service-item">
                <i class="fa fa-headset"></i>
                <h4>Hỗ trợ 24/7</h4>
                <p>Đội ngũ kỹ thuật chuyên nghiệp</p>
            </div>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>

</body>

</html>