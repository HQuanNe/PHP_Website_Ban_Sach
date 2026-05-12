<?php
/**
 * category_list.php — Quản lý Danh mục sách (Admin)
 * Phải được include từ admin_dashboard.php.
 */
if (!isset($conn)) {
    header('Location: admin_dashboard.php?page=category_list');
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
        if (empty($name)) {
            $error = 'Tên danh mục không được để trống.';
        } else {
            $dup = $conn->query("SELECT ID FROM category WHERE Decription='$name'");
            if ($dup && $dup->num_rows > 0) {
                $error = 'Danh mục đã tồn tại.';
            } else {
                $conn->query("INSERT INTO category (Decription) VALUES ('$name')");
                $success = $conn->error ? '' : 'Thêm danh mục thành công!';
                if ($conn->error) $error = 'Lỗi: '.$conn->error;
            }
        }
    }

    if ($action === 'edit') {
        $id   = intval($_POST['id'] ?? 0);
        $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
        if ($id > 0 && !empty($name)) {
            $dup = $conn->query("SELECT ID FROM category WHERE Decription='$name' AND ID!=$id");
            if ($dup && $dup->num_rows > 0) {
                $error = 'Tên danh mục đã tồn tại.';
            } else {
                $conn->query("UPDATE category SET Decription='$name' WHERE ID=$id");
                $success = $conn->error ? '' : 'Cập nhật danh mục thành công!';
                if ($conn->error) $error = 'Lỗi: '.$conn->error;
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            // Kiểm tra FK — còn sách trong danh mục?
            $count = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE Category_ID=$id");
            $cnt = $count ? $count->fetch_assoc()['cnt'] : 0;
            if ($cnt > 0) {
                $error = "Không thể xoá! Danh mục này còn $cnt sản phẩm. Hãy chuyển hoặc xoá sản phẩm trước.";
            } else {
                $conn->query("DELETE FROM category WHERE ID=$id");
                $success = 'Đã xoá danh mục.';
            }
        }
    }
}

