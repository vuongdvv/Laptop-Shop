<?php
session_start();

require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

requireAdminAccess($conn);


if (!isset($_GET['id']) || !isset($_GET['type'])) {
    header("Location: brand_category.php");
    exit;
}

$id = (int) $_GET['id'];
$type = $_GET['type'];

if ($type === "brand") {


    $check = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE brand_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if ($result['total'] > 0) {
        $_SESSION['error'] = "Không thể xoá thương hiệu vì đang có sản phẩm.";
    } else {
        $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Xoá thương hiệu thành công.";
        } else {
            $_SESSION['error'] = "Xoá thất bại.";
        }
    }
} elseif ($type === "category") {

    $check = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if ($result['total'] > 0) {
        $_SESSION['error'] = "Không thể xoá danh mục vì đang có sản phẩm.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Xoá danh mục thành công.";
        } else {
            $_SESSION['error'] = "Xoá thất bại.";
        }
    }
}

header("Location: brand_category.php");
exit;
