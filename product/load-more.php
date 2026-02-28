<?php
require_once "../config/config.php";
require_once "../config/database.php";
require_once "../includes/functions.php";


/* ===== PAGINATION ===== */
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;


/* ===== BUILD WHERE FUNCTION ===== */
function buildWhere($conn)
{
    $conditions = ["p.status = 1"];
    $params     = [];
    $types      = "";


    $add = function ($condition, $value = null, $type = "") use (&$conditions, &$params, &$types) {
        $conditions[] = $condition;
        if ($value !== null) {
            $params[] = $value;
            $types   .= $type;
        }
    };


    if (!empty($_GET['category'])) {
        $category = getCategoryBySlug($conn, $_GET['category']);

        if ($category) {
            $add("p.category_id = ?", $category['id'], "i");
        }
    }


    if (!empty($_GET['keyword'])) {
        $search = "%" . $_GET['keyword'] . "%";

        $conditions[] = "(
            p.name LIKE ?
            OR b.name LIKE ?
            OR EXISTS (
                SELECT 1 FROM product_variants v0
                WHERE v0.product_id = p.id
                AND v0.cpu LIKE ?
            )
        )";

        array_push($params, $search, $search, $search);
        $types .= "sss";
    }

    /* ===== BRAND ===== */
    if (!empty($_GET['brand'])) {
        $add("p.brand_id = ?", (int)$_GET['brand'], "i");
    }

    /* ===== RAM ===== */
    if (!empty($_GET['ram'])) {
        $add("
            EXISTS (
                SELECT 1 FROM product_variants v1
                WHERE v1.product_id = p.id
                AND v1.ram LIKE CONCAT('%', ?, '%')
            )
        ", $_GET['ram'], "s");
    }

    /* ===== SSD ===== */
    if (!empty($_GET['ssd'])) {
        $ssdNumber = preg_replace('/\D/', '', $_GET['ssd']);

        $add("
            EXISTS (
                SELECT 1 FROM product_variants v2
                WHERE v2.product_id = p.id
                AND v2.ssd REGEXP CONCAT('(^|[^0-9])', ?, '([^0-9]|$)')
            )
        ", $ssdNumber, "s");
    }

    $where = "WHERE " . implode(" AND ", $conditions);

    return [$where, $params, $types];
}


/* ===== APPLY WHERE ===== */
list($where, $params, $types) = buildWhere($conn);


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
        MIN(pv.cpu) AS cpu,
        MIN(pv.ram) AS ram,
        MIN(pv.ssd) AS ssd,
        MAX(CASE WHEN pi.is_main = 1 THEN pi.image_url END) AS image_url
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    LEFT JOIN product_images pi ON p.id = pi.product_id
    LEFT JOIN categories c ON p.category_id = c.id
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




foreach ($products as $product):

    $image = !empty($product['image_url'])
        ? BASE_URL . '/assets/images/products/' . $product['image_url']
        : BASE_URL . '/assets/images/products/no-image.png';
?>
    <div class="product-card">

        <div class="product-label">
            <div class="product-rating">
                <span class="star">★</span>
                <span class="rating-number"><?= round($product['rating']) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/product/detail.php?slug=<?= e($product['slug']) ?>" class="product-thumb">
                <img src="<?= $image ?>" alt="<?= e($product['name']) ?>">
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