// Query danh mục + đếm sản phẩm
$categories = $conn->query("SELECT c.*, COUNT(p.ID) as product_count 
    FROM category c LEFT JOIN products p ON c.ID = p.Category_ID 
    GROUP BY c.ID ORDER BY c.ID ASC");
?>

<style>
    .cat-table { width:100%; border-collapse:collapse; background:var(--admin-card); border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    .cat-table thead tr { background:var(--admin-primary); color:#fff; }
    .cat-table th { padding:12px 16px; text-align:left; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
    .cat-table tbody tr { border-bottom:1px solid var(--admin-border); cursor:pointer; transition:background 0.15s; }
    .cat-table tbody tr:hover { background:color-mix(in srgb, var(--admin-primary) 6%, transparent); }
    .cat-table td { padding:12px 16px; font-size:14px; color:var(--admin-text); }
    .cat-id { font-weight:700; color:var(--admin-primary); }
    .cat-name { font-weight:600; }
    .cat-count { display:inline-flex; align-items:center; gap:6px; background:var(--admin-bg); padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; color:var(--admin-primary); }
    .cat-actions { width:60px; text-align:center; }
    .btn-del-cat { padding:6px 12px; border:none; background:#e74c3c; color:#fff; border-radius:6px; font-size:12px; cursor:pointer; transition:all 0.2s; }
    .btn-del-cat:hover { background:#c0392b; }
    .btn-add-cat { padding:10px 20px; border:none; border-radius:8px; background:var(--admin-primary); color:#fff; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:14px; margin-bottom:16px; }
    .btn-add-cat:hover { opacity:0.85; }

    /* Modal */
    .cm-backdrop { display:none; position:fixed; inset:0; background:rgba(20,15,10,0.55); backdrop-filter:blur(5px); z-index:9000; justify-content:center; align-items:center; }
    .cm-backdrop.open { display:flex; }
    .cm-modal { background:var(--admin-card); border-radius:18px; box-shadow:0 24px 60px rgba(0,0,0,0.28); width:100%; max-width:440px; animation:cmSlide 0.28s cubic-bezier(0.175,0.885,0.32,1.275); }
    @keyframes cmSlide { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }
    .cm-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid var(--admin-border); }
    .cm-header h2 { font-size:17px; font-weight:700; display:flex; align-items:center; gap:10px; }
    .cm-close { width:32px;height:32px;border:none;border-radius:50%;background:var(--admin-bg);color:var(--admin-text-muted);font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s; }
    .cm-close:hover { background:#e74c3c; color:#fff; transform:rotate(90deg); }
    .cm-body { padding:20px 24px; }
    .cm-group { display:flex; flex-direction:column; gap:6px; }
    .cm-group label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--admin-text-muted); }
    .cm-group input { padding:10px 14px; border:1.5px solid var(--admin-border); border-radius:8px; font-size:14px; background:var(--admin-bg); color:var(--admin-text); font-family:inherit; }
    .cm-group input:focus { outline:none; border-color:var(--admin-primary); }
    .cm-footer { display:flex; justify-content:flex-end; gap:12px; padding:14px 24px 20px; border-top:1px solid var(--admin-border); }
    .cm-btn-cancel { padding:9px 20px; border:1.5px solid var(--admin-border); border-radius:8px; background:transparent; color:var(--admin-text-muted); font-weight:600; cursor:pointer; }
    .cm-btn-save { padding:9px 26px; border:none; border-radius:8px; background:var(--admin-primary); color:#fff; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; }
    .cm-btn-save:hover { opacity:0.85; }

    .alert { padding:12px 18px; border-radius:10px; margin-bottom:16px; font-size:13px; display:flex; align-items:center; gap:10px; }
    .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
    .alert-error { background:#fce4e4; color:#c0392b; border:1px solid #f5c6cb; }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
<?php endif; ?>

<button class="btn-add-cat" onclick="document.getElementById('addCatBackdrop').classList.add('open')">
    <i class="fa-solid fa-layer-group"></i> Thêm danh mục
</button>

<table class="cat-table">
    <thead><tr>
        <th style="width:80px">ID</th>
        <th>Tên danh mục</th>
        <th style="width:150px">Số sản phẩm</th>
        <th class="cat-actions">Xoá</th>
    </tr></thead>
    <tbody>
    <?php if ($categories && $categories->num_rows > 0): ?>
        <?php while ($c = $categories->fetch_assoc()): ?>
            <tr onclick="openEditCat(<?= $c['ID'] ?>, '<?= htmlspecialchars(addslashes($c['Decription']), ENT_QUOTES) ?>')">
                <td><span class="cat-id">#<?= $c['ID'] ?></span></td>
                <td><span class="cat-name"><?= htmlspecialchars($c['Decription']) ?></span></td>
                <td><span class="cat-count"><i class="fa-solid fa-book"></i> <?= $c['product_count'] ?> sản phẩm</span></td>
                <td class="cat-actions" onclick="event.stopPropagation()">
                    <form method="POST" style="display:inline" onsubmit="return confirm('Xoá danh mục «<?= htmlspecialchars(addslashes($c['Decription'])) ?>»?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['ID'] ?>">
                        <button class="btn-del-cat"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4" style="text-align:center;padding:30px;color:#999;">Chưa có danh mục nào.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<!-- Modal THÊM -->
<div id="addCatBackdrop" class="cm-backdrop" onclick="if(event.target===this)this.classList.remove('open')">
<div class="cm-modal">
    <div class="cm-header"><h2><i class="fa-solid fa-layer-group"></i> Thêm danh mục</h2><button class="cm-close" onclick="document.getElementById('addCatBackdrop').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="cm-body"><div class="cm-group"><label>Tên danh mục *</label><input type="text" name="name" required placeholder="VD: Thiếu nhi, Kinh tế..."></div></div>
        <div class="cm-footer"><button type="button" class="cm-btn-cancel" onclick="document.getElementById('addCatBackdrop').classList.remove('open')">Huỷ</button><button type="submit" class="cm-btn-save"><i class="fa-solid fa-plus"></i> Thêm</button></div>
    </form>
</div></div>

<!-- Modal SỬA -->
<div id="editCatBackdrop" class="cm-backdrop" onclick="if(event.target===this)this.classList.remove('open')">
<div class="cm-modal">
    <div class="cm-header"><h2><i class="fa-solid fa-pen-to-square"></i> Sửa danh mục</h2><button class="cm-close" onclick="document.getElementById('editCatBackdrop').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="ecId">
        <div class="cm-body"><div class="cm-group"><label>Tên danh mục *</label><input type="text" name="name" id="ecName" required></div></div>
        <div class="cm-footer"><button type="button" class="cm-btn-cancel" onclick="document.getElementById('editCatBackdrop').classList.remove('open')">Huỷ</button><button type="submit" class="cm-btn-save"><i class="fa-solid fa-floppy-disk"></i> Lưu</button></div>
    </form>
</div></div>

<script>
function openEditCat(id, name) {
    document.getElementById('ecId').value = id;
    document.getElementById('ecName').value = name;
    document.getElementById('editCatBackdrop').classList.add('open');
}
</script>
