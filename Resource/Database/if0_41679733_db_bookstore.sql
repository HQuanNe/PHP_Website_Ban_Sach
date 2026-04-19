-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: sql100.infinityfree.com
-- Thời gian đã tạo: Th4 16, 2026 lúc 09:55 PM
-- Phiên bản máy phục vụ: 11.4.10-MariaDB
-- Phiên bản PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `if0_41679733_db_bookstore`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `User_ID` int(10) UNSIGNED NOT NULL,
  `Product_ID` int(10) UNSIGNED NOT NULL,
  `Quantity` int(10) UNSIGNED NOT NULL,
  `Toltal_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `category`
--

CREATE TABLE `category` (
  `ID` int(11) NOT NULL,
  `Decription` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `category`
--

INSERT INTO `category` (`ID`, `Decription`) VALUES
(1, 'Kỹ năng'),
(2, 'Văn học'),
(3, 'Lịch sử'),
(4, 'Cổ tích');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `ID` int(10) UNSIGNED NOT NULL,
  `User_ID` int(10) UNSIGNED NOT NULL,
  `Order_date` datetime DEFAULT current_timestamp(),
  `Total_amount` decimal(10,2) DEFAULT NULL,
  `Shipping_address` varchar(255) DEFAULT NULL,
  `Status` enum('Chờ xử lý','Hoàn thành','Đã huỷ') DEFAULT 'Chờ xử lý'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_detail`
--

CREATE TABLE `order_detail` (
  `Order_ID` int(10) UNSIGNED NOT NULL,
  `Product_ID` int(10) UNSIGNED NOT NULL,
  `Quantity` int(10) UNSIGNED NOT NULL,
  `Price_at_order` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `ID` int(10) UNSIGNED NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Category_ID` int(11) NOT NULL,
  `Quantity` int(10) UNSIGNED NOT NULL,
  `Price` decimal(10,0) DEFAULT NULL,
  `TacGia` text DEFAULT NULL,
  `NhaXuatBan` text DEFAULT NULL,
  `NamXuatBan` year(4) DEFAULT NULL,
  `SoTrang` int(11) DEFAULT NULL,
  `Image_URL` varchar(255) DEFAULT NULL,
  `Update_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`ID`, `Name`, `Category_ID`, `Quantity`, `Price`, `TacGia`, `NhaXuatBan`, `NamXuatBan`, `SoTrang`, `Image_URL`, `Update_at`) VALUES
