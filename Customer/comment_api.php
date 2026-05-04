<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../Connect/connect.php';

// Tự động tạo bảng Comment nếu chưa có
$createTableSql = "CREATE TABLE IF NOT EXISTS `Comment` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_product` int(11) NOT NULL,
  `ID_User` int(11) NOT NULL,
  `Rating` int(1) NOT NULL DEFAULT 5,
  `Comment_Detail` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
$conn->query($createTableSql);

// Cập nhật thêm cột Rating cho bảng cũ (chạy ngầm - chỉ khi chưa có)
$checkCol = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Comment' AND COLUMN_NAME = 'Rating'");
if ($checkCol && $checkCol->num_rows === 0) {
    $conn->query("ALTER TABLE `Comment` ADD COLUMN `Rating` INT(1) NOT NULL DEFAULT 5 AFTER `ID_User`");
}

header('Content-Type: application/json');

// Hàm kiểm tra quyền bình luận
function canUserComment($conn, $user_id, $product_id) {
    if (!$user_id || $user_id <= 0) return false;
    $sql = "SELECT o.ID FROM orders o 
            JOIN order_detail od ON o.ID = od.Order_ID 
            WHERE o.User_ID = $user_id 
            AND od.Product_ID = $product_id 
            AND o.Status = 'Hoàn thành' LIMIT 1";
    $result = $conn->query($sql);
    return ($result && $result->num_rows > 0);
}

// Xử lý thêm bình luận (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
        exit;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $user_id = intval($_SESSION['user_id']);
    $rating = intval($_POST['rating'] ?? 5);

    if ($product_id <= 0 || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }
    
    if ($rating < 1 || $rating > 5) $rating = 5;

    if (!canUserComment($conn, $user_id, $product_id)) {
        echo json_encode(['success' => false, 'message' => 'Bạn cần mua sản phẩm này và hoàn thành đơn hàng để đánh giá.']);
        exit;
    }

    $contentEscaped = $conn->real_escape_string($content);

    $sql = "INSERT INTO Comment (ID_product, ID_User, Rating, Comment_Detail, Created_at) 
            VALUES ($product_id, $user_id, $rating, '$contentEscaped', NOW())";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi DB: ' . $conn->error]);
    }
    exit;
}

// Xử lý lấy bình luận (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    $sql = "SELECT c.*, u.UserName 
            FROM Comment c 
            JOIN users u ON c.ID_User = u.ID 
            WHERE c.ID_product = $product_id 
            ORDER BY c.Created_at DESC";
    
    $result = $conn->query($sql);
    $comments = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $comments[] = $row;
        }
    }
    
    $can_comment = false;
    if (isset($_SESSION['user_id'])) {
        $can_comment = canUserComment($conn, intval($_SESSION['user_id']), $product_id);
    }
    
    echo json_encode([
        'comments' => $comments,
        'can_comment' => $can_comment
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
