<?php
/**
 * search_api.php — API tìm kiếm sách (autocomplete)
 * Trả về JSON danh sách sách khớp với từ khoá.
 */
include '../Connect/connect.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$escaped = $conn->real_escape_string($q);
$sql = "SELECT p.ID, p.Name, p.Price, p.Image_URL, p.TacGia, c.Decription as CatName
        FROM products p
        LEFT JOIN category c ON p.Category_ID = c.ID
        WHERE p.Name LIKE '%$escaped%' OR p.TacGia LIKE '%$escaped%'
        ORDER BY p.Name ASC
        LIMIT 8";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id'     => $row['ID'],
            'name'   => $row['Name'],
            'price'  => number_format($row['Price'], 0, ',', '.') . ' ₫',
            'image'  => $row['Image_URL'] ?? '',
            'author' => $row['TacGia'] ?? '',
            'cat'    => $row['CatName'] ?? ''
        ];
    }
}

echo json_encode($data);
