<?php
include 'Connect/connect.php';
$sql = "CREATE TABLE IF NOT EXISTS `Comment` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_product` int(11) NOT NULL,
  `ID_User` int(11) NOT NULL,
  `Comment_Detail` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql) === TRUE) {
    echo "Table Comment created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
