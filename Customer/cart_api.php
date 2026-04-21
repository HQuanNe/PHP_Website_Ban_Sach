<?php
/**
 * cart_api.php — REST-like API endpoint cho Giỏ hàng
 * =====================================================
 * Nhận POST JSON hoặc POST form, trả về JSON.
 *
 * Các action:
 *   sync      — Khi vừa đăng nhập: đẩy giỏ guest (sessionStorage) lên DB
 *   get       — Lấy toàn bộ giỏ hàng DB của user hiện tại
 *   add       — Thêm/tăng số lượng 1 sản phẩm
 *   update    — Cập nhật số lượng 1 sản phẩm (qty=0 → xóa)
 *   remove    — Xóa 1 sản phẩm khỏi giỏ
 *   clear     — Xóa toàn bộ giỏ của user
 *
 * Nếu user chưa đăng nhập → trả lỗi 401 (giỏ hàng lưu client-side).
 */

if (session_status() === PHP_SESSION_NONE) session_start();
include '../Connect/connect.php';

header('Content-Type: application/json; charset=utf-8');

/* ── Helper: trả JSON rồi thoát ─────────────────────────────────── */
function json_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── Kiểm tra đăng nhập ─────────────────────────────────────────── */
$is_logged  = isset($_SESSION['user_id']);
$user_id    = $is_logged ? (int)$_SESSION['user_id'] : 0;

/* ── Đọc action ─────────────────────────────────────────────────── */
// Chấp nhận cả JSON body lẫn form-encoded
$raw    = file_get_contents('php://input');
$json   = json_decode($raw, true);
$action = $json['action'] ?? $_POST['action'] ?? '';

/* ────────────────────────────────────────────────────────────────────
   ACTION: get — Lấy danh sách giỏ hàng của user
   Trả về mảng items từ bảng giohang JOIN products
   ──────────────────────────────────────────────────────────────── */