(6, 'test', 1, 999, '990000', 'Hồng Quân', 'Hồng Quân', 2026, 111, 'Resource/Image/BookImage/KyNang/book_69e17d941cb9a.png', '2026-04-17 07:23:48'),
(7, 'Chí Phèo', 2, 999, '80000', 'Nam Cao', 'Đời mới', 1941, 196, 'Resource/Image/BookImage/VanHoc/book_69e18bf74a306.jpg', '2026-04-16 18:25:11'),
(8, 'Nhà Giả Kim', 2, 999, '79', 'Paulo Coelho', 'Hội Nhà Văn (Nhã Nam)', 2020, 227, '', '2026-04-16 18:28:03'),
(9, 'Vợ Nhặt', 2, 999, '70000', 'Kim Lân', 'Con chó xấu xí', 1962, 207, 'Resource/Image/BookImage/VanHoc/book_69e18caab6d78.jpg', '2026-04-16 18:28:11'),
(10, 'Hoàng Tử Bé', 2, 890, '40', 'Antoine de Saint-Exupéry', 'Kim Đồng', 2023, 104, 'Resource/Image/BookImage/VanHoc/book_69e18d2868cfa.png', '2026-04-16 18:30:16'),
(11, 'Tắt Đèn', 2, 999, '60000', 'Ngô Tất Tố', 'Nhà in Tân Dân (Hà Nội)', 1939, 208, 'Resource/Image/BookImage/VanHoc/book_69e18d4e6a398.jpg', '2026-04-16 18:30:54'),
(12, 'Suối Nguồn (The Fountainhead)', 2, 999, '380', 'Ayn Rand', 'Trẻ', 2023, 1200, '', '2026-04-16 18:31:18'),
(13, 'Số Đỏ', 2, 999, '100000', 'Vũ Trọng Phụng', 'Nhà xuất bản Đời Nay (Hà Nội)', 1938, 280, 'Resource/Image/BookImage/VanHoc/book_69e18e09bc4a2.jpg', '2026-04-16 18:34:02'),
(14, 'Hai Số Phận (Kane and Abel)', 2, 999, '195', 'Jeffrey Archer', 'Văn Học (Huy Hoàng)', 2025, 768, 'Resource/Image/BookImage/VanHoc/book_69e18e6a8d44d.jpg', '2026-04-16 18:35:38'),
(15, 'Rừng Na Uy (Norwegian Wood)', 2, 999, '185', 'Haruki Murakami', 'Hội Nhà Văn (Nhã Nam)', 2025, 562, 'Resource/Image/BookImage/VanHoc/book_69e18eba569f8.jpg', '2026-04-16 18:36:58'),
(16, 'ÔNG LÃO ĐÁNH CÁ VÀ CON CÁ VÀNG', 4, 9999, '60000', 'Cộng Đồng', 'NHÀ XUẤT BẢN KIM ĐỒNG', 2000, 0, 'Resource/Image/BookImage/CoTich/book_69e18ec679f8d.jpg', '2026-04-16 18:37:10'),
(17, 'Giết Con Chim Nhại (To Kill a Mockingbird)', 2, 0, '115', 'Harper Lee', 'Văn Học (Nhã Nam)', 2020, 456, 'Resource/Image/BookImage/VanHoc/book_69e18f3f46ead.jpg', '2026-04-16 18:39:11'),
(18, 'TÍCH CHU', 4, 9999, '60000', 'Cộng Đồng', 'NHÀ XUẤT BẢN KIM ĐỒNG', 2000, 200, 'Resource/Image/BookImage/CoTich/book_69e18f6342dc1.jpg', '2026-04-16 18:39:47'),
(19, 'Truyện Kiều', 2, 999, '190000', 'Nguyễn Du', 'NXB Văn học', 0000, 184, 'Resource/Image/BookImage/VanHoc/book_69e18f63eef54.webp', '2026-04-16 18:39:48'),
(20, 'Chiến Tranh Và Hòa Bình (War and Peace)', 2, 999, '600', 'Lev Tolstoy', 'Văn Học', 2022, 2500, '', '2026-04-16 18:41:03'),
(21, 'Lược Sử Thời Gian (A Brief History of Time)', 3, 999, '115', 'Stephen Hawking', 'Trẻ', 2020, 286, 'Resource/Image/BookImage/LichSu/book_69e1903718012.jpg', '2026-04-16 18:43:19'),
(22, 'Đất Rừng Phương Nam', 2, 999, '120000', 'Đoàn Giỏi', 'NXB Kim Đồng', 1957, 340, 'Resource/Image/BookImage/VanHoc/book_69e190371ae17.jpg', '2026-04-16 18:43:19'),
(23, 'SỌ DỪA', 4, 9999, '60000', 'Cộng Đồng', 'NHÀ XUẤT BẢN KIM ĐỒNG', 2000, 200, 'Resource/Image/BookImage/CoTich/book_69e19050048af.jpg', '2026-04-16 18:43:44'),
(24, 'Lược Sử Loài Người (Sapiens)', 3, 999, '320', 'Yuval Noah Harari', 'Thế Giới (omega Plus)', 2021, 566, 'Resource/Image/BookImage/LichSu/book_69e1909584df5.jpg', '2026-04-16 18:44:53'),
(25, 'THÁNH GIÓNG', 4, 9999, '60000', 'Cộng Đồng', 'NHÀ XUẤT BẢN KIM ĐỒNG', 2000, 200, 'Resource/Image/BookImage/CoTich/book_69e190af42533.jpg', '2026-04-16 18:45:19'),
(26, 'Đắc Nhân Tâm (How to Win Friends and Influence People)', 1, 999, '86', 'Dale Carnegie', 'Tổng hợp TP.HCM (First News)', 2021, 320, 'Resource/Image/BookImage/KyNang/book_69e190e4977b3.jpg', '2026-04-16 18:46:12'),
(27, 'TẤM CÁM', 4, 9999, '60000', 'Cộng Đồng', 'NHÀ XUẤT BẢN KIM ĐỒNG', 2000, 200, 'Resource/Image/BookImage/CoTich/book_69e190f880074.jpg', '2026-04-16 18:46:32'),
(28, 'Làm Đĩ', 2, 999, '80000', 'Vũ Trọng Phụng', 'Nhà xuất bản Đời Nay (Hà Nội)', 1936, 264, 'Resource/Image/BookImage/VanHoc/book_69e19115cb8b4.jpg', '2026-04-16 18:47:02'),
(29, 'Dế Mèn phiêu lưu ký', 2, 999, '200000', 'Tô Hoài', 'NXB Kim Đồng', 1941, 144, 'Resource/Image/BookImage/VanHoc/book_69e191bd8c122.webp', '2026-04-16 18:49:49'),
(30, 'BỈ Vỏ', 2, 999, '300000', 'Nguyên Hồng', 'Nhà xuất bản Tân Dân (Hà Nội)', 1938, 270, 'Resource/Image/BookImage/VanHoc/book_69e19272c6459.jpg', '2026-04-16 18:52:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `ID` int(10) UNSIGNED NOT NULL,
  `UserName` varchar(100) NOT NULL,
  `Passwd` varchar(200) NOT NULL,
  `Role` enum('admin','customer') DEFAULT 'customer',
  `Phone` varchar(10) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`ID`, `UserName`, `Passwd`, `Role`, `Phone`, `Address`, `Create_at`) VALUES
(1, 'anh_A', '123', 'customer', '123456789', 'Hà Tây', '2026-03-16 03:17:57'),
(2, 'anh_B', '123', 'customer', '123456789', 'Hà Tây', '2026-03-16 03:17:57'),
(3, 'anh_C', '123', 'customer', '123456789', 'Hà Tây', '2026-03-16 03:17:57'),
(4, 'admin', 'admin', 'admin', '0382294559', 'Hanoi', '2026-03-31 05:10:38'),
(5, 'test1', '$2y$10$ybh08cDDEAorfWq7E9gLWOSMqAHgl9Ex54Chvcj8x40Rk5Z2Xfm76', 'customer', '0123456789', NULL, '2026-04-17 00:21:28');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`User_ID`,`Product_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Chỉ mục cho bảng `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`ID`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `User_ID` (`User_ID`);

--
-- Chỉ mục cho bảng `order_detail`
--
ALTER TABLE `order_detail`
  ADD PRIMARY KEY (`Order_ID`,`Product_ID`),
  ADD UNIQUE KEY `Quantity` (`Quantity`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Name` (`Name`),
  ADD KEY `fk_Catgory_ID` (`Category_ID`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `UserName` (`UserName`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `category`
--
ALTER TABLE `category`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `products` (`ID`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `OrderID_FK_UserID` FOREIGN KEY (`User_ID`) REFERENCES `users` (`ID`);

--
-- Các ràng buộc cho bảng `order_detail`
--
ALTER TABLE `order_detail`
  ADD CONSTRAINT `order_detail_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`ID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_detail_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `products` (`ID`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_Catgory_ID` FOREIGN KEY (`Category_ID`) REFERENCES `category` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
