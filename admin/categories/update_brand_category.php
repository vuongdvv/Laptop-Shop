<?php
session_start();

require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

header('Content-Type: application/json');

requireAdminAccess($conn);

// ===== UPDATE BRAND =====
if (isset($_POST['type']) && $_POST['type'] === 'brand') {

    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name'] ?? '');

    if (!$id || !$name) {
        echo json_encode(["success" => false, "message" => "Missing data"]);
        exit;
    }

    $logoName = null;

    if (!empty($_FILES['logo']['name'])) {

        $targetDir = dirname(__DIR__, 2) . "/assets/images/brands/";

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $originalName = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_FILENAME));
        $originalName = preg_replace('/[^a-z0-9_-]/', '', $originalName);

        $logoName = $originalName . "_" . time() . "." . $ext;
        $targetFile = $targetDir . $logoName;

        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
            echo json_encode([
                "success" => false,
                "message" => "Upload failed"
            ]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE brands SET name = ?, logo_image = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $logoName, $id);
    } else {

        $stmt = $conn->prepare("UPDATE brands SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);

        // 🔥 LẤY LOGO CŨ
        $result = $conn->query("SELECT logo_image FROM brands WHERE id = $id");
        $row = $result->fetch_assoc();
        $logoName = $row['logo_image'];
    }

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "logo" => $logoName
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => $stmt->error
        ]);
    }

    exit;
}

// ===== UPDATE CATEGORY =====
$id = $_POST['id'] ?? 0;
$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');

if (!$id || !$name) {
    echo json_encode([
        "success" => false,
        "message" => "Missing data"
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
$stmt->bind_param("ssi", $name, $slug, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode([
        "success" => false,
        "message" => $stmt->error
    ]);
}