if ($action === 'get') {
    if (!$is_logged) json_out(['items' => []]);   // guest → giỏ trống từ DB

    $res = $conn->query("
        SELECT g.ID, g.ID_Prod, g.Quantity, g.Total,
               p.Name, p.Price, p.Image_URL
        FROM giohang g
        JOIN products p ON p.ID = g.ID_Prod
        WHERE g.ID_User = $user_id
    ");
    $items = [];
    while ($r = $res->fetch_assoc()) {
        $items[] = [
            'id'    => (string)$r['ID_Prod'],
            'name'  => $r['Name'],
            'price' => (int)$r['Price'],
            'img'   => $r['Image_URL'],
            'qty'   => (int)$r['Quantity'],
        ];
    }
    json_out(['items' => $items]);
}

/* ────────────────────────────────────────────────────────────────────
   ACTION: sync — Đồng bộ giỏ guest (sessionStorage) lên DB
   Gọi sau khi user đăng nhập thành công.
   Payload: { action: "sync", items: [{id, qty}, ...] }
   Logic: Nếu sản phẩm đã có trong DB → cộng dồn qty.
          Nếu chưa → INSERT mới.
   ──────────────────────────────────────────────────────────────── */
if ($action === 'sync') {
    if (!$is_logged) json_out(['error' => 'Chưa đăng nhập'], 401);

    $items = $json['items'] ?? [];
    if (empty($items)) json_out(['ok' => true, 'synced' => 0]);

    $synced = 0;
    foreach ($items as $item) {
        $pid = (int)($item['id'] ?? 0);
        $qty = (int)($item['qty'] ?? 1);
        if ($pid <= 0 || $qty <= 0) continue;

        // Lấy giá hiện tại từ DB để tính Total
        $pr = $conn->query("SELECT Price FROM products WHERE ID = $pid");
        if (!$pr || !($prow = $pr->fetch_assoc())) continue;
        $price = (float)$prow['Price'];

        // Kiểm tra đã có trong giỏ chưa
        $ex = $conn->query("SELECT ID, Quantity FROM giohang WHERE ID_User = $user_id AND ID_Prod = $pid");
        if ($ex && $ex->num_rows > 0) {
            $erow    = $ex->fetch_assoc();
            $newQty  = (int)$erow['Quantity'] + $qty;
            $newTotal = $newQty * $price;
            $conn->query("UPDATE giohang SET Quantity=$newQty, Total=$newTotal WHERE ID={$erow['ID']}");
        } else {
            $total = $qty * $price;
            $conn->query("INSERT INTO giohang (ID_User, ID_Prod, Quantity, Total)
                          VALUES ($user_id, $pid, $qty, $total)");
        }
        $synced++;
    }

    json_out(['ok' => true, 'synced' => $synced]);
}

/* ────────────────────────────────────────────────────────────────────
   ACTION: add — Thêm hoặc tăng qty sản phẩm vào giỏ DB
   Payload: { action: "add", prod_id: N, qty: N }
   Chỉ dùng khi đã đăng nhập (guest tự xử lý phía client).
   ──────────────────────────────────────────────────────────────── */
if ($action === 'add') {
    if (!$is_logged) json_out(['error' => 'Chưa đăng nhập'], 401);

    $pid = (int)($json['prod_id'] ?? $_POST['prod_id'] ?? 0);
    $qty = max(1, (int)($json['qty'] ?? $_POST['qty'] ?? 1));

    $pr = $conn->query("SELECT Price FROM products WHERE ID = $pid");
    if (!$pr || !($prow = $pr->fetch_assoc())) json_out(['error' => 'Không tìm thấy sản phẩm'], 404);
    $price = (float)$prow['Price'];

    $ex = $conn->query("SELECT ID, Quantity FROM giohang WHERE ID_User=$user_id AND ID_Prod=$pid");
    if ($ex && $ex->num_rows > 0) {
        $erow   = $ex->fetch_assoc();
        $newQty = (int)$erow['Quantity'] + $qty;
        $conn->query("UPDATE giohang SET Quantity=$newQty, Total=".($newQty*$price)." WHERE ID={$erow['ID']}");
    } else {
        $conn->query("INSERT INTO giohang (ID_User,ID_Prod,Quantity,Total)
                      VALUES ($user_id,$pid,$qty,".($qty*$price).")");
    }
    json_out(['ok' => true]);
}

/* ────────────────────────────────────────────────────────────────────
   ACTION: update — Cập nhật số lượng (qty=0 → xóa row)
   Payload: { action: "update", prod_id: N, qty: N }
   ──────────────────────────────────────────────────────────────── */
if ($action === 'update') {
    if (!$is_logged) json_out(['error' => 'Chưa đăng nhập'], 401);

    $pid = (int)($json['prod_id'] ?? $_POST['prod_id'] ?? 0);
    $qty = (int)($json['qty']     ?? $_POST['qty']     ?? 0);

    if ($qty <= 0) {
        $conn->query("DELETE FROM giohang WHERE ID_User=$user_id AND ID_Prod=$pid");
    } else {
        $pr    = $conn->query("SELECT Price FROM products WHERE ID=$pid");
        $price = ($pr && ($r=$pr->fetch_assoc())) ? (float)$r['Price'] : 0;
        $conn->query("UPDATE giohang SET Quantity=$qty, Total=".($qty*$price)."
                      WHERE ID_User=$user_id AND ID_Prod=$pid");
    }
    json_out(['ok' => true]);
}

/* ────────────────────────────────────────────────────────────────────
   ACTION: remove — Xóa 1 sản phẩm khỏi giỏ DB
   Payload: { action: "remove", prod_id: N }
   ──────────────────────────────────────────────────────────────── */
if ($action === 'remove') {
    if (!$is_logged) json_out(['error' => 'Chưa đăng nhập'], 401);
    $pid = (int)($json['prod_id'] ?? $_POST['prod_id'] ?? 0);
    $conn->query("DELETE FROM giohang WHERE ID_User=$user_id AND ID_Prod=$pid");
    json_out(['ok' => true]);
}

/* ────────────────────────────────────────────────────────────────────
   ACTION: clear — Xóa toàn bộ giỏ hàng của user
   ──────────────────────────────────────────────────────────────── */
if ($action === 'clear') {
    if (!$is_logged) json_out(['error' => 'Chưa đăng nhập'], 401);
    $conn->query("DELETE FROM giohang WHERE ID_User=$user_id");
    json_out(['ok' => true]);
}

json_out(['error' => 'Action không hợp lệ'], 400);
