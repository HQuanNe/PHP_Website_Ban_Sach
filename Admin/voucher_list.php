<?php
/**
 * voucher_list.php — Quản lý Voucher (Admin)
 * CRUD voucher: thêm, sửa, xoá, toggle banner
 * Phải được include từ admin_dashboard.php (cần $conn).
 */
if (!isset($conn)) {
    header('Location: admin_dashboard.php?page=voucher_list');
    exit;
}
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $code      = $conn->real_escape_string(strtoupper(trim($_POST['code'] ?? '')));
        $type      = in_array($_POST['type'] ?? '', ['percent','fixed']) ? $_POST['type'] : 'fixed';
        $value     = floatval($_POST['value'] ?? 0);
        $min_order = floatval($_POST['min_order'] ?? 0);
        $max_disc  = ($_POST['max_discount'] ?? '') !== '' ? floatval($_POST['max_discount']) : 'NULL';
        $qty       = intval($_POST['quantity'] ?? -1);
        $start     = $conn->real_escape_string($_POST['start_date'] ?? date('Y-m-d'));
        $end       = $conn->real_escape_string($_POST['end_date'] ?? date('Y-m-d'));
        $is_banner = isset($_POST['is_banner']) ? 1 : 0;
        $b_title   = $conn->real_escape_string(trim($_POST['banner_title'] ?? ''));
        $b_sub     = $conn->real_escape_string(trim($_POST['banner_subtitle'] ?? ''));
        $status    = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

        if (empty($code)) { $error = 'Mã voucher không được để trống.'; }
        else {
            $dup = $conn->query("SELECT ID FROM vouchers WHERE Code='$code'");
            if ($dup && $dup->num_rows > 0) { $error = 'Mã voucher đã tồn tại.'; }
            else {
                $max_sql = $max_disc === 'NULL' ? 'NULL' : $max_disc;
                $conn->query("INSERT INTO vouchers (Code,Type,Value,Min_order,Max_discount,Quantity,Start_date,End_date,Is_banner,Banner_title,Banner_subtitle,Status)
                    VALUES ('$code','$type',$value,$min_order,$max_sql,$qty,'$start','$end',$is_banner,'$b_title','$b_sub','$status')");
                $success = $conn->error ? '' : 'Thêm voucher thành công!';
                if ($conn->error) $error = 'Lỗi DB: '.$conn->error;
            }
        }
    }

    if ($action === 'edit') {
        $id        = intval($_POST['id'] ?? 0);
        $code      = $conn->real_escape_string(strtoupper(trim($_POST['code'] ?? '')));
        $type      = in_array($_POST['type'] ?? '', ['percent','fixed']) ? $_POST['type'] : 'fixed';
        $value     = floatval($_POST['value'] ?? 0);
        $min_order = floatval($_POST['min_order'] ?? 0);
        $max_disc  = ($_POST['max_discount'] ?? '') !== '' ? floatval($_POST['max_discount']) : 'NULL';
        $qty       = intval($_POST['quantity'] ?? -1);
        $start     = $conn->real_escape_string($_POST['start_date'] ?? '');
        $end       = $conn->real_escape_string($_POST['end_date'] ?? '');
        $is_banner = isset($_POST['is_banner']) ? 1 : 0;
        $b_title   = $conn->real_escape_string(trim($_POST['banner_title'] ?? ''));
        $b_sub     = $conn->real_escape_string(trim($_POST['banner_subtitle'] ?? ''));
        $status    = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

        if ($id > 0 && !empty($code)) {
            $dup = $conn->query("SELECT ID FROM vouchers WHERE Code='$code' AND ID!=$id");
            if ($dup && $dup->num_rows > 0) { $error = 'Mã voucher đã tồn tại.'; }
            else {
                $max_sql = $max_disc === 'NULL' ? 'NULL' : $max_disc;
                $conn->query("UPDATE vouchers SET Code='$code',Type='$type',Value=$value,Min_order=$min_order,Max_discount=$max_sql,Quantity=$qty,Start_date='$start',End_date='$end',Is_banner=$is_banner,Banner_title='$b_title',Banner_subtitle='$b_sub',Status='$status' WHERE ID=$id");
                $success = $conn->error ? '' : 'Cập nhật voucher thành công!';
                if ($conn->error) $error = 'Lỗi: '.$conn->error;
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM vouchers WHERE ID=$id");
            $success = 'Đã xoá voucher.';
        }
    }
}

$vouchers = $conn->query("SELECT * FROM vouchers ORDER BY Created_at DESC");
$today = date('Y-m-d');
?>

