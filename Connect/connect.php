<?php
$servername = "localhost";
$username = "root";
$password = ""; // Mặc định của XAMPP là rỗng
$dbname = "book_store";

// Tạo kết nối bằng MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối đến database thất bại: " . $conn->connect_error);
}

// Set charset utf8mb4 để hỗ trợ hiển thị tiếng Việt (có dấu) chính xác
$conn->set_charset("utf8mb4");

// echo "Kết nối thành công"; // Bạn có thể mở comment này để test
?>
