<?php
session_start();
require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);

$errors = [];
$currentPath = $_SERVER['PHP_SELF'];



function createSlug($string)
{
    $string = strtolower(trim($string));
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/* ================= LẤY BRAND ================= */
$brandResult = $conn->query("SELECT * FROM brands");

/* ================= LẤY CATEGORY ================= */
$categoryResult = $conn->query("SELECT * FROM categories");

/* ================= XỬ LÝ FORM ================= */



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // product
    $name = $_POST["name"];
    $slug = createSlug($name);
    $brand_id = $_POST["brand_id"];
    $category_id = $_POST["category_id"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $sale_price = $_POST["sale_price"];
    $stock = $_POST["stock"];
    $rating = $_POST["rating"];
    $status = $_POST["status"];

    // Variant
    $cpu = $_POST["cpu"];
    $ram = $_POST["ram"];
    $ssd = $_POST["ssd"];
    $gpu = $_POST["gpu"];
    $screen = $_POST["screen"];
    $pin = $_POST["pin"];
    $he_dieu_hanh = $_POST["he_dieu_hanh"];
    $kich_thuoc = $_POST["kich_thuoc"];

    if (empty($name)) {
        $errors[] = "Tên sản phẩm không được để trống";
    }

    if (empty($errors)) {

        // insert product
        $stmt = $conn->prepare("
            INSERT INTO products
            (name, slug, brand_id, category_id, description, price, sale_price, stock, rating, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?,?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssiisddiid",
            $name,
            $slug,
            $brand_id,
            $category_id,
            $description,
            $price,
            $sale_price,
            $stock,
            $status,
            $rating
        );

        $stmt->execute();

        $product_id = $stmt->insert_id;

        // upload ảnh
        if (!empty($_FILES["image"]["name"])) {

            $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $imageName = $slug . "-" . time() . "." . $extension;
            $target = "../../assets/images/products/" . $imageName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target)) {

                $imgStmt = $conn->prepare("
            INSERT INTO product_images (product_id, image_url, is_main)
            VALUES (?, ?, 1)
        ");

                $imgStmt->bind_param("is", $product_id, $imageName);
                $imgStmt->execute();
            } else {
                die("Upload ảnh thất bại");
            }
        }

        // insert variant
        $variantStmt = $conn->prepare("
            INSERT INTO product_variants
            (product_id, cpu, ram, ssd, gpu, screen, pin, he_dieu_hanh, kich_thuoc)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $variantStmt->bind_param(
            "issssssss",
            $product_id,
            $cpu,
            $ram,
            $ssd,
            $gpu,
            $screen,
            $pin,
            $he_dieu_hanh,
            $kich_thuoc
        );

        $variantStmt->execute();

        header("Location: addproduct.php?success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/product.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/addproduct.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="admin-wrapper">

        <!-- ================= SIDEBAR ================= -->
        <?php include dirname(__DIR__) . "/sidebar.php"; ?>
        <div class="admin-container">

            <div class="breadcrumbs">
                <a href="<?= BASE_URL ?>/admin/products/product.php">Sản phẩm</a> >
                <span>Thêm sản phẩm mới</span>
            </div>
            <div class="top-bar">
                <h1>Thêm sản phẩm mới</h1>
                <div>
                    <a href="product.php" class="btn-cancel">Hủy</a>
                    <button form="productForm" type="submit" class="btn-save">Lưu sản phẩm</button>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data"
                id="productForm" class="grid-layout" autocomplete="off">

                <!-- LEFT COLUMN -->
                <div class="left-column">

                    <!-- 1. Thông tin chung -->
                    <div class="card">
                        <h3>1. Thông tin chung</h3>

                        <label>Tên sản phẩm </label>
                        <input type="text" name="name" required>

                        <label>Slug sản phẩm</label>
                        <input type="text" name="slug">

                        <div class="row-2">
                            <div>
                                <label>Thương hiệu</label>
                                <select name="brand_id" required>
                                    <option value="">Chọn thương hiệu</option>
                                    <?php while ($brand = $brandResult->fetch_assoc()): ?>
                                        <option value="<?= $brand['id'] ?>">
                                            <?= $brand['name'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label>Danh mục</label>
                                <select name="category_id" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php while ($cat = $categoryResult->fetch_assoc()): ?>
                                        <option value="<?= $cat['id'] ?>">
                                            <?= $cat['name'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>


                    </div>

                    <!-- 3. Thông số kỹ thuật -->
                    <div class="card">
                        <h3>3. Thông số kỹ thuật</h3>

                        <div class="row-2">
                            <input type="text" name="cpu" placeholder="CPU">
                            <input type="text" name="ram" placeholder="RAM">
                        </div>

                        <div class="row-2">
                            <input type="text" name="ssd" placeholder="SSD">
                            <input type="text" name="gpu" placeholder="GPU">
                        </div>

                        <div class="row-2">
                            <input type="text" name="screen" placeholder="Màn hình">
                            <input type="text" name="pin" placeholder="Pin">
                        </div>

                        <div class="row-2">
                            <input type="text" name="he_dieu_hanh" placeholder="Hệ điều hành">
                            <input type="text" name="kich_thuoc" placeholder="Kích thước">
                        </div>
                    </div>

                    <!-- 4. Mô tả -->
                    <div class="card">
                        <h3>4. Mô tả sản phẩm</h3>
                        <textarea name="description" rows="6"
                            placeholder="Nhập mô tả chi tiết sản phẩm..."></textarea>
                    </div>

                </div>


                <!-- RIGHT COLUMN -->
                <div class="right-column">

                    <!-- 2. Giá & Kho -->
                    <div class="card">
                        <h3>2. Giá & Kho hàng</h3>

                        <label>Giá gốc (VND)</label>
                        <input type="number" name="price" min="0">

                        <label>Giá khuyến mãi (VND)</label>
                        <input type="number" name="sale_price" min="0">

                        <label>Số lượng</label>
                        <input type="number" name="stock" min="0" value="">

                    </div>


                    <div class="card">
                        <h3>5. Hình ảnh</h3>

                        <div class="upload-box" id="uploadBox">
                            <input type="file" name="image" id="imageInput" accept="image/*" hidden>

                            <div class="upload-content" id="uploadContent">
                                <i class="fa fa-cloud-upload-alt upload-icon"></i>
                                <p class="upload-text">Kéo thả ảnh vào đây</p>
                                <span>Hỗ trợ JPG, PNG, WEBP (Max 5MB)</span>
                            </div>
                            <div style="position:relative; display:inline-block;">
                                <img id="previewImage" src="<?= BASE_URL ?>/assets/images/products/<?= $image['image_url'] ?? 'no-image.png' ?>"
                                    style="width:150px; border:1px solid #ccc; padding:5px;">
                                <span id="removeImage" class="removeImage"> &times; </span>
                            </div>


                        </div>

                    </div>
                    <div class="card">
                        <h3>6. Đánh giá sản phẩm</h3>
                        <div class="rating-box">
                            <label>Đánh giá ★ (rating)</label>
                            <div class="rating-input">
                                <input type="number" name="rating" min="0" max="5" step="0.1" placeholder="VD: 4.5">

                            </div>
                        </div>
                    </div>


                    <!-- Trạng thái -->
                    <div class="card status-card">
                        <h3>Trạng thái</h3>

                        <label class="status-option">
                            <input type="radio" name="status" value="1" checked>
                            <span>Công khai <small>(Hiển thị ngay)</small></span>
                        </label>

                        <label class="status-option">
                            <input type="radio" name="status" value="0">
                            <span>Bản nháp</span>
                        </label>
                    </div>


                </div>
                <?php if (isset($_GET['success'])): ?> <div id="toast" class="toast-success">
                        ✔ Lưu sản phẩm thành công </div>
                <?php endif; ?>
            </form>
        </div>

        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast');
                if (toast) {
                    toast.remove();
                }

                // Xoá ?success=1 khỏi URL
                if (window.location.search.includes('success')) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

            }, 3500);
        </script>

</body>

<script>
    function createSlug(str) {
        return str
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/đ/g, "d")
            .replace(/[^a-z0-9\s-]/g, "")
            .trim()
            .replace(/\s+/g, "-")
            .replace(/-+/g, "-");
    }

    document.querySelector('input[name="name"]').addEventListener("input", function() {
        const slugInput = document.querySelector('input[name="slug"]');
        slugInput.value = createSlug(this.value);
    });

    document.getElementById("imageInput").addEventListener("change", function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById("previewImage").src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    const imageInput = document.getElementById("imageInput");
    const previewImage = document.getElementById("previewImage");
    const removeImage = document.getElementById("removeImage");
    const uploadBox = document.getElementById("uploadBox");
    const uploadContent = document.getElementById("uploadContent");

    const defaultImage = "<?= BASE_URL ?>/assets/images/products/<?= $image['image_url'] ?? 'no-image.png' ?>";

    imageInput.addEventListener("change", function(e) {
        const file = e.target.files[0];

        if (file) {
            previewImage.src = URL.createObjectURL(file);
            previewImage.style.display = "block";
            removeImage.style.display = "block";
            uploadContent.style.display = "none";
        }
    });


    removeImage.addEventListener("click", function(e) {
        e.stopPropagation();

        imageInput.value = "";
        previewImage.src = defaultImage;


        if (!imageInput.files.length) {
            uploadContent.style.display = "block";
            previewImage.style.display = "none";
            removeImage.style.display = "none";
        }
    });


    uploadContent.addEventListener("click", function() {
        imageInput.click();
    });

    uploadBox.addEventListener("dragover", (e) => {
        e.preventDefault();
        uploadBox.style.borderColor = "#2563eb";
    });

    uploadBox.addEventListener("dragleave", () => {
        uploadBox.style.borderColor = "#d1d5db";
    });

    uploadBox.addEventListener("drop", (e) => {
        e.preventDefault();
        uploadBox.style.borderColor = "#d1d5db";

        const file = e.dataTransfer.files[0];
        if (file) {
            imageInput.files = e.dataTransfer.files;

            previewImage.src = URL.createObjectURL(file);
            previewImage.style.display = "block";
            removeImage.style.display = "block";
            uploadContent.style.display = "none";
        }
    });
</script>


</html>