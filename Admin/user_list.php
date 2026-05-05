<?php
/**
 * user_list.php — Quản lý Người dùng (Admin)
 * - Hiển thị danh sách (KHÔNG hiện mật khẩu)
 * - Click dòng → modal sửa thông tin
 * - Form đổi mật khẩu riêng
 * - Thêm / Xoá user (kiểm tra FK)
 */

$success = '';
$error   = '';

// ── XỬ LÝ POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // THÊM USER MỚI
    if ($action === 'add') {
        $username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $role     = in_array($_POST['role'] ?? '', ['admin','customer']) ? $_POST['role'] : 'customer';
        $phone    = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $address  = $conn->real_escape_string(trim($_POST['address'] ?? ''));

        if (empty($username) || empty($password)) {
            $error = 'Tên đăng nhập và mật khẩu không được để trống.';
        } else {
            $check = $conn->query("SELECT ID FROM users WHERE UserName = '$username'");
            if ($check && $check->num_rows > 0) {
                $error = 'Tên đăng nhập đã tồn tại.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (UserName, Passwd, Role, Phone, Address) VALUES ('$username', '$hashed', '$role', '$phone', '$address')";
                if ($conn->query($sql)) {
                    $success = 'Thêm người dùng thành công!';
                } else {
                    $error = 'Lỗi DB: ' . $conn->error;
                }
            }
        }
    }

    // SỬA THÔNG TIN (không đổi pass)
    if ($action === 'edit') {
        $uid      = intval($_POST['id'] ?? 0);
        $username = $conn->real_escape_string(trim($_POST['username'] ?? ''));
        $role     = in_array($_POST['role'] ?? '', ['admin','customer']) ? $_POST['role'] : 'customer';
        $phone    = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $address  = $conn->real_escape_string(trim($_POST['address'] ?? ''));

        if ($uid > 0 && !empty($username)) {
            $dup = $conn->query("SELECT ID FROM users WHERE UserName = '$username' AND ID != $uid");
            if ($dup && $dup->num_rows > 0) {
                $error = 'Tên đăng nhập đã được sử dụng bởi người khác.';
            } else {
                $conn->query("UPDATE users SET UserName='$username', Role='$role', Phone='$phone', Address='$address' WHERE ID=$uid");
                $success = 'Cập nhật thông tin thành công!';
            }
        }
    }

    // ĐỔI MẬT KHẨU (xác nhận mật khẩu cũ + nhập 2 lần mật khẩu mới)
    if ($action === 'change_password') {
        $uid       = intval($_POST['id'] ?? 0);
        $old_pass  = trim($_POST['old_password'] ?? '');
        $new_pass  = trim($_POST['new_password'] ?? '');
        $new_pass2 = trim($_POST['new_password2'] ?? '');

        if ($uid <= 0 || empty($old_pass) || empty($new_pass)) {
            $error = 'Vui lòng điền đầy đủ các trường mật khẩu.';
        } elseif ($new_pass !== $new_pass2) {
            $error = 'Mật khẩu mới nhập 2 lần không khớp nhau.';
        } else {
            // Lấy mật khẩu cũ từ DB để xác minh
            $pw_check = $conn->query("SELECT Passwd FROM users WHERE ID = $uid");
            $pw_row   = $pw_check ? $pw_check->fetch_assoc() : null;
            if (!$pw_row) {
                $error = 'Không tìm thấy người dùng.';
            } else {
                $stored = $pw_row['Passwd'];
                // Hỗ trợ cả mật khẩu plain text (legacy) và bcrypt hash
                $valid = password_verify($old_pass, $stored) || ($old_pass === $stored);
                if (!$valid) {
                    $error = 'Mật khẩu cũ không chính xác.';
                } else {
                    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                    $conn->query("UPDATE users SET Passwd='$hashed' WHERE ID=$uid");
                    $success = 'Đổi mật khẩu thành công!';
                }
            }
        }
    }

    // XOÁ USER
    if ($action === 'delete') {
        $uid = intval($_POST['id'] ?? 0);
        if ($uid > 0) {
            // Không cho xoá chính mình
            if ($uid == ($_SESSION['user_id'] ?? 0)) {
                $error = 'Bạn không thể xoá chính tài khoản của mình!';
            } else {
                $fk = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE User_ID = $uid");
                $fk_row = $fk ? $fk->fetch_assoc() : null;
                if ($fk_row && $fk_row['cnt'] > 0) {
                    $error = 'Không thể xoá người dùng này vì đã có ' . $fk_row['cnt'] . ' đơn hàng liên quan.';
                } else {
                    $conn->query("DELETE FROM cart WHERE User_ID = $uid");
                    if ($conn->query("DELETE FROM users WHERE ID = $uid")) {
                        $success = 'Đã xoá người dùng thành công.';
                    } else {
                        $error = 'Lỗi khi xoá: ' . $conn->error;
                    }
                }
            }
        }
    }
}

