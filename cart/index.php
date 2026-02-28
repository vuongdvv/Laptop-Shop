<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once dirname(__DIR__) . "/config/database.php";
require_once dirname(__DIR__) . "/includes/functions.php";

/* =========================
   KIỂM TRA ĐĂNG NHẬP
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* =========================
   LẤY CART ID
========================= */
$stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();

$cartItems = [];
$totalPrice = 0;

if ($cart) {
    $cartId = $cart['id'];

    /* =========================
       LẤY SẢN PHẨM TRONG GIỎ
    ========================= */
    $stmt = $conn->prepare("
        SELECT 
            ci.id AS cart_item_id,
            ci.quantity,
            ci.price,

            p.id AS product_id,
            p.name,
            p.slug,

            pi.image_url
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_images pi 
            ON pi.product_id = p.id AND pi.is_main = 1
        WHERE ci.cart_id = ?
        ORDER BY ci.id DESC
    ");
    $stmt->bind_param("i", $cartId);
    $stmt->execute();
    $cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($cartItems as $item) {
        $totalPrice += $item['price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng - <?= SITE_NAME ?></title>
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



        <h2 class="page-title">Giỏ hàng của bạn</h2>

        <?php if (empty($cartItems)): ?>

            <div class="empty-cart">
                <div class="empty-cart-content">
                    <img src="<?= BASE_URL ?>/assets/images/products/empty-cart.png"
                        alt="Giỏ hàng trống">

                    <h3>Giỏ hàng trống</h3>
                    <p>Không có sản phẩm nào trong giỏ hàng</p>
                    <a href="<?= BASE_URL ?>/product/list.php"
                        class="btn-continue-shopping">
                        ← Tiếp tục mua sắm
                    </a>

                </div>

            <?php else: ?>

                <form action="<?= BASE_URL ?>/cart/checkout.php"
                    method="post"
                    id="checkoutForm">

                    <div class="cart-page">

                        <!-- ================= LEFT: CART LIST ================= -->
                        <div class="cart-card">

                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <td></td>
                                        <td data-label="Sản phẩm">Sản Phẩm</td>
                                        <td data-label="Giá">Giá</td>
                                        <td data-label="Số lượng">Số lượng</td>
                                        <td data-label="Tổng cộng">Tổng cộng</td>

                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <?php
                                        $imagePath = !empty($item['image_url'])
                                            ? BASE_URL . "/assets/images/products/" . $item['image_url']
                                            : BASE_URL . "/assets/images/products/no-image.png";
                                        ?>

                                        <tr>
                                            <td>
                                                <input type="checkbox"
                                                    name="selected_items[]"
                                                    value="<?= $item['cart_item_id'] ?>"
                                                    class="item-checkbox"
                                                    data-price="<?= $item['price'] * $item['quantity'] ?>">
                                            </td>

                                            <td>
                                                <div class="cart-product">
                                                    <img src="<?= $imagePath ?>" alt="<?= e($item['name']) ?>">
                                                    <div>
                                                        <a href="<?= BASE_URL ?>/product/detail.php?slug=<?= e($item['slug']) ?>">
                                                            <?= e($item['name']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="cart-price">
                                                <?= formatPrice($item['price']) ?>
                                            </td>

                                            <td>
                                                <div class="qty-box">
                                                    <button type="button" onclick="decreaseQty(this, <?= $item['cart_item_id'] ?>, <?= $item['price'] ?>)">−</button>
                                                    <input type="number" name="quantity" min="1"
                                                        value="<?= $item['quantity'] ?>"
                                                        data-cart-item-id="<?= $item['cart_item_id'] ?>"
                                                        data-price="<?= $item['price'] ?>"
                                                        oninput="handleManualInput(this)">
                                                    <button type="button" onclick="increaseQty(this, <?= $item['cart_item_id'] ?>, <?= $item['price'] ?>)">+</button>
                                                </div>
                                            </td>

                                            <td class="cart-price">
                                                <?= formatPrice($item['price'] * $item['quantity']) ?>
                                            </td>

                                            <td>
                                                <a href="<?= BASE_URL ?>/cart/remove.php?id=<?= $item['cart_item_id'] ?>"
                                                    class="remove-btn">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <a href="<?= BASE_URL ?>/product/list.php" class="cart-continue-shopping">
                                ← Tiếp tục mua sắm
                            </a>

                        </div>

                        <!-- ================= RIGHT: SUMMARY ================= -->
                        <div class="cart-card cart-summary">

                            <h3>Tóm tắt đơn hàng</h3>

                            <div class="summary-row">
                                <span>Tạm tính</span>
                                <span id="subtotal">0 đ</span>
                            </div>

                            <div class="summary-row total">
                                <span>Tổng cộng</span>
                                <span id="total">0 đ</span>
                            </div>

                            <button type="submit" class="btn-checkout">
                                🔒 Tiến hành thanh toán
                            </button>

                        </div>

                    </div>
                </form>

            </div>

        <?php endif; ?>

    </div>



    <script>
        const BASE_URL = "<?= BASE_URL ?>";

        function decreaseQty(btn, cartItemId, price) {
            const input = btn.nextElementSibling;
            const oldQty = parseInt(input.value);
            if (input.value > 1) {
                input.value--;
                updateQuantity(cartItemId, input.value, price, input, oldQty);
            }
        }

        function increaseQty(btn, cartItemId, price) {
            const input = btn.previousElementSibling;
            const oldQty = parseInt(input.value);
            input.value++;
            updateQuantity(cartItemId, input.value, price, input, oldQty);
        }

        function updateQuantity(cartItemId, quantity, price, inputElement, oldQty) {
            fetch(BASE_URL + "/cart/update.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: `cart_item_id=${cartItemId}&quantity=${quantity}`
                })
                .then(response => response.text())
                .then(() => {
                    // Cập nhật tổng tiền của sản phẩm này
                    const totalPrice = price * quantity;
                    const row = inputElement.closest('tr');
                    const totalCell = row.querySelector('td:nth-child(5)'); // Column "Tổng cộng"
                    totalCell.innerText = formatCurrency(totalPrice);

                    // Cập nhật data-price của checkbox
                    const checkbox = row.querySelector('.item-checkbox');
                    checkbox.dataset.price = totalPrice;

                    // Cập nhật cart-badge
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        const qtyDiff = quantity - oldQty;
                        const newBadgeCount = parseInt(cartBadge.innerText) + qtyDiff;

                        if (newBadgeCount > 0) {
                            cartBadge.innerText = newBadgeCount;
                        } else {
                            cartBadge.remove();
                        }
                    } else if (quantity - oldQty > 0) {
                        // Nếu không có badge, tạo mới
                        const cartLink = document.querySelector('a[href="' + BASE_URL + '/cart/index.php"]');
                        if (cartLink) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'cart-badge';
                            newBadge.innerText = quantity - oldQty;
                            cartLink.appendChild(newBadge);
                        }
                    }

                    // Recalculate tổng tiền đơn hàng
                    updateTotal();
                })
                .catch(error => console.error("Error:", error));
        }

        function formatCurrency(number) {
            return number.toLocaleString('vi-VN') + " đ";
        }

        function updateTotal() {
            let total = 0;

            document.querySelectorAll(".item-checkbox:checked").forEach(cb => {
                total += parseInt(cb.dataset.price);
            });

            document.getElementById("subtotal").innerText = formatCurrency(total);
            document.getElementById("total").innerText = formatCurrency(total);
        }

        document.querySelectorAll(".item-checkbox").forEach(cb => {
            cb.addEventListener("change", updateTotal);
        });

        document.getElementById("checkAll").addEventListener("change", function() {
            document.querySelectorAll(".item-checkbox").forEach(cb => {
                cb.checked = this.checked;
            });
            updateTotal();
        });

        // Bắt sự kiện nhập tay số lượng
        document.querySelectorAll('input[name="quantity"]').forEach(input => {

            input.addEventListener("input", function() {

                let quantity = parseInt(this.value);
                const cartItemId = this.dataset.cartItemId;
                const price = parseInt(this.dataset.price);
                const oldQty = parseInt(this.defaultValue);

                if (!quantity || quantity < 1) {
                    quantity = 1;
                    this.value = 1;
                }

                updateQuantity(cartItemId, quantity, price, this, oldQty);

                // Cập nhật defaultValue để lần sau tính đúng chênh lệch
                this.defaultValue = quantity;
            });

        });
        // Chặn Enter trong input số lượng
        document.querySelectorAll('input[name="quantity"]').forEach(input => {

            input.addEventListener("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault(); // Ngăn submit form
                    this.blur(); // Bỏ focus để kích hoạt input event
                }
            });

        });
    </script>

</body>

</html>