<style>
    .vc-table { width:100%; border-collapse:collapse; background:var(--admin-card); border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    .vc-table thead tr { background:var(--admin-primary); color:#fff; }
    .vc-table th { padding:10px 12px; text-align:left; font-size:12px; font-weight:600; white-space:nowrap; }
    .vc-table tbody tr { border-bottom:1px solid var(--admin-border); cursor:pointer; transition:background 0.15s; }
    .vc-table tbody tr:hover { background:color-mix(in srgb, var(--admin-primary) 6%, transparent); }
    .vc-table td { padding:10px 12px; font-size:13px; color:var(--admin-text); vertical-align:middle; }
    .vc-code { font-family:'Courier New',monospace; font-weight:700; background:var(--admin-primary-light); padding:3px 8px; border-radius:6px; font-size:13px; letter-spacing:1px; }
    .vc-type { padding:3px 8px; border-radius:12px; font-size:11px; font-weight:700; }
    .vc-type-percent { background:#fff3e0; color:#e65100; }
    .vc-type-fixed { background:#e8f5e9; color:#2e7d32; }
    .vc-status { padding:3px 8px; border-radius:12px; font-size:11px; font-weight:700; }
    .vc-active { background:#e8f5e9; color:#2e7d32; }
    .vc-inactive { background:#fce4e4; color:#c0392b; }
    .vc-expired { background:#f5f5f5; color:#999; }
    .vc-banner-on { color:#e65100; font-weight:700; }
    .td-actions { width:60px; text-align:center; }
    .btn-del-vc { padding:4px 10px; border:none; background:#e74c3c; color:#fff; border-radius:6px; font-size:11px; cursor:pointer; }
    .btn-del-vc:hover { background:#c0392b; }
    .btn-add-vc { padding:10px 20px; border:none; border-radius:8px; background:var(--admin-primary); color:#fff; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:14px; margin-bottom:16px; }
    .btn-add-vc:hover { opacity:0.85; }

    /* Modal */
    .vm-backdrop { display:none; position:fixed; inset:0; background:rgba(20,15,10,0.55); backdrop-filter:blur(5px); z-index:9000; justify-content:center; align-items:flex-start; padding:20px; overflow-y:auto; }
    .vm-backdrop.open { display:flex; }
    .vm-modal { background:var(--admin-card); border-radius:18px; box-shadow:0 24px 60px rgba(0,0,0,0.28); width:100%; max-width:680px; margin:auto; animation:vmSlide 0.28s cubic-bezier(0.175,0.885,0.32,1.275); }
    @keyframes vmSlide { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }
    .vm-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px 14px; border-bottom:1px solid var(--admin-border); }
    .vm-header h2 { font-size:17px; font-weight:700; display:flex; align-items:center; gap:10px; }
    .vm-close { width:32px;height:32px;border:none;border-radius:50%;background:var(--admin-bg);color:var(--admin-text-muted);font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s; }
    .vm-close:hover { background:#e74c3c; color:#fff; transform:rotate(90deg); }
    .vm-body { padding:20px 24px; }
    .vm-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .vm-grid .full { grid-column:1/-1; }
    .vm-group { display:flex; flex-direction:column; gap:5px; }
    .vm-group label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--admin-text-muted); }
    .vm-group input, .vm-group select, .vm-group textarea { padding:8px 12px; border:1.5px solid var(--admin-border); border-radius:8px; font-size:13px; background:var(--admin-bg); color:var(--admin-text); font-family:inherit; }
    .vm-group input:focus, .vm-group select:focus { outline:none; border-color:var(--admin-primary); }
    .vm-group textarea { resize:vertical; min-height:40px; }
    .vm-footer { display:flex; justify-content:flex-end; gap:12px; padding:14px 24px 20px; border-top:1px solid var(--admin-border); }
    .vm-btn-cancel { padding:9px 20px; border:1.5px solid var(--admin-border); border-radius:8px; background:transparent; color:var(--admin-text-muted); font-weight:600; cursor:pointer; }
    .vm-btn-save { padding:9px 26px; border:none; border-radius:8px; background:var(--admin-primary); color:#fff; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; }
    .vm-btn-save:hover { opacity:0.85; }
    .vm-check { display:flex; align-items:center; gap:8px; margin-top:8px; }
    .vm-check input[type=checkbox] { width:18px; height:18px; accent-color:var(--admin-primary); }
    .vm-banner-fields { margin-top:10px; padding:12px; background:var(--admin-bg); border-radius:10px; border:1px dashed var(--admin-border); }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
<?php endif; ?>

<button class="btn-add-vc" onclick="openAddVc()"><i class="fa-solid fa-ticket"></i> Thêm voucher</button>

<table class="vc-table">
    <thead><tr>
        <th>Mã</th><th>Loại</th><th>Giá trị</th><th>Đơn tối thiểu</th><th>Lượt</th><th>Hiệu lực</th><th>Banner</th><th>Trạng thái</th><th class="td-actions">Xoá</th>
    </tr></thead>
    <tbody>
    <?php if ($vouchers && $vouchers->num_rows > 0): ?>
        <?php while ($v = $vouchers->fetch_assoc()):
            $expired = $v['End_date'] < $today;
            $remaining = $v['Quantity'] < 0 ? '∞' : ($v['Quantity'] - $v['Used']);
        ?>
            <tr onclick="openEditVc(this)"
                data-id="<?= $v['ID'] ?>"
                data-code="<?= htmlspecialchars($v['Code'], ENT_QUOTES) ?>"
                data-type="<?= $v['Type'] ?>"
                data-value="<?= $v['Value'] ?>"
                data-min="<?= $v['Min_order'] ?>"
                data-max="<?= $v['Max_discount'] ?? '' ?>"
                data-qty="<?= $v['Quantity'] ?>"
                data-start="<?= $v['Start_date'] ?>"
                data-end="<?= $v['End_date'] ?>"
                data-banner="<?= $v['Is_banner'] ?>"
                data-btitle="<?= htmlspecialchars($v['Banner_title'] ?? '', ENT_QUOTES) ?>"
                data-bsub="<?= htmlspecialchars($v['Banner_subtitle'] ?? '', ENT_QUOTES) ?>"
                data-status="<?= $v['Status'] ?>">
                <td><span class="vc-code"><?= htmlspecialchars($v['Code']) ?></span></td>
                <td><span class="vc-type <?= $v['Type']==='percent'?'vc-type-percent':'vc-type-fixed' ?>"><?= $v['Type']==='percent'?'Giảm %':'Giảm tiền' ?></span></td>
                <td><strong><?= $v['Type']==='percent' ? $v['Value'].'%' : number_format($v['Value'],0,',','.').'₫' ?></strong></td>
                <td><?= number_format($v['Min_order'],0,',','.').'₫' ?></td>
                <td><?= $v['Used'] ?>/<?= $v['Quantity']<0?'∞':$v['Quantity'] ?></td>
                <td><?= date('d/m',strtotime($v['Start_date'])).' → '.date('d/m/Y',strtotime($v['End_date'])) ?></td>
                <td><?= $v['Is_banner']?'<span class="vc-banner-on"><i class="fa-solid fa-flag"></i> Bật</span>':'—' ?></td>
                <td><span class="vc-status <?= $expired?'vc-expired':($v['Status']==='active'?'vc-active':'vc-inactive') ?>"><?= $expired?'Hết hạn':($v['Status']==='active'?'Hoạt động':'Tắt') ?></span></td>
                <td class="td-actions" onclick="event.stopPropagation()">
                    <form method="POST" style="display:inline" onsubmit="return confirm('Xoá voucher này?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $v['ID'] ?>">
                        <button class="btn-del-vc"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="9" style="text-align:center;padding:30px;color:#999;">Chưa có voucher nào.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<!-- MODAL THÊM -->
<div id="addVcBackdrop" class="vm-backdrop" onclick="if(event.target===this)closeAddVc()">
<div class="vm-modal">
    <div class="vm-header"><h2><i class="fa-solid fa-ticket"></i> Thêm voucher mới</h2><button class="vm-close" onclick="closeAddVc()"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="vm-body"><div class="vm-grid">
            <div class="vm-group"><label>Mã voucher *</label><input type="text" name="code" required style="text-transform:uppercase"></div>
            <div class="vm-group"><label>Loại</label><select name="type"><option value="percent">Giảm %</option><option value="fixed">Giảm tiền</option></select></div>
            <div class="vm-group"><label>Giá trị *</label><input type="number" name="value" required placeholder="VD: 50 hoặc 30000"></div>
            <div class="vm-group"><label>Đơn tối thiểu</label><input type="number" name="min_order" value="0"></div>
            <div class="vm-group"><label>Giảm tối đa (loại %)</label><input type="number" name="max_discount" placeholder="Để trống = không giới hạn"></div>
            <div class="vm-group"><label>Số lượt (-1 = ∞)</label><input type="number" name="quantity" value="-1"></div>
            <div class="vm-group"><label>Ngày bắt đầu</label><input type="date" name="start_date" value="<?= date('Y-m-d') ?>"></div>
            <div class="vm-group"><label>Ngày kết thúc</label><input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
            <div class="vm-group"><label>Trạng thái</label><select name="status"><option value="active">Hoạt động</option><option value="inactive">Tắt</option></select></div>
            <div class="vm-group full">
                <div class="vm-check"><input type="checkbox" name="is_banner" id="addBannerCk" onchange="document.getElementById('addBannerFields').style.display=this.checked?'block':'none'"><label for="addBannerCk" style="font-size:13px;text-transform:none;cursor:pointer;">Hiển thị trên banner trang chủ</label></div>
                <div id="addBannerFields" class="vm-banner-fields" style="display:none;">
                    <div class="vm-group"><label>Tiêu đề banner</label><input type="text" name="banner_title" placeholder="VD: 🔥 ƯU ĐÃI CHÀO HÈ"></div>
                    <div class="vm-group" style="margin-top:8px"><label>Phụ đề</label><input type="text" name="banner_subtitle" placeholder="VD: Giảm 50% cho đơn từ 100K"></div>
                </div>
            </div>
        </div></div>
        <div class="vm-footer"><button type="button" class="vm-btn-cancel" onclick="closeAddVc()">Huỷ</button><button type="submit" class="vm-btn-save"><i class="fa-solid fa-plus"></i> Thêm</button></div>
    </form>
</div></div>

<!-- MODAL SỬA -->
<div id="editVcBackdrop" class="vm-backdrop" onclick="if(event.target===this)closeEditVc()">
<div class="vm-modal">
    <div class="vm-header"><h2><i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa voucher</h2><button class="vm-close" onclick="closeEditVc()"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="evId">
        <div class="vm-body"><div class="vm-grid">
            <div class="vm-group"><label>Mã voucher *</label><input type="text" name="code" id="evCode" required style="text-transform:uppercase"></div>
            <div class="vm-group"><label>Loại</label><select name="type" id="evType"><option value="percent">Giảm %</option><option value="fixed">Giảm tiền</option></select></div>
            <div class="vm-group"><label>Giá trị *</label><input type="number" name="value" id="evValue" required></div>
            <div class="vm-group"><label>Đơn tối thiểu</label><input type="number" name="min_order" id="evMin"></div>
            <div class="vm-group"><label>Giảm tối đa (loại %)</label><input type="number" name="max_discount" id="evMax" placeholder="Để trống = không giới hạn"></div>
            <div class="vm-group"><label>Số lượt (-1 = ∞)</label><input type="number" name="quantity" id="evQty"></div>
            <div class="vm-group"><label>Ngày bắt đầu</label><input type="date" name="start_date" id="evStart"></div>
            <div class="vm-group"><label>Ngày kết thúc</label><input type="date" name="end_date" id="evEnd"></div>
            <div class="vm-group"><label>Trạng thái</label><select name="status" id="evStatus"><option value="active">Hoạt động</option><option value="inactive">Tắt</option></select></div>
            <div class="vm-group full">
                <div class="vm-check"><input type="checkbox" name="is_banner" id="editBannerCk" onchange="document.getElementById('editBannerFields').style.display=this.checked?'block':'none'"><label for="editBannerCk" style="font-size:13px;text-transform:none;cursor:pointer;">Hiển thị trên banner trang chủ</label></div>
                <div id="editBannerFields" class="vm-banner-fields" style="display:none;">
                    <div class="vm-group"><label>Tiêu đề banner</label><input type="text" name="banner_title" id="evBTitle"></div>
                    <div class="vm-group" style="margin-top:8px"><label>Phụ đề</label><input type="text" name="banner_subtitle" id="evBSub"></div>
                </div>
            </div>
        </div></div>
        <div class="vm-footer"><button type="button" class="vm-btn-cancel" onclick="closeEditVc()">Huỷ</button><button type="submit" class="vm-btn-save"><i class="fa-solid fa-floppy-disk"></i> Lưu</button></div>
    </form>
</div></div>

<script>
function openAddVc(){ document.getElementById('addVcBackdrop').classList.add('open'); }
function closeAddVc(){ document.getElementById('addVcBackdrop').classList.remove('open'); }
function openEditVc(row){
    const d = row.dataset;
    document.getElementById('evId').value = d.id;
    document.getElementById('evCode').value = d.code;
    document.getElementById('evType').value = d.type;
    document.getElementById('evValue').value = d.value;
    document.getElementById('evMin').value = d.min;
    document.getElementById('evMax').value = d.max;
    document.getElementById('evQty').value = d.qty;
    document.getElementById('evStart').value = d.start;
    document.getElementById('evEnd').value = d.end;
    document.getElementById('evStatus').value = d.status;
    const ck = document.getElementById('editBannerCk');
    ck.checked = d.banner === '1';
    document.getElementById('editBannerFields').style.display = ck.checked ? 'block' : 'none';
    document.getElementById('evBTitle').value = d.btitle;
    document.getElementById('evBSub').value = d.bsub;
    document.getElementById('editVcBackdrop').classList.add('open');
}
function closeEditVc(){ document.getElementById('editVcBackdrop').classList.remove('open'); }
</script>
