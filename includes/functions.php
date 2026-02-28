<?php

/*Kiểm tra người dùng đã đăng nhập chưa*/
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/*Lấy tên người dùng đang đăng nhập*/
function getUserName()
{
    return $_SESSION['user_name'] ?? '';
}

/*Chuyển hướng nếu chưa đăng nhập*/
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
}

/*Đăng xuất*/
function logout()
{
    session_destroy();
    header("Location: " . BASE_URL);
    exit;
}

/**
 * Kiểm tra quyền admin (cho trang admin)
 * Nếu admin là user được set role='admin', kiểm tra lại role trong DB
 * Nếu role đã bị thay đổi thành user hoặc status=0 thì tự động logout
 */
function requireAdminAccess($conn)
{
    if (!isset($_SESSION['admin_id'])) {
        header("Location: " . BASE_URL . "/admin/admin_auth/login.php");
        exit;
    }


    if (isset($_SESSION['admin_source']) && $_SESSION['admin_source'] === 'users_table') {
        $stmt = $conn->prepare("
            SELECT role, status 
            FROM users 
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();


        if (!$user || $user['role'] !== 'admin' || $user['status'] != 1) {
            session_unset();
            session_destroy();
            header("Location: " . BASE_URL . "/admin/admin_auth/login.php?error=unauthorized");
            exit;
        }
    }
}


/*Escape dữ liệu (chống XSS)*/
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/*Lấy danh sách danh mục cha*/
function getCategories($conn)
{
    $sql = "SELECT id, name, slug FROM categories WHERE parent_id IS NULL";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/*Lấy danh mục theo slug*/
function getCategoryBySlug($conn, $slug)
{
    $stmt = $conn->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}


/*Lấy danh sách laptop mới nhất*/
function getLatestProducts($conn, $limit = 8)
{
    $stmt = $conn->prepare(
        "SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/*Lấy laptop theo danh mục*/
function getProductsByCategory($conn, $category_id)
{
    $stmt = $conn->prepare(
        "SELECT * FROM products WHERE category_id = ? AND status = 1"
    );
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/*Lấy chi tiết laptop*/
function getProductById($conn, $id)
{
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}



/* Tổng số sản phẩm trong giỏ hàng*/
function cartCount()
{
    if (!isset($_SESSION['cart'])) return 0;
    return array_sum($_SESSION['cart']);
}

/*Tổng tiền giỏ hàng*/
function cartTotal($conn)
{
    if (empty($_SESSION['cart'])) return 0;

    $total = 0;
    foreach ($_SESSION['cart'] as $product_id => $qty) {
        $product = getProductById($conn, $product_id);
        if ($product) {
            $total += $product['price'] * $qty;
        }
    }
    return $total;
}

/*Format tiền VND*/
function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . ' ₫';
}
