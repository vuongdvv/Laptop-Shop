<?php
session_start();
require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);

// 2️⃣ Check method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: product.php");
    exit();
}

// 3️⃣ CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "CSRF token không hợp lệ!";
    header("Location: product.php");
    exit();
}

// 4️⃣ Validate ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $_SESSION['error'] = "ID không hợp lệ!";
    header("Location: product.php");
    exit();
}

$product_id = (int)$_POST['id'];

// 5️⃣ Lấy danh sách ảnh trước khi xóa
$imgStmt = $conn->prepare("
    SELECT image_url FROM product_images 
    WHERE product_id = ?
");
$imgStmt->bind_param("i", $product_id);
$imgStmt->execute();
$imgResult = $imgStmt->get_result();

$images = [];
while ($row = $imgResult->fetch_assoc()) {
    $images[] = $row['image_url'];
}

// 6️⃣ Xóa product (CASCADE sẽ tự xóa bảng con)
$deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$deleteStmt->bind_param("i", $product_id);
$deleteStmt->execute();

if ($deleteStmt->affected_rows > 0) {

    // 7️⃣ Xóa file ảnh vật lý
    foreach ($images as $img) {
        $filePath = dirname(__DIR__, 2) . "/assets/images/products/" . $img;

        if (!empty($img) && file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $_SESSION['success'] = "Xóa sản phẩm thành công!";
} else {
    $_SESSION['error'] = "Không thể xóa sản phẩm!";
}

header("Location: product.php");
exit();
