<?php
// config/database.php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "laptopshop";

$conn = new mysqli($host, $user, $pass, $db);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}


$conn->set_charset("utf8mb4");
