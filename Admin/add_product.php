<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../Connect/connect.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = $conn->real_escape_string(trim($_POST['name']));
    $category_id = intval($_POST['category_id']);
    $author      = $conn->real_escape_string(trim($_POST['author']));
    $publisher   = $conn->real_escape_string(trim($_POST['publisher']));
    $year        = intval($_POST['year']);
    $pages       = intval($_POST['pages']);
    $price       = intval($_POST['price']);
    $quantity    = intval($_POST['quantity']);
    $mota        = $conn->real_escape_string(trim($_POST['mota'] ?? ''));
    $image_url   = '';

    // Xử lý upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        if (!in_array($file_type, $allowed)) {
            $error = 'Chỉ chấp nhận file ảnh JPG, PNG, WEBP, GIF.';
        } else {
            // Lấy tên danh mục để làm tên folder
            $cat_res = $conn->query("SELECT Decription FROM category WHERE ID = $category_id");
            $cat_row = $cat_res ? $cat_res->fetch_assoc() : null;
            $cat_folder = 'Other';
            if ($cat_row) {
                // Chuyển sang tên folder không dấu
                $cat_map = [
                    'Kỹ năng' => 'KyNang',
                    'Văn học' => 'VanHoc',
                    'Lịch sử' => 'LichSu',
                    'Cổ tích'  => 'CoTich',
                ];
                $cat_folder = $cat_map[$cat_row['Decription']] ?? preg_replace('/[^a-zA-Z0-9]/', '', $cat_row['Decription']);
            }

            $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('book_') . '.' . $ext;
            $upload_dir = "../Resource/Image/BookImage/$cat_folder/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $dest = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image_url = "Resource/Image/BookImage/$cat_folder/$filename";
            } else {
                $error = 'Lỗi khi tải ảnh lên server.';
            }
        }
    }

    if (empty($error)) {
        if (empty($name)) {
            $error = 'Vui lòng nhập tên sách.';
        } elseif ($price <= 0) {
            $error = 'Giá sách phải lớn hơn 0.';
        } else {
            $sql = "INSERT INTO products (Name, Category_ID, TacGia, NhaXuatBan, NamXuatBan, SoTrang, Price, Quantity, MoTa, Image_URL, Update_at)
                    VALUES ('$name', $category_id, '$author', '$publisher', $year, $pages, $price, $quantity, '$mota', '$image_url', NOW())";
            if ($conn->query($sql)) {
                $success = 'Thêm sách thành công!';
            } else {
                $error = 'Lỗi DB: ' . $conn->error;
            }
        }
    }
}

// Lấy danh sách thể loại
$categories = $conn->query("SELECT * FROM category ORDER BY ID");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm sản phẩm - Dream Book Admin</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../Resource/FontAwesome/fontawesome-free-7.2.0-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                        <li><a href="add_product.php" class="active"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a></li>
                        <li><a href="#"><i class="fa-solid fa-list"></i> Danh sách</a></li>
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

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <h1 class="page-title"><i class="fa-solid fa-plus"></i> Thêm sản phẩm mới</h1>
                <nav class="breadcrumb">
                    <span>Dashboard</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>Sản phẩm</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span class="active">Thêm mới</span>
                </nav>
            </div>
            <div class="topbar-right">
                <div class="admin-user-info">
                    <i class="fa-solid fa-user-shield"></i>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
        </header>

        <!-- Form Container -->
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

            <form method="POST" enctype="multipart/form-data" class="product-form">
                <div class="form-grid">
                    <!-- Cột trái -->
                    <div class="form-col">
                        <div class="form-card">
                            <h3 class="card-title"><i class="fa-solid fa-circle-info"></i> Thông tin cơ bản</h3>

                            <div class="form-group">
                                <label for="name">Tên sách <span class="required">*</span></label>
                                <input type="text" id="name" name="name" placeholder="Nhập tên sách..." required>
                            </div>

                            <div class="form-group">
                                <label for="category_id">Thể loại <span class="required">*</span></label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">-- Chọn thể loại --</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['ID'] ?>"><?= htmlspecialchars($cat['Decription']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="author">Tác giả</label>
                                <input type="text" id="author" name="author" placeholder="Tên tác giả...">
                            </div>

                            <div class="form-group">
                                <label for="publisher">Nhà xuất bản</label>
                                <input type="text" id="publisher" name="publisher" placeholder="Tên NXB...">
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="year">Năm xuất bản</label>
                                    <input type="number" id="year" name="year" min="1900" max="<?= date('Y') ?>" placeholder="2024">
                                </div>
                                <div class="form-group">
                                    <label for="pages">Số trang</label>
                                    <input type="number" id="pages" name="pages" min="1" placeholder="VD: 320">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="mota">Mô tả sách</label>
                                <textarea id="mota" name="mota" rows="5" placeholder="Nhập mô tả ngắn về nội dung sách..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải -->
                    <div class="form-col">
                        <div class="form-card">
                            <h3 class="card-title"><i class="fa-solid fa-tag"></i> Giá & Kho hàng</h3>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label for="price">Giá (₫) <span class="required">*</span></label>
                                    <div class="input-prefix">
                                        <span>₫</span>
                                        <input type="number" id="price" name="price" min="0" placeholder="85000" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="quantity">Số lượng <span class="required">*</span></label>
                                    <input type="number" id="quantity" name="quantity" min="0" placeholder="0" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-card">
                            <h3 class="card-title"><i class="fa-solid fa-image"></i> Ảnh bìa sách</h3>

                            <div class="form-group">
                                <label>Tải ảnh lên</label>
                                <div class="upload-area" id="uploadArea" onclick="document.getElementById('image').click()">
                                    <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                    <p>Click hoặc kéo thả ảnh vào đây</p>
                                    <span>JPG, PNG, WEBP, GIF (Tối đa 5MB)</span>
                                    <img id="imgPreview" src="" alt="Preview" style="display:none; max-height: 160px; border-radius: 8px; margin-top: 12px;">
                                </div>
                                <input type="file" id="image" name="image" accept="image/*" style="display:none" onchange="previewImage(this)">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="form-actions">
                    <button type="reset" class="btn-reset">
                        <i class="fa-solid fa-rotate-left"></i> Làm lại
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Lưu sản phẩm
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imgPreview');
    const uploadIcon = document.querySelector('.upload-icon');
    const uploadText = document.querySelector('.upload-area p');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            uploadIcon.style.display = 'none';
            uploadText.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Kéo thả
const uploadArea = document.getElementById('uploadArea');
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    const fileInput = document.getElementById('image');
    fileInput.files = e.dataTransfer.files;
    previewImage(fileInput);
});
</script>
</body>
</html>
