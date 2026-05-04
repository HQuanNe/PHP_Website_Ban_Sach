<?php
/**
 * logout.php — Đăng xuất
 * Xóa session server-side, redirect kèm flag để JS xóa sessionStorage.
 */
session_start();
session_unset();
session_destroy();

// Chuyển hướng về trang chủ kèm tham số để JS biết cần xóa giỏ hàng ở Client
header("Location: index.php?clear_cart=1");
exit;
?>
