<?php
require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";


requireAdminAccess($conn);

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET['id'];
$errors = [];
$currentPath = $_SERVER['PHP_SELF'];
/* ================= LẤY PRODUCT ================= */
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: product.php");
    exit();
}

/* ================= LẤY VARIANT ================= */
$vStmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$vStmt->bind_param("i", $id);
$vStmt->execute();
$variant = $vStmt->get_result()->fetch_assoc();

/* ================= LẤY ẢNH CHÍNH ================= */
$iStmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_main = 1");
$iStmt->bind_param("i", $id);
$iStmt->execute();
$image = $iStmt->get_result()->fetch_assoc();

/* ================= LẤY BRAND & CATEGORY ================= */
$brands = $conn->query("SELECT * FROM brands");
$categories = $conn->query("SELECT * FROM categories");

/* ================= HÀM TẠO SLUG ================= */
function createSlug($string)
{
    $string = strtolower(trim($string));
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/* ================= UPDATE ================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST["name"];
    $slug = !empty($_POST["slug"]) ? $_POST["slug"] : createSlug($name);
    $brand_id = $_POST["brand_id"];
    $category_id = $_POST["category_id"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $sale_price = $_POST["sale_price"];
    $stock = $_POST["stock"];
    $rating = $_POST["rating"] ?? 0;
    $status = $_POST["status"];

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

        /* UPDATE PRODUCT */
        $update = $conn->prepare("
            UPDATE products 
            SET name=?, slug=?, brand_id=?, category_id=?, 
                description=?, price=?, sale_price=?, stock=?, rating=?, status=?
            WHERE id=?
        ");

        $update->bind_param(
            "ssiisddiidi",
            $name,
            $slug,
            $brand_id,
            $category_id,
            $description,
            $price,
            $sale_price,
            $stock,
            $rating,
            $status,
            $id
        );
        $update->execute();

        /* UPDATE VARIANT */
        if ($variant) {

            // Nếu đã tồn tại → UPDATE
            $vUpdate = $conn->prepare("
        UPDATE product_variants
        SET cpu=?, ram=?, ssd=?, gpu=?, screen=?, 
            pin=?, he_dieu_hanh=?, kich_thuoc=?
        WHERE product_id=?
    ");

            $vUpdate->bind_param(
                "ssssssssi",
                $cpu,
                $ram,
                $ssd,
                $gpu,
                $screen,
                $pin,
                $he_dieu_hanh,
                $kich_thuoc,
                $id
            );

            $vUpdate->execute();
        } else {


            $vInsert = $conn->prepare("
        INSERT INTO product_variants
        (product_id, cpu, ram, ssd, gpu, screen, pin, he_dieu_hanh, kich_thuoc)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

            $vInsert->bind_param(
                "issssssss",
                $id,
                $cpu,
                $ram,
                $ssd,
                $gpu,
                $screen,
                $pin,
                $he_dieu_hanh,
                $kich_thuoc
            );

            $vInsert->execute();
        }


        /* ================= UPDATE ẢNH ================= */

        if (isset($_FILES["image"]) && $_FILES["image"]["error"] === 0) {

            $uploadDir = "../../assets/images/products/";
            $imageName = time() . "_" . basename($_FILES["image"]["name"]);
            $targetPath = $uploadDir . $imageName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {


                if ($image) {

                    $imgUpdate = $conn->prepare("
                UPDATE product_images 
                SET image_url=? 
                WHERE product_id=? AND is_main=1
            ");
                    $imgUpdate->bind_param("si", $imageName, $id);
                    $imgUpdate->execute();
                } else {


                    $imgInsert = $conn->prepare("
                INSERT INTO product_images (product_id, image_url, is_main)
                VALUES (?, ?, 1)
            ");
                    $imgInsert->bind_param("is", $id, $imageName);
                    $imgInsert->execute();
                }
            }
        }

        header("Location: editproduct.php?id=" . $id . "&success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa sản phẩm</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/product.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>admin/asset/editproduct.css">
</head>

<body>
    <div class="admin-wrapper">

        <!-- ================= SIDEBAR ================= -->
        <?php include_once dirname(__DIR__) . "/sidebar.php"; ?>

        <div class="admin-container">

            <div class="breadcrumbs">
                <a href="<?= BASE_URL ?>/admin/products/product.php">Quản lý sản phẩm</a> >
                <span>Chỉnh sửa sản phẩm</span>
            </div>


            <div class="top-bar">
                <h1>Chỉnh sửa sản phẩm</h1>
                <div>
                    <a href="product.php" class="btn-cancel">Hủy</a>
                    <button form="product-edit-form" type="submit" class="btn-save">Cập nhật sản phẩm</button>
                </div>
            </div>
            <?php if (!empty($errors)): ?>
                <div style="color:red;">
                    <?php foreach ($errors as $error): ?>
                        <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data"
                id="product-edit-form" class="grid-layout" autocomplete="off">

                <div class="left-column">
                    <div class="card">
                        <h3>1. Thông tin chung</h3>

                        <label for="product-name">Tên sản phẩm</label>
                        <input type="text" id="name" name="name" value="<?= $product['name'] ?? '' ?>">

                        <label for="product-slug">Slug sản phẩm</label>
                        <input type="text" id="slug" name="slug" value="<?= $product['slug'] ?? '' ?>">


                        <div class="row-2">
                            <div>
                                <label>Thương hiệu</label>
                                <select name="brand_id">
                                    <?php while ($brand = $brands->fetch_assoc()): ?>
                                        <option value="<?= $brand['id'] ?>"
                                            <?= ($brand['id'] == $product['brand_id']) ? 'selected' : '' ?>>
                                            <?= $brand['name'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div>
                                <label>Danh mục</label>
                                <select name="category_id">
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id'] ?>"
                                            <?= ($cat['id'] == $product['category_id']) ? 'selected' : '' ?>>
                                            <?= $cat['name'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3>3. Thông số kỹ thuật</h3>
                        <div class="row-2">
                            <input type="text" name="cpu" placeholder="CPU" value="<?= $variant['cpu'] ?? '' ?>">
                            <input type="text" name="ram" placeholder="RAM" value="<?= $variant['ram'] ?? '' ?>">
                        </div>

                        <div class="row-2">
                            <input type="text" name="ssd" placeholder="SSD" value="<?= $variant['ssd'] ?? '' ?>">
                            <input type="text" name="gpu" placeholder="GPU" value="<?= $variant['gpu'] ?? '' ?>">
                        </div>

                        <div class="row-2">
                            <input type="text" name="screen" placeholder="Màn hình" value="<?= $variant['screen'] ?? '' ?>">
                            <input type="text" name="pin" placeholder="Pin" value="<?= $variant['pin'] ?? '' ?>">
                        </div>

                        <div class="row-2">
                            <input type="text" name="he_dieu_hanh" placeholder="Hệ điều hành" value="<?= $variant['he_dieu_hanh'] ?? '' ?>">
                            <input type="text" name="kich_thuoc" placeholder="Kích thước" value="<?= $variant['kich_thuoc'] ?? '' ?>">
                        </div>

                    </div>

                    <div class="card">
                        <h4>4. Mô tả sản phẩm</h4>
                        <textarea name="description" rows="6"><?= $product['description'] ?></textarea>
                    </div>

                </div>

                <div class="right-column">
                    <div class="card">
                        <h3>2. Giá & Kho hàng</h3>
                        <div class="row-2">
                            <label>Giá gốc (VNĐ)</label>
                            <input type="number" name="price" value="<?= $product['price'] ?>">

                            <label>Giá khuyến mãi (VNĐ)</label>
                            <input type="number" name="sale_price" value="<?= $product['sale_price'] ?>">

                            <label>Số lượng</label>
                            <input type="number" name="stock" value="<?= $product['stock'] ?>">
                        </div>

                        <div class="card">
                            <h3>5. Hình ảnh</h3>
                            <?php if ($image): ?>
                                <img src="../../assets/images/products/<?= $image['image_url'] ?>" width="150">
                            <?php else: ?>
                                <p>Chưa có ảnh</p>
                            <?php endif; ?>

                            <div class="upload-box" id="uploadBox">
                                <input type="file" name="image" id="imageInput" accept="image/*" hidden>
                                <div class="upload-content" id="uploadContent">
                                    <i class="fa fa-cloud-upload-alt upload-icon"></i>
                                    <p class="upload-text">Kéo thả ảnh vào đây</p>
                                    <span>Hỗ trợ JPG, PNG, WEBP (Max 5MB)</span>
                                </div>
                                <div style="position:relative; display:inline-block;">

                                    <img
                                        id="previewImage"
                                        style="width:150px; border:1px solid #ccc; padding:5px;
                                    <?= empty($image['image_url']) ? 'display:none;' : '' ?>">

                                    <span id="removeImage"
                                        class="removeImage"
                                        style="<?= empty($image['image_url']) ? 'display:none;' : '' ?>">
                                        &times;
                                    </span>

                                </div>

                            </div>

                        </div>

                        <div class="card">
                            <h3>6. Đánh giá sản phẩm</h3>
                            <div class="rating-box">
                                <label>Đánh giá ★ (rating)</label>

                                <div class="rating-input">
                                    <input
                                        type="number"
                                        name="rating"
                                        min="0"
                                        max="5"
                                        step="0.1"
                                        value="<?= $product['rating'] ?? 0 ?>">

                                    <span class="star">★</span>
                                    <span id="ratingValue"><?= $product['rating'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card status-card">
                            <h3>7. Trạng thái</h3>
                            <label class="status-option">
                                <input type="radio" name="status" value="1"
                                    <?= $product['status'] == 1 ? 'checked' : '' ?>>
                                <span>Công khai <small>(Hiển thị ngay)</small></span>
                            </label>

                            <label class="status-option">
                                <input type="radio" name="status" value="0"
                                    <?= $product['status'] == 0 ? 'checked' : '' ?>>
                                <span>Ẩn sản phẩm</span>
                            </label>
                        </div>

                    </div>
            </form>

        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div id="toast" class="toast-success">
            ✔ Cập nhật sản phẩm thành công
        </div>
    <?php endif; ?>

    <script>
        setTimeout(() => {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.remove();
            }


            if (window.location.search.includes('success')) {
                window.history.replaceState({}, document.title, window.location.pathname + '?id=' + new URLSearchParams(window.location.search).get('id'));
            }
        }, 3500);

        const imageInput = document.getElementById("imageInput");
        const previewImage = document.getElementById("previewImage");
        const removeImage = document.getElementById("removeImage");
        const uploadBox = document.getElementById("uploadBox");
        const uploadContent = document.getElementById("uploadContent");

        const defaultImage = "<?= BASE_URL ?>/assets/images/products/<?= $image['image_url'] ?? 'no-image.png' ?>";

        const ratingInput = document.querySelector('input[name="rating"]');
        const ratingValue = document.getElementById("ratingValue");

        if (ratingInput) {
            ratingInput.addEventListener("input", function() {
                ratingValue.innerText = this.value || 0;
            });
        }

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

</body>

</html>