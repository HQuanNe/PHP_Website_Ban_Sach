<?php
/**
 * product_list.php — Quản lý danh sách sản phẩm (Admin)
 * -------------------------------------------------------
 * Chức năng:
 *   1. Hiển thị tất cả sản phẩm phân theo danh mục.
 *   2. Bấm vào sản phẩm → mở form chỉnh sửa toàn bộ thông số (trừ mã ID).
 *   3. Xóa sản phẩm với hộp thoại cảnh báo xác nhận trước khi xóa.
 *
 * Luồng xử lý POST:
 *   - action=edit  + id → UPDATE bảng products
 *   - action=delete + id → DELETE sản phẩm (kèm xóa file ảnh nếu có)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../Connect/connect.php';

$success = '';
$error   = '';

/* ══════════════════════════════════════════════════════════
   XỬ LÝ POST — EDIT / DELETE
   ══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pid    = intval($_POST['id'] ?? 0);

    /* ── XÓA SẢN PHẨM ─────────────────────────────────────
     * Trước khi xóa DB, cố xóa file ảnh trên server nếu có.
     * Dù xóa ảnh thất bại vẫn tiếp tục xóa record DB.
     */
    if ($action === 'delete' && $pid > 0) {
        // Lấy đường dẫn ảnh để xóa file
        $res = $conn->query("SELECT Image_URL FROM products WHERE ID = $pid");
        if ($res && $row = $res->fetch_assoc()) {
            $imgPath = '../' . $row['Image_URL'];
            if (!empty($row['Image_URL']) && file_exists($imgPath)) {
                unlink($imgPath); // Xóa file vật lý
            }
        }
        if ($conn->query("DELETE FROM products WHERE ID = $pid")) {
            $success = 'Đã xóa sản phẩm thành công.';
        } else {
            $error = 'Lỗi khi xóa: ' . $conn->error;
        }
    }

    /* ── CHỈNH SỬA SẢN PHẨM ───────────────────────────────
     * Cập nhật tất cả trường (trừ ID và Update_at sẽ tự cập nhật).
     * Nếu có ảnh mới upload → lưu file mới, xóa file cũ.
     * Nếu không upload ảnh mới → giữ nguyên Image_URL cũ.
     */
    if ($action === 'edit' && $pid > 0) {
        $name        = $conn->real_escape_string(trim($_POST['name']));
        $category_id = intval($_POST['category_id']);
        $author      = $conn->real_escape_string(trim($_POST['author'] ?? ''));
        $publisher   = $conn->real_escape_string(trim($_POST['publisher'] ?? ''));
        $year        = intval($_POST['year'] ?? 0);
        $pages       = intval($_POST['pages'] ?? 0);
        $price       = intval($_POST['price'] ?? 0);
        $quantity    = intval($_POST['quantity'] ?? 0);
        $mota        = $conn->real_escape_string(trim($_POST['mota'] ?? ''));

        // Lấy image_url cũ để giữ lại nếu không upload ảnh mới
        $old = $conn->query("SELECT Image_URL FROM products WHERE ID = $pid");
        $old_img = ($old && $r = $old->fetch_assoc()) ? $r['Image_URL'] : '';
        $image_url = $old_img;

        // Xử lý ảnh mới nếu có upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed   = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $file_type = $_FILES['image']['type'];

            if (!in_array($file_type, $allowed)) {
                $error = 'Chỉ chấp nhận ảnh JPG, PNG, WEBP, GIF.';
            } else {
                // Xác định folder theo danh mục mới
                $cat_res = $conn->query("SELECT Decription FROM category WHERE ID = $category_id");
                $cat_row = $cat_res ? $cat_res->fetch_assoc() : null;
                $cat_map = [
                    'Kỹ năng' => 'KyNang',
                    'Văn học' => 'VanHoc',
                    'Lịch sử' => 'LichSu',
                    'Cổ tích'  => 'CoTich',
                ];
                $cat_folder = $cat_map[$cat_row['Decription'] ?? ''] ?? preg_replace('/[^a-zA-Z0-9]/', '', $cat_row['Decription'] ?? 'Other') ?: 'Other';

                $ext        = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename   = uniqid('book_') . '.' . $ext;
                $upload_dir = "../Resource/Image/BookImage/$cat_folder/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                    // Xóa ảnh cũ nếu tồn tại
                    if (!empty($old_img) && file_exists('../' . $old_img)) {
                        unlink('../' . $old_img);
                    }
                    $image_url = "Resource/Image/BookImage/$cat_folder/$filename";
                } else {
                    $error = 'Lỗi khi upload ảnh mới.';
                }
            }
        }

        if (empty($error)) {
            if (empty($name)) {
                $error = 'Tên sách không được để trống.';
            } elseif ($price <= 0) {
                $error = 'Giá phải lớn hơn 0.';
            } else {
                $sql = "UPDATE products SET
                            Name        = '$name',
                            Category_ID = $category_id,
                            TacGia      = '$author',
                            NhaXuatBan  = '$publisher',
                            NamXuatBan  = $year,
                            SoTrang     = $pages,
                            Price       = $price,
                            Quantity    = $quantity,
                            MoTa        = '$mota',
                            Image_URL   = '$image_url',
                            Update_at   = NOW()
                        WHERE ID = $pid";
                if ($conn->query($sql)) {
                    $success = 'Cập nhật sản phẩm thành công!';
                } else {
                    $error = 'Lỗi DB: ' . $conn->error;
                }
            }
        }
    }
}

