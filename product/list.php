<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/includes/functions.php";


/* ===== PAGINATION ===== */
$limit  = 8;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;



function buildFilter($conn)
{
    $where  = "WHERE p.status = 1";
    $params = [];
    $types  = "";


    $categorySlug = $_GET['category'] ?? null;

    if ($categorySlug) {
        $category = getCategoryBySlug($conn, $categorySlug);

        if (!$category) {
            return [null, [], ""];
        }

        $where   .= " AND p.category_id = ?";
        $params[] = $category['id'];
        $types   .= "i";
    }

    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    if (!empty($keyword)) {
        $where .= " AND (
            p.name LIKE ?
            OR b.name LIKE ?
            OR EXISTS (
                SELECT 1 FROM product_variants pv2
                WHERE pv2.product_id = p.id
                AND pv2.cpu LIKE ?
            )
        )";

        $search = "%$keyword%";
        $params = array_merge($params, [$search, $search, $search]);
        $types .= "sss";
    }


    $brand = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;

    if ($brand > 0) {
        $where   .= " AND p.brand_id = ?";
        $params[] = $brand;
        $types   .= "i";
    }

    return [$where, $params, $types];
}

/* ===== APPLY FILTER ===== */
list($where, $params, $types) = buildFilter($conn);

// category không tồn tại
if ($where === null) {
    $products = [];
    $totalProducts = 0;
    return;
}

/* ===== MAIN QUERY ===== */
$sql = "
    SELECT 
        p.id,
        p.name,
        p.slug,
        p.price,
        p.sale_price,
        p.rating,
        b.name AS brand_name,
        c.name AS category_name,
        MAX(CASE WHEN pi.is_main = 1 THEN pi.image_url END) AS image_url
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    LEFT JOIN product_images pi ON p.id = pi.product_id
    $where
    GROUP BY p.id
    ORDER BY p.id DESC
    LIMIT ? OFFSET ?
";

$paramsWithLimit = [...$params, $limit, $offset];
$typesWithLimit  = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$stmt->execute();

$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);


/* ===== COUNT QUERY (REUSE FILTER) ===== */
$countSql = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    $where
";

$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalProducts = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;


/* ===== GET BRANDS ===== */
$brandSql = "
    SELECT id, name, logo_image 
    FROM brands 
    ORDER BY name ASC
";

$brandResult = $conn->query($brandSql);
$brands = $brandResult ? $brandResult->fetch_all(MYSQLI_ASSOC) : [];
?>



<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sản phẩm - <?= SITE_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/productlist.css">
</head>

<body>

    <?php include dirname(__DIR__) . "/includes/header.php"; ?>

    <div class="container">

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

        <h2 class="page-title">
            <?= isset($category) && $category ? 'Laptop ' . e($category['name']) : 'Danh sách sản phẩm' ?>
        </h2>




        <div class="product-grid">

            <?php if (empty($products)): ?>
                <p>Không có sản phẩm nào.</p>
            <?php else: ?>
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
                                    class="cart-icon"> <i class="fa fa-cart-plus"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/cart/add.php?id=<?= $product['id'] ?>&buy_now=1"
                                    class="btn btn-primary"> Mua ngay
                                </a>

                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>

        </div>
        <?php if ($totalProducts > count($products)): ?>
            <div class="load-more-wrap">
                <button id="loadMoreBtn"
                    data-page="1"
                    data-total="<?= $totalProducts ?>"
                    data-loaded="<?= count($products) ?>"
                    data-keyword="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>"
                    data-brand="<?= htmlspecialchars($_GET['brand'] ?? '') ?>"
                    data-ram="<?= htmlspecialchars($_GET['ram'] ?? '') ?>"
                    data-ssd="<?= htmlspecialchars($_GET['ssd'] ?? '') ?>"
                    data-category="<?= htmlspecialchars($_GET['category'] ?? '') ?>"
                    class="load-more-btn">
                    Xem thêm sản phẩm <i class="fa fa-chevron-down"></i>
                </button>
            </div>
        <?php endif; ?>



    </div>
    <?php include dirname(__DIR__) . "/includes/footer.php"; ?>



</body>

<script>
    const grid = document.querySelector('.product-grid');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const filters = document.querySelectorAll('#filterForm select');

    const brandFilter = document.querySelector('select[name="brand"]');
    const ramFilter = document.querySelector('select[name="ram"]');
    const ssdFilter = document.querySelector('select[name="ssd"]');

    let nextPageToLoad = 2;
    let totalProducts = loadMoreBtn ? parseInt(loadMoreBtn.dataset.total) : 0;
    let loadedProducts = loadMoreBtn ? parseInt(loadMoreBtn.dataset.loaded) : 0;
    let initialCount = loadedProducts;
    let isCollapsed = false;

    function updateButtonText() {
        if (!loadMoreBtn) return;

        if (loadedProducts >= totalProducts) {

            if (!isCollapsed) {
                loadMoreBtn.innerHTML = 'Ẩn bớt <i class="fa fa-chevron-up"></i>';
            }
        } else {

            loadMoreBtn.innerHTML = 'Xem thêm sản phẩm <i class="fa fa-chevron-down"></i>';
        }
    }

    function toggleCollapse() {
        if (loadedProducts < totalProducts) {
            loadProducts(false);
        } else {

            const cards = grid.querySelectorAll('.product-card');

            if (!isCollapsed) {

                for (let i = initialCount; i < cards.length; i++) {
                    cards[i].style.display = 'none';
                }
                loadMoreBtn.innerHTML = 'Xem thêm sản phẩm <i class="fa fa-chevron-down"></i>';
                isCollapsed = true;
            } else {

                for (let i = initialCount; i < cards.length; i++) {
                    cards[i].style.display = '';
                }
                loadMoreBtn.innerHTML = 'Ẩn bớt <i class="fa fa-chevron-up"></i>';
                isCollapsed = false;
            }
        }
    }

    function loadProducts(reset = false) {

        if (reset) {
            nextPageToLoad = 2;
            loadedProducts = initialCount;
            grid.innerHTML = '';
            isCollapsed = false;
            updateButtonText();
            return;
        }

        // Lấy toàn bộ query hiện tại trên URL (keyword, category...)
        const urlParams = new URLSearchParams(window.location.search);

        // Cập nhật page để load
        urlParams.set('page', nextPageToLoad);
        if (brandFilter) urlParams.set('brand', brandFilter.value);
        if (ramFilter) urlParams.set('ram', ramFilter.value);
        if (ssdFilter) urlParams.set('ssd', ssdFilter.value);

        fetch(`load-more.php?${urlParams.toString()}`)
            .then(res => res.text())
            .then(html => {

                if (html.trim() === '') {
                    loadedProducts = totalProducts;
                    updateButtonText();
                    return;
                }

                grid.insertAdjacentHTML('beforeend', html);

                const allCards = grid.querySelectorAll('.product-card');
                loadedProducts = allCards.length;


                nextPageToLoad++;

                updateButtonText();
            })
            .catch(err => {
                console.error('Load error:', err);
            });
    }

    // LOAD MORE / TOGGLE COLLAPSE
    if (loadMoreBtn) {
        updateButtonText();

        loadMoreBtn.addEventListener('click', () => {
            toggleCollapse();
        });
    }


    filters.forEach(select => {
        select.addEventListener('change', () => {
            loadProducts(true);
        });
    });
</script>




</html>