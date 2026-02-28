<?php
// includes/footer.php
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/footer.css">
<footer class="footer">
    <div class="footer-container container">

        <!-- Cột 1: Logo + mô tả -->
        <div class="footer-col">
            <div class="footer-logo">
                <span class="logo-icon">💻</span>
                <span class="logo-text">TechStore</span>
            </div>
            <p class="footer-desc">
                Địa chỉ tin cậy cho những tín đồ công nghệ.
                Chúng tôi mang đến những sản phẩm laptop chất lượng nhất.
            </p>
        </div>

        <div class="footer-col">
            <h4>Sản phẩm</h4>
            <ul>
                <li><a href="<?= BASE_URL ?>/product/list.php">Laptops</a></li>
                <li><a href="<?= BASE_URL ?>/product?type=phu-kien">Phụ kiện</a></li>
                <li><a href="<?= BASE_URL ?>/product?type=may-cu">Máy cũ giá rẻ</a></li>
                <li><a href="<?= BASE_URL ?>/product?type=xa-kho">Xả kho</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Hỗ trợ</h4>
            <ul>
                <li><a href="/user/bao-hanh.php">Trung tâm bảo hành</a></li>
                <li><a href="/user/huong-dan.php">Hướng dẫn mua hàng</a></li>
                <li><a href="/user/doi-tra.php">Chính sách đổi trả</a></li>
                <li><a href="/user/lien-he.php">Liên hệ</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Đăng ký nhận tin</h4>
            <form class="newsletter-form" method="post" action="#">
                <input type="email" name="email" placeholder="Email của bạn" required>
                <button type="submit">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="white" d="M2 21L23 12L2 3V10L17 12L2 14V21Z" />
                    </svg>
                </button>
            </form>
        </div>

    </div>


</footer>