// ── QUERY DATA ──
$users = $conn->query("SELECT ID, UserName, Role, Phone, Address, Create_at FROM users ORDER BY Create_at DESC");
?>

<style>
    .user-table { width:100%; border-collapse:collapse; background:var(--admin-card); border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    .user-table thead tr { background:var(--admin-primary); color:#fff; }
    .user-table th { padding:12px 14px; text-align:left; font-size:13px; font-weight:600; }
    .user-table tbody tr { border-bottom:1px solid var(--admin-border); cursor:pointer; transition:background 0.15s; }
    .user-table tbody tr:hover { background:color-mix(in srgb, var(--admin-primary) 6%, transparent); }
    .user-table td { padding:11px 14px; font-size:13.5px; color:var(--admin-text); vertical-align:middle; }
    .role-badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
    .role-admin { background:#fce4e4; color:#c0392b; }
    .role-customer { background:#e8f5e9; color:#2e7d32; }
    .td-actions { width:90px; text-align:center; }

    /* Modal chung */
    .um-backdrop { display:none; position:fixed; inset:0; background:rgba(20,15,10,0.55); backdrop-filter:blur(5px); z-index:9000; justify-content:center; align-items:flex-start; padding:30px 20px; overflow-y:auto; }
    .um-backdrop.open { display:flex; }
    .um-modal { background:var(--admin-card); border-radius:18px; box-shadow:0 24px 60px rgba(0,0,0,0.28); width:100%; max-width:600px; margin:auto; animation:umSlide 0.28s cubic-bezier(0.175,0.885,0.32,1.275); }
    @keyframes umSlide { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }
    .um-header { display:flex; align-items:center; justify-content:space-between; padding:20px 24px 16px; border-bottom:1px solid var(--admin-border); }
    .um-header h2 { font-size:18px; font-weight:700; display:flex; align-items:center; gap:10px; color:var(--admin-text); }
    .um-close { width:34px;height:34px;border:none;border-radius:50%;background:var(--admin-bg);color:var(--admin-text-muted);font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s; }
    .um-close:hover { background:#e74c3c; color:#fff; transform:rotate(90deg); }
    .um-body { padding:22px 24px; }
    .um-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .um-grid .full { grid-column:1/-1; }
    .um-group { display:flex; flex-direction:column; gap:6px; }
    .um-group label { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--admin-text-muted); }
    .um-group input, .um-group select { padding:9px 12px; border:1.5px solid var(--admin-border); border-radius:8px; font-size:14px; background:var(--admin-bg); color:var(--admin-text); transition:border-color 0.2s; font-family:inherit; }
    .um-group input:focus, .um-group select:focus { outline:none; border-color:var(--admin-primary); }
    .um-footer { display:flex; justify-content:flex-end; gap:12px; padding:16px 24px 22px; border-top:1px solid var(--admin-border); }
    .um-btn-cancel { padding:10px 22px; border:1.5px solid var(--admin-border); border-radius:8px; background:transparent; color:var(--admin-text-muted); font-weight:600; cursor:pointer; }
    .um-btn-save { padding:10px 28px; border:none; border-radius:8px; background:var(--admin-primary); color:#fff; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; }
    .um-btn-save:hover { opacity:0.85; }
    .btn-add-user { padding:10px 20px; border:none; border-radius:8px; background:var(--admin-primary); color:#fff; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; font-size:14px; margin-bottom:16px; }
    .btn-add-user:hover { opacity:0.85; }
    .btn-delete-user { padding:5px 12px; border:none; background:#e74c3c; color:#fff; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; }
    .btn-delete-user:hover { background:#c0392b; }

    .password-section { margin-top:16px; padding-top:16px; border-top:2px dashed var(--admin-border); }
    .password-section h4 { font-size:14px; color:var(--admin-primary); margin-bottom:10px; display:flex; align-items:center; gap:8px; }

    /* Confirm delete */
    .cd-backdrop { display:none; position:fixed; inset:0; background:rgba(20,15,10,0.6); z-index:9500; justify-content:center; align-items:center; }
    .cd-backdrop.open { display:flex; }
    .cd-modal { background:var(--admin-card); border-radius:16px; padding:32px; max-width:400px; width:90%; text-align:center; box-shadow:0 16px 48px rgba(0,0,0,0.3); }
    .cd-modal .warn-icon { font-size:44px; color:#e74c3c; margin-bottom:12px; }
    .cd-modal h3 { font-size:18px; margin-bottom:8px; }
    .cd-modal p { font-size:14px; color:var(--admin-text-muted); margin-bottom:24px; }
    .cd-actions { display:flex; gap:12px; justify-content:center; }
</style>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
<?php endif; ?>

<button class="btn-add-user" onclick="openAddUser()"><i class="fa-solid fa-user-plus"></i> Thêm người dùng</button>

<table class="user-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tên đăng nhập</th>
            <th>Vai trò</th>
            <th>Số điện thoại</th>
            <th>Địa chỉ</th>
            <th>Ngày tạo</th>
            <th class="td-actions">Xoá</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($users && $users->num_rows > 0): ?>
            <?php while ($u = $users->fetch_assoc()): ?>
                <tr onclick="openEditUser(this)"
                    data-id="<?= $u['ID'] ?>"
                    data-username="<?= htmlspecialchars($u['UserName'], ENT_QUOTES) ?>"
                    data-role="<?= $u['Role'] ?>"
                    data-phone="<?= htmlspecialchars($u['Phone'] ?? '', ENT_QUOTES) ?>"
                    data-address="<?= htmlspecialchars($u['Address'] ?? '', ENT_QUOTES) ?>">
                    <td>#<?= $u['ID'] ?></td>
                    <td><strong><?= htmlspecialchars($u['UserName']) ?></strong></td>
                    <td><span class="role-badge <?= $u['Role'] === 'admin' ? 'role-admin' : 'role-customer' ?>"><?= $u['Role'] === 'admin' ? 'Admin' : 'Khách hàng' ?></span></td>
                    <td><?= htmlspecialchars($u['Phone'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($u['Address'] ?? '—') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($u['Create_at'])) ?></td>
                    <td class="td-actions" onclick="event.stopPropagation()">
                        <button class="btn-delete-user" onclick="openDeleteUser(<?= $u['ID'] ?>, '<?= htmlspecialchars($u['UserName'], ENT_QUOTES) ?>')"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center; padding:30px; color:#999;">Chưa có người dùng nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- MODAL THÊM USER -->
<div id="addUserBackdrop" class="um-backdrop" onclick="if(event.target===this)closeAddUser()">
    <div class="um-modal">
        <div class="um-header">
            <h2><i class="fa-solid fa-user-plus"></i> Thêm người dùng mới</h2>
            <button class="um-close" onclick="closeAddUser()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="um-body">
                <div class="um-grid">
                    <div class="um-group full"><label>Tên đăng nhập <span style="color:#e74c3c">*</span></label><input type="text" name="username" required></div>
                    <div class="um-group full"><label>Mật khẩu <span style="color:#e74c3c">*</span></label><input type="password" name="password" required></div>
                    <div class="um-group"><label>Vai trò</label><select name="role"><option value="customer">Khách hàng</option><option value="admin">Admin</option></select></div>
                    <div class="um-group"><label>Số điện thoại</label><input type="text" name="phone"></div>
                    <div class="um-group full"><label>Địa chỉ</label><input type="text" name="address"></div>
                </div>
            </div>
            <div class="um-footer">
                <button type="button" class="um-btn-cancel" onclick="closeAddUser()">Huỷ</button>
                <button type="submit" class="um-btn-save"><i class="fa-solid fa-plus"></i> Thêm</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SỬA USER -->
<div id="editUserBackdrop" class="um-backdrop" onclick="if(event.target===this)closeEditUser()">
    <div class="um-modal">
        <div class="um-header">
            <h2><i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa người dùng</h2>
            <button class="um-close" onclick="closeEditUser()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="euId">
            <div class="um-body">
                <div class="um-grid">
                    <div class="um-group full"><label>Tên đăng nhập</label><input type="text" name="username" id="euUsername" required></div>
                    <div class="um-group"><label>Vai trò</label><select name="role" id="euRole"><option value="customer">Khách hàng</option><option value="admin">Admin</option></select></div>
                    <div class="um-group"><label>Số điện thoại</label><input type="text" name="phone" id="euPhone"></div>
                    <div class="um-group full"><label>Địa chỉ</label><input type="text" name="address" id="euAddress"></div>
                </div>
                <div class="password-section">
                    <h4><i class="fa-solid fa-key"></i> Đổi mật khẩu</h4>
                    <p style="font-size:13px;color:var(--admin-text-muted);margin-bottom:10px;">Điền đầy đủ 3 trường bên dưới để đổi mật khẩu.</p>
                    <div class="um-grid">
                        <div class="um-group full"><label>Mật khẩu cũ <span style="color:#e74c3c">*</span></label><input type="password" id="euOldPass" placeholder="Nhập mật khẩu hiện tại..."></div>
                        <div class="um-group"><label>Mật khẩu mới <span style="color:#e74c3c">*</span></label><input type="password" id="euNewPass" placeholder="Nhập mật khẩu mới..."></div>
                        <div class="um-group"><label>Xác nhận mật khẩu mới <span style="color:#e74c3c">*</span></label><input type="password" id="euNewPass2" placeholder="Nhập lại mật khẩu mới..."></div>
                    </div>
                    <button type="button" class="um-btn-save" style="margin-top:12px;background:var(--admin-primary-dark);" onclick="submitChangePassword()"><i class="fa-solid fa-key"></i> Đổi mật khẩu</button>
                </div>
            </div>
            <div class="um-footer">
                <button type="button" class="um-btn-cancel" onclick="closeEditUser()">Huỷ</button>
                <button type="submit" class="um-btn-save"><i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL XÁC NHẬN XOÁ -->
<div id="deleteUserBackdrop" class="cd-backdrop">
    <div class="cd-modal">
        <div class="warn-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3>Xác nhận xoá người dùng</h3>
        <p>Bạn sắp xoá tài khoản <strong id="duName"></strong>.<br>Hành động này <strong>không thể hoàn tác</strong>.</p>
        <div class="cd-actions">
            <button class="um-btn-cancel" onclick="closeDeleteUser()">Huỷ bỏ</button>
            <button class="btn-delete-user" style="padding:10px 24px;font-size:14px;" onclick="submitDeleteUser()"><i class="fa-solid fa-trash"></i> Xoá</button>
        </div>
    </div>
</div>
<form id="deleteUserForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="duId">
</form>

<!-- FORM ẨN ĐỔI MẬT KHẨU -->
<form id="changePassForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="change_password">
    <input type="hidden" name="id" id="cpId">
    <input type="hidden" name="old_password" id="cpOldPass">
    <input type="hidden" name="new_password" id="cpPass">
    <input type="hidden" name="new_password2" id="cpPass2">
</form>

<script>
function openAddUser() { document.getElementById('addUserBackdrop').classList.add('open'); document.body.style.overflow='hidden'; }
function closeAddUser() { document.getElementById('addUserBackdrop').classList.remove('open'); document.body.style.overflow=''; }

function openEditUser(row) {
    const d = row.dataset;
    document.getElementById('euId').value = d.id;
    document.getElementById('euUsername').value = d.username;
    document.getElementById('euRole').value = d.role;
    document.getElementById('euPhone').value = d.phone;
    document.getElementById('euAddress').value = d.address;
    document.getElementById('euOldPass').value = '';
    document.getElementById('euNewPass').value = '';
    document.getElementById('euNewPass2').value = '';
    document.getElementById('editUserBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeEditUser() { document.getElementById('editUserBackdrop').classList.remove('open'); document.body.style.overflow=''; }

function submitChangePassword() {
    const oldPass  = document.getElementById('euOldPass').value.trim();
    const newPass  = document.getElementById('euNewPass').value.trim();
    const newPass2 = document.getElementById('euNewPass2').value.trim();
    if (!oldPass) { alert('Vui lòng nhập mật khẩu cũ.'); return; }
    if (!newPass) { alert('Vui lòng nhập mật khẩu mới.'); return; }
    if (newPass !== newPass2) { alert('Mật khẩu mới nhập 2 lần không khớp nhau.'); return; }
    document.getElementById('cpId').value = document.getElementById('euId').value;
    document.getElementById('cpOldPass').value = oldPass;
    document.getElementById('cpPass').value = newPass;
    document.getElementById('cpPass2').value = newPass2;
    document.getElementById('changePassForm').submit();
}

let _deleteUserId = 0;
function openDeleteUser(id, name) {
    _deleteUserId = id;
    document.getElementById('duName').textContent = name;
    document.getElementById('deleteUserBackdrop').classList.add('open');
}
function closeDeleteUser() { document.getElementById('deleteUserBackdrop').classList.remove('open'); }
function submitDeleteUser() {
    document.getElementById('duId').value = _deleteUserId;
    document.getElementById('deleteUserForm').submit();
}
</script>
