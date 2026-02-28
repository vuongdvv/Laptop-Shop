<?php
session_start();
require_once dirname(__DIR__, 2) . "/config/config.php";
require_once dirname(__DIR__, 2) . "/config/database.php";
require_once dirname(__DIR__, 2) . "/includes/functions.php";

header('Content-Type: application/json');

requireAdminAccess($conn);

$id = $_POST['id'] ?? 0;
$status = $_POST['status'] ?? 0;
$role = $_POST['role'] ?? 'user';

$stmt = $conn->prepare("UPDATE users SET status=?, role=? WHERE id=?");
$stmt->bind_param("isi", $status, $role, $id);

if ($stmt->execute()) {
    // Kiểm tra nếu update role của admin hiện tại
    if ($id == $_SESSION['admin_id']) {
        // Nếu role không phải 'admin' hoặc status=0 thì logout
        if ($role !== 'admin' || $status == 0) {
            session_unset();
            session_destroy();
            echo json_encode(["success" => true, "logout" => true]);
            exit;
        }
    }
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
