<?php
/**
 * setup_vouchers.php — Tạo bảng vouchers + ALTER orders
 * Chạy 1 lần duy nhất. An toàn chạy lại (IF NOT EXISTS).
 */
include 'Connect/connect.php';

// Tạo bảng vouchers
$conn->query("CREATE TABLE IF NOT EXISTS `vouchers` (
    `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `Code` VARCHAR(50) NOT NULL,
    `Type` ENUM('percent','fixed') NOT NULL DEFAULT 'fixed',
    `Value` DECIMAL(10,0) NOT NULL DEFAULT 0,
    `Min_order` DECIMAL(10,0) NOT NULL DEFAULT 0,
    `Max_discount` DECIMAL(10,0) DEFAULT NULL,
    `Quantity` INT NOT NULL DEFAULT -1,
    `Used` INT NOT NULL DEFAULT 0,
    `Start_date` DATE NOT NULL,
    `End_date` DATE NOT NULL,
    `Is_banner` TINYINT NOT NULL DEFAULT 0,
    `Banner_title` VARCHAR(255) DEFAULT NULL,
    `Banner_subtitle` VARCHAR(255) DEFAULT NULL,
    `Status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `Created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `Code` (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

echo "✅ Bảng vouchers OK<br>";

// ALTER orders: thêm cột voucher
$cols = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='book_store' AND TABLE_NAME='orders' AND COLUMN_NAME='Voucher_code'");
if ($cols->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN `Voucher_code` VARCHAR(50) DEFAULT NULL");
    echo "✅ Thêm cột Voucher_code<br>";
} else {
    echo "⏭ Cột Voucher_code đã tồn tại<br>";
}

$cols2 = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='book_store' AND TABLE_NAME='orders' AND COLUMN_NAME='Discount_amount'");
if ($cols2->num_rows == 0) {
    $conn->query("ALTER TABLE orders ADD COLUMN `Discount_amount` DECIMAL(10,0) DEFAULT 0");
    echo "✅ Thêm cột Discount_amount<br>";
} else {
    echo "⏭ Cột Discount_amount đã tồn tại<br>";
}

// Insert voucher mẫu
$check = $conn->query("SELECT ID FROM vouchers LIMIT 1");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO vouchers (Code, Type, Value, Min_order, Max_discount, Quantity, Start_date, End_date, Is_banner, Banner_title, Banner_subtitle, Status) VALUES
        ('CHAOHE50', 'percent', 50, 100000, 200000, 100, '2026-05-01', '2026-08-31', 1, '🔥 ƯU ĐÃI CHÀO HÈ', 'Giảm 50% cho đơn từ 100K — Tối đa 200K', 'active'),
        ('GIAM30K', 'fixed', 30000, 50000, NULL, 200, '2026-05-01', '2026-12-31', 1, '🎁 GIẢM NGAY 30K', 'Áp dụng cho đơn hàng từ 50.000₫', 'active'),
        ('FREESHIP', 'fixed', 15000, 0, NULL, -1, '2026-01-01', '2026-12-31', 0, NULL, NULL, 'active')
    ");
    echo "✅ Thêm voucher mẫu<br>";
}

echo "<br><a href='index.php'>← Về trang chủ</a>";
