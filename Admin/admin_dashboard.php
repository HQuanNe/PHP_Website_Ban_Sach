<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../Connect/connect.php';

$page = $_GET['page'] ?? 'product_list';

if ($page === 'order_list' && isset($_GET['ajax_details'])) {
    $order_id = intval($_GET['ajax_details']);
    $sql = "SELECT od.*, p.Name as product_name, p.Image_URL as Image 
            FROM order_detail AS od 
            JOIN products AS p ON od.Product_ID = p.ID 
            WHERE od.Order_ID = $order_id";
    $details = $conn->query($sql);
    $data = [];
    if ($details) {
        while ($row = $details->fetch_assoc()) {
            $data[] = $row;
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle titles and breadcrumbs based on the page
$pageTitle = 'Dashboard';
$breadcrumb = '<span class="active">Trang chủ</span>';
$icon = 'fa-gauge-high';

if ($page === 'add_product') {
    $pageTitle = 'Thêm sản phẩm mới';
    $icon = 'fa-plus';
    $breadcrumb = '<span>Sản phẩm</span> <i class="fa-solid fa-chevron-right"></i> <span class="active">Thêm mới</span>';
} elseif ($page === 'product_list') {
    $pageTitle = 'Danh sách sản phẩm';
    $icon = 'fa-list';
    $breadcrumb = '<span>Sản phẩm</span> <i class="fa-solid fa-chevron-right"></i> <span class="active">Danh sách</span>';
} elseif ($page === 'order_list') {
    $pageTitle = 'Quản lý Đơn hàng';
    $icon = 'fa-cart-shopping';
    $breadcrumb = '<span>Đơn hàng</span> <i class="fa-solid fa-chevron-right"></i> <span class="active">Danh sách</span>';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - Dream Book Admin</title>
    <link rel="icon" href="../Resource/Image/Logo/LogoNoText.webp">
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../Resource/FontAwesome/fontawesome-free-7.2.0-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($page === 'order_list'): ?>
    <style>
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: left; }
        .table th { background-color: #f8f9fa; font-weight: 600; color: #495057; }
        .table tbody tr:hover { background-color: #f1f3f5; }
        
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-success { background-color: #28a745; color: #fff; }
        .badge-danger { background-color: #dc3545; color: #fff; }

        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-info { background: #17a2b8; color: #fff; }
        .btn-info:hover { background: #138496; }
        
        /* Modal Chi tiết đơn hàng */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px; overflow: hidden; }
        .modal-header { padding: 15px 20px; background: #343a40; color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { margin: 0; font-size: 18px; }
        .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: #fff; }
        .modal-body { padding: 20px; }
        
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .detail-item strong { display: inline-block; width: 140px; color: #6c757d; }
        
        .status-form { display: flex; gap: 10px; align-items: center; }
        .status-select { padding: 8px; border: 1px solid #ced4da; border-radius: 4px; }
        .btn-update { background: #007bff; color: #fff; }
        .btn-update:hover { background: #0069d9; }
    </style>
    <?php endif; ?>
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-book-open-reader"></i>
            <span>Dream Book</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">QUẢN LÝ</div>
            <ul>
                <li>
                    <a href="admin_dashboard.php?page=dashboard" class="nav-item <?= $page == 'dashboard' ? 'active' : '' ?>">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= in_array($page, ['add_product', 'product_list']) ? 'active' : '' ?>">
                    <a href="#" class="nav-item <?= in_array($page, ['add_product', 'product_list']) ? 'active' : '' ?>">
                        <i class="fa-solid fa-box-open"></i>
                        <span>Sản phẩm</span>
                    </a>
                    <ul class="nav-submenu" style="<?= in_array($page, ['add_product', 'product_list']) ? 'display:block;' : '' ?>">
                        <li><a href="admin_dashboard.php?page=add_product" class="<?= $page == 'add_product' ? 'active' : '' ?>"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a></li>
                        <li><a href="admin_dashboard.php?page=product_list" class="<?= $page == 'product_list' ? 'active' : '' ?>"><i class="fa-solid fa-list"></i> Danh sách</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#" class="nav-item">
                        <i class="fa-solid fa-users"></i>
                        <span>Người dùng</span>
                    </a>
                </li>
                <li>
                    <a href="admin_dashboard.php?page=order_list" class="nav-item <?= $page == 'order_list' ? 'active' : '' ?>">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>Đơn hàng</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="sidebar-logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Đăng xuất</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <h1 class="page-title"><i class="fa-solid <?= $icon ?>"></i> <?= $pageTitle ?></h1>
                <nav class="breadcrumb">
                    <span>Dashboard</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <?= $breadcrumb ?>
                </nav>
            </div>
            <div class="topbar-right">
                <?php if ($page === 'product_list'): ?>
                <a href="admin_dashboard.php?page=add_product" class="btn-save-edit" style="text-decoration:none;">
                    <i class="fa-solid fa-plus"></i> Thêm sản phẩm
                </a>
                <?php endif; ?>
                <div class="admin-user-info">
                    <i class="fa-solid fa-user-shield"></i>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
        </header>

        <!-- Nội dung động -->
        <div class="admin-content">
            <?php 
                $allowed_pages = ['add_product', 'product_list', 'order_list'];
                if (in_array($page, $allowed_pages)) {
                    include $page . '.php';
                } else {
                    echo '<div style="padding: 20px; font-size: 18px; color: #555;">Chào mừng đến với trang quản trị Dream Book! Chọn một danh mục bên trái để bắt đầu.</div>';
                }
            ?>
        </div><!-- /.admin-content -->
    </main>
</div><!-- /.admin-wrapper -->

</body>
</html>