/* ══════════════════════════════════════════════════════════
   QUERY DỮ LIỆU
   - Lấy toàn bộ danh mục
   - Lấy toàn bộ sản phẩm, JOIN category để có tên danh mục
   - Nhóm sản phẩm theo category_id trong PHP
   ══════════════════════════════════════════════════════════ */
$categories = $conn->query("SELECT * FROM category ORDER BY ID");
$cat_list   = [];
if ($categories) {
    while ($c = $categories->fetch_assoc()) {
        $cat_list[$c['ID']] = $c['Decription'];
    }
}

$products_raw = $conn->query("
    SELECT p.*, c.Decription AS CatName
    FROM products p
    LEFT JOIN category c ON p.Category_ID = c.ID
    ORDER BY p.Category_ID, p.Name
");

// Nhóm theo danh mục
$grouped = []; // [category_id => ['name'=>..., 'products'=>[...]]]
if ($products_raw) {
    while ($p = $products_raw->fetch_assoc()) {
        $cid = $p['Category_ID'] ?? 0;
        if (!isset($grouped[$cid])) {
            $grouped[$cid] = ['name' => $p['CatName'] ?? 'Không rõ', 'products' => []];
        }
        $grouped[$cid]['products'][] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách sản phẩm - Dream Book Admin</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../Resource/FontAwesome/fontawesome-free-7.2.0-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Bảng sản phẩm theo danh mục ── */

        /* Header mỗi nhóm danh mục: nền kem nhạt, icon badge */
        .cat-group-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: var(--admin-bg);
            border-radius: 10px;
            margin: 24px 0 10px;
            font-weight: 700;
            font-size: 15px;
            color: var(--admin-text);
        }
        .cat-group-header .cat-count {
            margin-left: auto;
            font-size: 12px;
            font-weight: 600;
            background: var(--admin-primary);
            color: #fff;
            padding: 2px 10px;
            border-radius: 20px;
        }

        /* Bảng danh sách sản phẩm */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--admin-card);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .product-table thead tr {
            background: var(--admin-primary);
            color: #fff;
        }
        .product-table th {
            padding: 12px 14px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .product-table tbody tr {
            border-bottom: 1px solid var(--admin-border);
            cursor: pointer;              /* toàn row có thể click để sửa */
            transition: background 0.15s;
        }
        .product-table tbody tr:hover {
            background: color-mix(in srgb, var(--admin-primary) 6%, transparent);
        }
        .product-table td {
            padding: 11px 14px;
            font-size: 13.5px;
            color: var(--admin-text);
            vertical-align: middle;
        }

        /* Thumb ảnh nhỏ trong bảng */
        .product-thumb {
            width: 48px;
            height: 58px;
            object-fit: cover;
            border-radius: 6px;
            background: var(--admin-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-thumb img {
            width: 100%; height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }
        .product-thumb .no-img {
            font-size: 22px;
            color: #ccc;
        }

        /* Cột nút hành động — không trigger click-row */
        .td-actions { width: 90px; text-align: center; }
        .btn-delete-row {
            padding: 5px 12px;
            border: none;
            background: #e74c3c;
            color: #fff;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-delete-row:hover { background: #c0392b; }

        /* Badge tồn kho */
        .badge-stock {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #e8f5e9;
            color: #2e7d32;
        }
        .badge-stock.out {
            background: #fce4e4;
            color: #c0392b;
        }

        /* ── MODAL CHỈNH SỬA ── */

        /* Backdrop mờ phủ toàn màn hình */
        .edit-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20,15,10,0.55);
            backdrop-filter: blur(5px);
            z-index: 9000;
            justify-content: center;
            align-items: flex-start;
            padding: 30px 20px;
            overflow-y: auto;
            animation: modalFadeIn 0.2s ease;
        }
        .edit-modal-backdrop.open { display: flex; }
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* Hộp nội dung modal */
        .edit-modal {
            background: var(--admin-card);
            border-radius: 18px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.28);
            width: 100%;
            max-width: 780px;
            margin: auto;
            animation: modalSlideUp 0.28s cubic-bezier(0.175,0.885,0.32,1.275);
        }
        @keyframes modalSlideUp {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .edit-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--admin-border);
        }
        .edit-modal-header h2 {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--admin-text);
        }
        .edit-modal-close {
            width: 34px; height: 34px;
            border: none;
            border-radius: 50%;
            background: var(--admin-bg);
            color: var(--admin-text-muted);
            font-size: 16px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .edit-modal-close:hover {
            background: #e74c3c;
            color: #fff;
            transform: rotate(90deg);
        }

        .edit-modal-body { padding: 22px 24px; }

        /* Layout 2 cột trong modal edit */
        .edit-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        .edit-form-grid .full-width { grid-column: 1 / -1; }

        .edit-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .edit-form-group label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--admin-text-muted);
        }
        .edit-form-group input,
        .edit-form-group select,
        .edit-form-group textarea {
            padding: 9px 12px;
            border: 1.5px solid var(--admin-border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--admin-bg);
            color: var(--admin-text);
            transition: border-color 0.2s;
            font-family: inherit;
        }
        .edit-form-group input:focus,
        .edit-form-group select:focus,
        .edit-form-group textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
        .edit-form-group textarea { resize: vertical; min-height: 90px; }

        /* Xem trước ảnh hiện tại + nút đổi ảnh mới */
        .img-preview-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 8px;
        }
        .img-preview-current {
            width: 80px; height: 100px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--admin-bg);
            border: 1.5px solid var(--admin-border);
        }
        .img-preview-current.no-img {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #ccc;
        }

        /* Footer nút lưu */
        .edit-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 16px 24px 22px;
            border-top: 1px solid var(--admin-border);
        }
        .btn-cancel-edit {
            padding: 10px 22px;
            border: 1.5px solid var(--admin-border);
            border-radius: 8px;
            background: transparent;
            color: var(--admin-text-muted);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cancel-edit:hover { background: var(--admin-bg); }
        .btn-save-edit {
            padding: 10px 28px;
            border: none;
            border-radius: 8px;
            background: var(--admin-primary);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
        }
        .btn-save-edit:hover { opacity: 0.85; }

        /* ── MODAL XÁC NHẬN XÓA ── */
        .confirm-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20,15,10,0.6);
            z-index: 9500;
            justify-content: center;
            align-items: center;
        }
        .confirm-modal-backdrop.open { display: flex; }
        .confirm-modal {
            background: var(--admin-card);
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 16px 48px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.25s ease;
        }
        .confirm-modal .warn-icon {
            font-size: 44px;
            color: #e74c3c;
            margin-bottom: 12px;
        }
        .confirm-modal h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--admin-text);
        }
        .confirm-modal p {
            font-size: 14px;
            color: var(--admin-text-muted);
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .confirm-modal p strong { color: var(--admin-text); }
        .confirm-actions { display: flex; gap: 12px; justify-content: center; }
        .btn-confirm-no {
            padding: 10px 24px;
            border: 1.5px solid var(--admin-border);
            border-radius: 8px;
            background: transparent;
            font-weight: 600;
            cursor: pointer;
            color: var(--admin-text-muted);
        }
        .btn-confirm-yes {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            background: #e74c3c;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: background 0.2s;
        }
        .btn-confirm-yes:hover { background: #c0392b; }
    </style>
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- ══ SIDEBAR ══════════════════════════════════════════════════════ -->
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-book-open-reader"></i>
            <span>Dream Book</span>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">QUẢN LÝ</div>
            <ul>
                <li>
                    <a href="#" class="nav-item">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="active">
                    <a href="#" class="nav-item active">
                        <i class="fa-solid fa-box-open"></i>
                        <span>Sản phẩm</span>
                    </a>
                    <ul class="nav-submenu">
                        <li><a href="add_product.php"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a></li>
                        <li><a href="product_list.php" class="active"><i class="fa-solid fa-list"></i> Danh sách</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#" class="nav-item">
                        <i class="fa-solid fa-users"></i>
                        <span>Người dùng</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="nav-item">
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

    <!-- ══ NỘI DUNG CHÍNH ════════════════════════════════════════════════ -->
    <main class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <h1 class="page-title"><i class="fa-solid fa-list"></i> Danh sách sản phẩm</h1>
                <nav class="breadcrumb">
                    <span>Dashboard</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Sản phẩm</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span class="active">Danh sách</span>
                </nav>
            </div>
            <div class="topbar-right">
                <a href="add_product.php" class="btn-save-edit" style="text-decoration:none;">
                    <i class="fa-solid fa-plus"></i> Thêm sản phẩm
                </a>
                <div class="admin-user-info">
                    <i class="fa-solid fa-user-shield"></i>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
        </header>

        <!-- Nội dung -->
        <div class="admin-content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php
            /*
             * VÒNG LẶP THEO DANH MỤC:
             * Mỗi danh mục hiển thị 1 bảng riêng với header màu primary.
             * Từng hàng (tr) khi click → gọi openEdit() mở modal sửa.
             * Nút "Xóa" trong cột cuối khi click → gọi openDelete() (không mở modal sửa).
             */
            foreach ($grouped as $cid => $group): ?>
                <div class="cat-group-header">
                    <i class="fa-solid fa-layer-group"></i>
                    <?= htmlspecialchars($group['name']) ?>
                    <span class="cat-count"><?= count($group['products']) ?> sản phẩm</span>
                </div>

                <table class="product-table">
                    <thead>
                        <tr>
                            <th style="width:60px">Ảnh</th>
                            <th>Tên sách</th>
                            <th>Tác giả</th>
                            <th>Giá</th>
                            <th>Tồn kho</th>
                            <th>Năm XB</th>
                            <th class="td-actions">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group['products'] as $p): ?>
                            <?php
                            /*
                             * Mỗi hàng dùng onclick="openEdit(this)" để mở modal sửa.
                             * Dữ liệu sản phẩm được nhúng qua data-* attributes
                             * (giống cách mainpage.php truyền dữ liệu cho Preview).
                             * Cột "Xóa" dùng stopPropagation để ngăn click lan ra tr.
                             */
                            ?>
                            <tr onclick="openEdit(this)"
                                data-id="<?= $p['ID'] ?>"
                                data-name="<?= htmlspecialchars($p['Name'], ENT_QUOTES) ?>"
                                data-cat="<?= intval($p['Category_ID']) ?>"
                                data-author="<?= htmlspecialchars($p['TacGia'] ?? '', ENT_QUOTES) ?>"
                                data-publisher="<?= htmlspecialchars($p['NhaXuatBan'] ?? '', ENT_QUOTES) ?>"
                                data-year="<?= intval($p['NamXuatBan']) ?>"
                                data-pages="<?= intval($p['SoTrang']) ?>"
                                data-price="<?= intval($p['Price']) ?>"
                                data-qty="<?= intval($p['Quantity']) ?>"
                                data-mota="<?= htmlspecialchars($p['MoTa'] ?? '', ENT_QUOTES) ?>"
                                data-img="<?= htmlspecialchars($p['Image_URL'] ?? '', ENT_QUOTES) ?>">

                                <!-- Ảnh thumbnail -->
                                <td>
                                    <div class="product-thumb">
                                        <?php if (!empty($p['Image_URL']) && file_exists('../' . $p['Image_URL'])): ?>
                                            <img src="../<?= htmlspecialchars($p['Image_URL']) ?>" alt="">
                                        <?php else: ?>
                                            <span class="no-img"><i class="fa-solid fa-book"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td><strong><?= htmlspecialchars($p['Name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['TacGia'] ?? '—') ?></td>
                                <td><?= number_format($p['Price'], 0, ',', '.') ?> ₫</td>

                                <!-- Badge tồn kho: xanh = còn hàng, đỏ = hết -->
                                <td>
                                    <span class="badge-stock <?= $p['Quantity'] <= 0 ? 'out' : '' ?>">
                                        <?= $p['Quantity'] > 0 ? 'Còn ' . $p['Quantity'] : 'Hết hàng' ?>
                                    </span>
                                </td>

                                <td><?= $p['NamXuatBan'] ? intval($p['NamXuatBan']) : '—' ?></td>

                                <!-- Nút xóa: stopPropagation để không trigger click của tr -->
                                <td class="td-actions" onclick="event.stopPropagation()">
                                    <button class="btn-delete-row"
                                            onclick="openDelete(<?= $p['ID'] ?>, '<?= htmlspecialchars($p['Name'], ENT_QUOTES) ?>')">
                                        <i class="fa-solid fa-trash"></i> Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>

            <?php if (empty($grouped)): ?>
                <div style="text-align:center; padding:60px; color:#999;">
                    <i class="fa-solid fa-box-open" style="font-size:48px; margin-bottom:12px; display:block;"></i>
                    Chưa có sản phẩm nào trong hệ thống.
                </div>
            <?php endif; ?>
        </div><!-- /.admin-content -->
    </main>
</div><!-- /.admin-wrapper -->


<!-- ══ MODAL CHỈNH SỬA SẢN PHẨM ════════════════════════════════════════
     Form này luôn tồn tại trong DOM (ẩn bằng CSS).
     Khi click vào row → JS điền data vào form → hiện modal.
     Submit POST với action=edit.
     ════════════════════════════════════════════════════════════════════ -->
<div id="editModalBackdrop" class="edit-modal-backdrop" onclick="closeEditOutside(event)">
    <div class="edit-modal" id="editModal">
        <div class="edit-modal-header">
            <h2><i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa sản phẩm</h2>
            <button class="edit-modal-close" onclick="closeEdit()"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="editForm">
            <!-- ID ẩn — không cho user sửa nhưng POST cần để biết sửa record nào -->
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">

            <div class="edit-modal-body">

                <!-- Xem trước ảnh hiện tại -->
                <div class="edit-form-group full-width" style="margin-bottom:4px;">
                    <label>Ảnh bìa hiện tại</label>
                    <div class="img-preview-row">
                        <div class="img-preview-current no-img" id="editImgPreview">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <div style="flex:1;">
                            <label style="font-size:13px;font-weight:500;color:var(--admin-text-muted);display:block;margin-bottom:6px;">
                                Đổi ảnh mới (bỏ trống = giữ ảnh cũ):
                            </label>
                            <input type="file" name="image" id="editImgInput" accept="image/*"
                                   style="font-size:13px;" onchange="previewNewImg(this)">
                        </div>
                    </div>
                </div>

                <div class="edit-form-grid">
                    <!-- Tên sách — chiếm full width -->
                    <div class="edit-form-group full-width">
                        <label for="editName">Tên sách <span style="color:#e74c3c">*</span></label>
                        <input type="text" id="editName" name="name" required>
                    </div>

                    <!-- Danh mục -->
                    <div class="edit-form-group">
                        <label for="editCat">Thể loại</label>
                        <select id="editCat" name="category_id">
                            <?php
                            // Render lại categories (đã dùng hết con trỏ ở trên, dùng mảng cat_list)
                            foreach ($cat_list as $id => $name): ?>
                                <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tác giả -->
                    <div class="edit-form-group">
                        <label for="editAuthor">Tác giả</label>
                        <input type="text" id="editAuthor" name="author">
                    </div>

                    <!-- NXB -->
                    <div class="edit-form-group">
                        <label for="editPublisher">Nhà xuất bản</label>
                        <input type="text" id="editPublisher" name="publisher">
                    </div>

                    <!-- Năm XB -->
                    <div class="edit-form-group">
                        <label for="editYear">Năm xuất bản</label>
                        <input type="number" id="editYear" name="year" min="1700" max="<?= date('Y') ?>">
                    </div>

                    <!-- Số trang -->
                    <div class="edit-form-group">
                        <label for="editPages">Số trang</label>
                        <input type="number" id="editPages" name="pages" min="0">
                    </div>

                    <!-- Giá -->
                    <div class="edit-form-group">
                        <label for="editPrice">Giá (₫) <span style="color:#e74c3c">*</span></label>
                        <input type="number" id="editPrice" name="price" min="1">
                    </div>

                    <!-- Số lượng -->
                    <div class="edit-form-group">
                        <label for="editQty">Số lượng tồn kho</label>
                        <input type="number" id="editQty" name="quantity" min="0">
                    </div>

                    <!-- Mô tả — full width -->
                    <div class="edit-form-group full-width">
                        <label for="editMota">Mô tả sách</label>
                        <textarea id="editMota" name="mota" rows="4"></textarea>
                    </div>
                </div>
            </div>

            <div class="edit-modal-footer">
                <button type="button" class="btn-cancel-edit" onclick="closeEdit()">Hủy</button>
                <button type="submit" class="btn-save-edit">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══ MODAL XÁC NHẬN XÓA ════════════════════════════════════════════════
     Hiển thị cảnh báo + tên sản phẩm trước khi gửi POST xóa.
     Bấm "Xóa" → submit form ẩn bên dưới với action=delete.
     ════════════════════════════════════════════════════════════════════ -->
<div id="confirmModalBackdrop" class="confirm-modal-backdrop">
    <div class="confirm-modal">
        <div class="warn-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3>Xác nhận xóa sản phẩm</h3>
        <p>Bạn sắp xóa vĩnh viễn sách<br><strong id="confirmProductName"></strong><br>Hành động này <strong>không thể hoàn tác</strong>.</p>
        <div class="confirm-actions">
            <button class="btn-confirm-no" onclick="closeDelete()">Hủy bỏ</button>
            <button class="btn-confirm-yes" onclick="submitDelete()">
                <i class="fa-solid fa-trash"></i> Xóa vĩnh viễn
            </button>
        </div>
    </div>
</div>

<!-- Form ẩn để submit DELETE — JS điền id rồi submit() -->
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>


<script>
/**
 * ════════════════════════════════════════════════════════════
 *  JavaScript — product_list.php
 *  A. openEdit() / closeEdit()  : Modal chỉnh sửa sản phẩm
 *  B. openDelete() / closeDelete() / submitDelete() : Modal xác nhận xóa
 *  C. previewNewImg()           : Xem trước ảnh mới trước khi upload
 * ════════════════════════════════════════════════════════════
 */

// ══ A. MODAL CHỈNH SỬA ══════════════════════════════════════════════════

/**
 * openEdit(row)
 * Đọc data-* từ <tr> được click, điền vào form trong modal, rồi mở modal.
 * @param {HTMLElement} row - Phần tử <tr> của sản phẩm được click
 */
function openEdit(row) {
    const d = row.dataset;

    // Điền các trường text / number
    document.getElementById('editId').value        = d.id;
    document.getElementById('editName').value      = d.name;
    document.getElementById('editAuthor').value    = d.author;
    document.getElementById('editPublisher').value = d.publisher;
    document.getElementById('editYear').value      = d.year;
    document.getElementById('editPages').value     = d.pages;
    document.getElementById('editPrice').value     = d.price;
    document.getElementById('editQty').value       = d.qty;
    document.getElementById('editMota').value      = d.mota;

    // Chọn đúng option danh mục
    document.getElementById('editCat').value = d.cat;

    // Hiện ảnh bìa hiện tại (hoặc icon placeholder nếu không có)
    const preview = document.getElementById('editImgPreview');
    if (d.img) {
        preview.innerHTML = `<img src="../${d.img}" alt="" style="width:80px;height:100px;object-fit:cover;border-radius:8px;">`;
        preview.classList.remove('no-img');
    } else {
        preview.innerHTML = '<i class="fa-solid fa-book"></i>';
        preview.classList.add('no-img');
    }

    // Reset input file (xóa file đã chọn trước nếu có)
    document.getElementById('editImgInput').value = '';

    // Mở modal
    document.getElementById('editModalBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}

/** Đóng modal chỉnh sửa, trả lại scroll */
function closeEdit() {
    document.getElementById('editModalBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

/**
 * Đóng modal khi click vào backdrop (nền tối),
 * nhưng KHÔNG đóng khi click vào nội dung modal bên trong.
 */
function closeEditOutside(e) {
    if (e.target === document.getElementById('editModalBackdrop')) closeEdit();
}

// Phím ESC đóng modal chỉnh sửa
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });


// ══ B. MODAL XÁC NHẬN XÓA ═══════════════════════════════════════════════

/**
 * openDelete(id, name)
 * Hiện modal cảnh báo xóa, điền tên sản phẩm vào nội dung cảnh báo.
 * @param {number} id   - ID sản phẩm cần xóa
 * @param {string} name - Tên sản phẩm để hiển thị trong cảnh báo
 */
function openDelete(id, name) {
    document.getElementById('deleteId').value        = id;
    document.getElementById('confirmProductName').textContent = '"' + name + '"';
    document.getElementById('confirmModalBackdrop').classList.add('open');
}

/** Đóng modal xác nhận xóa mà không làm gì */
function closeDelete() {
    document.getElementById('confirmModalBackdrop').classList.remove('open');
}

/** Người dùng xác nhận → submit form DELETE */
function submitDelete() {
    document.getElementById('deleteForm').submit();
}


// ══ C. XEM TRƯỚC ẢNH MỚI ════════════════════════════════════════════════

/**
 * previewNewImg(input)
 * Khi người dùng chọn file ảnh mới → hiện ngay preview trong modal
 * thay vì ảnh cũ để dễ kiểm tra trước khi lưu.
 * @param {HTMLInputElement} input - Input file vừa được chọn
 */
function previewNewImg(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('editImgPreview');
        preview.innerHTML = `<img src="${e.target.result}" alt="" style="width:80px;height:100px;object-fit:cover;border-radius:8px;">`;
        preview.classList.remove('no-img');
    };
    reader.readAsDataURL(input.files[0]);
}
</script>

</body>
</html>
