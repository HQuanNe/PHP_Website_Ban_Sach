<?php
include 'Connect/connect.php';
$res = $conn->query("SHOW COLUMNS FROM orders LIKE 'Status'");
$row = $res->fetch_assoc();
echo "Status Type: " . $row['Type'];
?>
