<?php
session_start();
require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

header('Content-Type: application/json');

requireAdminAccess($conn);

$name = $_POST['name'] ?? '';

if (!$name) {
    echo json_encode(["success" => false, "message" => "Thiếu tên"]);
    exit;
}

$logoName = null;

// xử lý upload ảnh
if (!empty($_FILES['logo']['name'])) {
    $targetDir = "../../assets/images/brands/";

    $logoName = time() . "_" . basename($_FILES['logo']['name']);
    $targetFile = $targetDir . $logoName;

    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
        echo json_encode(["success" => false, "message" => "Upload lỗi"]);
        exit;
    }
}

// insert DB
$stmt = $conn->prepare("INSERT INTO brands (name, logo_image) VALUES (?, ?)");
$stmt->bind_param("ss", $name, $logoName);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
