<?php
/**
 * checkout.php — Trang đặt hàng / thanh toán
 * ============================================
 * Hiển thị:
 *   - Banner đăng nhập (nếu là guest)
 *   - Form thông tin giao hàng (Họ tên, SĐT, email, địa chỉ)
 *   - Phương thức thanh toán (COD / Chuyển khoản / QR BIDV)
 *   - Ghi chú đơn hàng
 *   - Panel phải: Giỏ hàng + mã khuyến mãi + tóm tắt + nút đặt
 *
 * Khi submit POST → lưu vào bảng orders + order_detail + xóa giỏ DB.
 * Guest → chỉ lưu orders (User_ID = NULL).
 */

if (session_status() === PHP_SESSION_NONE) session_start();
include '../Connect/connect.php';

$is_logged = isset($_SESSION['user_id']);
$user_id   = $is_logged ? (int)$_SESSION['user_id'] : 0;

$success = '';
$error   = '';

/* ═══════════════════════════════════════════════════════════════
   XỬ LÝ POST — LƯU ĐƠN HÀNG
   ───────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items_json = $_POST['items_json'] ?? '[]';
    $items      = json_decode($items_json, true);

    $fullname  = $conn->real_escape_string(trim($_POST['fullname']  ?? ''));
    $phone     = $conn->real_escape_string(trim($_POST['phone']     ?? ''));
    $email     = $conn->real_escape_string(trim($_POST['email']     ?? ''));
    $addr      = $conn->real_escape_string(trim($_POST['address_detail'] ?? ''));
    $district  = $conn->real_escape_string(trim($_POST['district']  ?? ''));
    $payment   = $conn->real_escape_string(trim($_POST['payment']   ?? 'COD'));
    $note      = $conn->real_escape_string(trim($_POST['note']      ?? ''));

    if (empty($fullname) || empty($phone) || empty($addr)) {
        $error = 'Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ.';
    } elseif (empty($items)) {
        $error = 'Giỏ hàng trống, không thể đặt hàng.';
    } else {
        // Tính tổng tiền
        $total_amount = array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['qty'] ?? 1), $items));
        $shipping_addr = trim("$addr, $district", ", "); // Giữ địa chỉ đầy đủ

        // Ghi vào bảng orders
        $uid_sql = $user_id > 0 ? $user_id : 'NULL';
        
        $sql_order = "INSERT INTO orders (User_ID, Full_name, Phone, Payment_method, Note, Total_amount, Shipping_address, Status)
                      VALUES ($uid_sql, '$fullname', '$phone', '$payment', '$note', $total_amount, '$shipping_addr', 'Chờ xử lý')";

        if ($conn->query($sql_order)) {
            $order_id = $conn->insert_id;

            // Ghi chi tiết từng sản phẩm vào order_detail
            $ok = true;
            foreach ($items as $item) {
                $prod_id = intval($item['id']    ?? 0);
                $qty     = intval($item['qty']   ?? 1);
                $price   = floatval($item['price'] ?? 0);
                if ($prod_id <= 0) continue;
                $sql_det = "INSERT INTO order_detail (Order_ID, Product_ID, Quantity, Price_at_order)
                            VALUES ($order_id, $prod_id, $qty, $price)";
                if (!$conn->query($sql_det)) { $ok = false; break; }
            }

            if ($ok) {
                // Xóa giỏ DB nếu đã login
                if ($user_id > 0) {
                    $conn->query("DELETE FROM giohang WHERE ID_User = $user_id");
                }
                $success = "Đặt hàng thành công!";
            } else {
                $error = 'Lỗi khi lưu chi tiết đơn hàng: ' . $conn->error;
            }
        } else {
            $error = 'Không thể tạo đơn hàng: ' . $conn->error;
        }
    }
}

// Lấy thông tin user đăng nhập để điền sẵn form
$user_info = null;
if ($is_logged) {
    $res = $conn->query("SELECT UserName, Phone, Address FROM users WHERE ID = $user_id");
    if ($res) $user_info = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Dream Book</title>
    <meta name="user-logged" content="<?= $is_logged ? '1' : '0' ?>">
    <link rel="icon" href="../Resource/Image/Logo/LogoNoText.webp">
    <link rel="stylesheet" href="../CSS/index.css">
    <link rel="stylesheet" href="../CSS/default.css">
    <link rel="stylesheet" href="../CSS/cart.css">
    <link rel="stylesheet" href="../Resource/FontAwesome/fontawesome-free-7.2.0-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ─────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background-color, #f5ede4);
            color: var(--color-5, #3e3a34);
            min-height: 100vh;
        }

        /* ── Topbar (header mini cho trang checkout) ───────── */
        .ck-topbar {
            background: var(--white-color, #fff);
            border-bottom: 1px solid var(--color-2, #e8e0d5);
            padding: 14px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .ck-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 20px;
            font-weight: 800;
            color: var(--color-5, #3e3a34);
        }
        .ck-logo i { color: var(--color-4, #7a6f63); font-size: 22px; }
        .ck-back {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-4, #7a6f63);
            text-decoration: none;
            transition: color 0.2s;
        }
        .ck-back:hover { color: var(--color-5, #3e3a34); }

        /* ── Layout chính 2 cột ───────────────────────────── */
        .ck-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 28px;
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 24px 60px;
            align-items: start;
        }
        @media (max-width: 860px) {
            .ck-layout { grid-template-columns: 1fr; }
        }

        /* ── Card chung ────────────────────────────────────── */
        .ck-card {
            background: var(--white-color, #fff);
            border-radius: 16px;
            padding: 22px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 16px;
        }
        .ck-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--color-5, #3e3a34);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── Banner đăng nhập (cho guest) ─────────────────── */
        .ck-login-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: linear-gradient(135deg, #fff8f2, #fdeee0);
            border: 1px solid #f5d8c0;
            border-radius: 12px;
            margin-bottom: 16px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .ck-login-banner span { font-size: 13.5px; color: var(--color-4, #7a6f63); }
        .ck-login-banner a {
            padding: 8px 20px;
            background: var(--color-4, #7a6f63);
            color: #fff;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: opacity 0.2s;
        }
        .ck-login-banner a:hover { opacity: 0.85; }

        /* ── Form fields ───────────────────────────────────── */
        .ck-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .ck-form-grid .full { grid-column: 1 / -1; }
        .ck-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .ck-field label {
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--color-4, #7a6f63);
        }
        .ck-field input, .ck-field select, .ck-field textarea {
            padding: 11px 14px;
            border: 1.5px solid var(--color-2, #e8e0d5);
            border-radius: 10px;
            font-size: 14px;
            background: #faf7f4;
            color: var(--color-5, #3e3a34);
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .ck-field input:focus, .ck-field select:focus, .ck-field textarea:focus {
            outline: none;
            border-color: var(--color-4, #7a6f63);
            box-shadow: 0 0 0 3px rgba(122,111,99,0.12);
            background: #fff;
        }
        .ck-field textarea { resize: vertical; min-height: 80px; }

        /* ── Phương thức thanh toán — radio cards ──────────── */
        .payment-options { display: flex; flex-direction: column; gap: 10px; }
        .payment-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1.5px solid var(--color-2, #e8e0d5);
            border-radius: 12px;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }
        .payment-option:has(input:checked) {
            border-color: var(--color-4, #7a6f63);
            background: rgba(122,111,99,0.05);
        }
        .payment-option input[type=radio] {
            accent-color: var(--color-4, #7a6f63);
            width: 18px; height: 18px;
            flex-shrink: 0;
        }
        .payment-option-icon {
            width: 36px; height: 36px;
            border-radius: 8px;
            background: var(--background-color, #f5ede4);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            color: var(--color-4, #7a6f63);
            flex-shrink: 0;
        }
        .payment-option-text { flex: 1; }
        .payment-option-title { font-size: 14px; font-weight: 600; }
        .payment-option-sub   { font-size: 12px; color: #aaa; margin-top: 2px; }

        /* QR section (ẩn mặc định, hiện khi chọn QR) */
        #qrSection {
            display: none;
            margin-top: 12px;
            padding: 16px;
            background: var(--background-color, #f5ede4);
            border-radius: 12px;
            text-align: center;
        }
        #qrSection img {
            width: 160px; height: 160px;
            border-radius: 8px;
            border: 2px solid var(--color-2, #e8e0d5);
            margin-bottom: 8px;
            background: #fff;
        }
        #qrSection p { font-size: 13px; color: var(--color-4, #7a6f63); }
        #qrSection strong { display: block; font-size: 15px; font-weight: 700; color: var(--color-5, #3e3a34); }

        /* ── Panel phải: Giỏ hàng review ──────────────────── */
        .ck-right { position: sticky; top: 80px; }

        /* Danh sách sản phẩm trong panel đặt hàng */
        .order-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--color-2, #e8e0d5);
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-img {
            width: 52px; height: 64px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            background: var(--color-2, #e8e0d5);
        }
        .order-item-img.no-img {
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #bbb;
        }
        .order-item-info { flex: 1; min-width: 0; }
        .order-item-name {
            font-size: 13px; font-weight: 600;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .order-item-qty { font-size: 12px; color: #aaa; margin-top: 2px; }
        .order-item-price {
            font-size: 13px; font-weight: 700;
            color: rgb(200,50,50); white-space: nowrap;
        }

        /* Tóm tắt đơn hàng */
        .ck-summary { border-top: 1px solid var(--color-2, #e8e0d5); margin-top: 12px; padding-top: 14px; }
        .ck-summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13.5px;
            margin-bottom: 8px;
            color: var(--color-4, #7a6f63);
        }
        .ck-summary-row.total {
            font-size: 17px;
            font-weight: 800;
            color: var(--color-5, #3e3a34);
            margin-top: 4px;
        }
        .ck-summary-row.total span:last-child { color: rgb(200,50,50); }

        /* Mã khuyến mãi */
        .ck-coupon {
            display: flex;
            gap: 8px;
            margin: 14px 0;
        }
        .ck-coupon input {
            flex: 1;
            padding: 10px 14px;
            border: 1.5px solid var(--color-2, #e8e0d5);
            border-radius: 10px;
            font-size: 13.5px;
            background: #faf7f4;
            font-family: inherit;
        }
        .ck-coupon input:focus {
            outline: none;
            border-color: var(--color-4, #7a6f63);
        }
        .ck-coupon-btn {
            padding: 10px 18px;
            background: var(--color-5, #3e3a34);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity 0.2s;
        }
        .ck-coupon-btn:hover { opacity: 0.85; }

        /* Nút đặt hàng */
        .ck-place-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--color-4,#7a6f63), var(--color-5,#3e3a34));
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: opacity 0.2s, transform 0.2s;
            box-shadow: 0 4px 16px rgba(62,58,52,0.3);
            letter-spacing: 0.3px;
            margin-top: 10px;
        }
        .ck-place-btn:hover { opacity: 0.88; transform: translateY(-1px); }

        /* Thông báo */
        .ck-alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
        }
        .ck-alert.success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .ck-alert.error   { background: #fce4e4; color: #c0392b; border: 1px solid #f1998e; }

        /* Empty cart redirect */
        .ck-empty {
            text-align: center;
            padding: 60px 20px;
            color: #bbb;
        }
        .ck-empty i { font-size: 56px; display: block; margin-bottom: 14px; }
        
        /* ── POPUP THÀNH CÔNG ────────────────────────────────────── */
        .success-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); z-index: 9999;
            display: none; align-items: center; justify-content: center;
        }
        .success-modal.active { display: flex; animation: fadeIn 0.3s ease; }
        .success-modal-content {
            background: #fff; padding: 40px; border-radius: 20px;
            text-align: center; max-width: 400px; width: 90%;
            transform: scale(0.8); opacity: 0;
            animation: popIn 0.4s 0.1s forwards cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .success-icon {
            width: 80px; height: 80px; border-radius: 50%;
            background: #4caf50; color: #fff; font-size: 40px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; position: relative;
        }
        .success-icon-circle {
            position: absolute; top: -10px; left: -10px; right: -10px; bottom: -10px;
            border: 2px solid #4caf50; border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        .success-modal h2 { color: #333; margin-bottom: 10px; font-size: 24px; }
        .success-modal p { color: #666; margin-bottom: 5px; font-size: 15px; }
        .success-modal .sub-msg { font-size: 13px; color: #999; margin-bottom: 25px; }
        .success-modal .btn-ok {
            background: #4caf50; color: #fff; border: none; padding: 12px 40px;
            border-radius: 10px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: background 0.2s;
        }
        .success-modal .btn-ok:hover { background: #43a047; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { to { transform: scale(1); opacity: 1; } }
        @keyframes pulse { 0% { transform: scale(0.8); opacity: 0.8; } 100% { transform: scale(1.3); opacity: 0; } }
    </style>
</head>
<body>

<!-- ══ TOPBAR ══════════════════════════════════════════════════ -->
<div class="ck-topbar">
    <a class="ck-logo" href="../index.php">
        <i class="fa-solid fa-book-open-reader"></i>
        Dream Book
    </a>
    <a class="ck-back" href="../index.php">
        <i class="fa-solid fa-arrow-left"></i> Tiếp tục mua sắm
    </a>
</div>

<!-- ══ LAYOUT CHÍNH ════════════════════════════════════════════ -->
<div class="ck-layout">

    <!-- ── CỘT TRÁI: Form thông tin ──────────────────────────── -->
    <div class="ck-left">

        <?php if ($success): ?>
            <!-- Đặt hàng thành công → hiện Popup thay vì alert -->
            <div id="successPopup" class="success-modal active">
                <div class="success-modal-content">
                    <div class="success-icon">
                        <div class="success-icon-circle"></div>
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h2>Đặt hàng thành công!</h2>
                    <p>Mã đơn hàng của bạn là <strong>#<?= $order_id ?? '' ?></strong></p>
                    <p class="sub-msg">Cảm ơn bạn đã mua hàng tại Dream Book.</p>
                    <button class="btn-ok" onclick="window.location.href='../index.php'">OK</button>
                </div>
            </div>
            
            <!-- Đặt thẻ div ẩn toàn bộ form khi popup hiện -->
            <div style="display:none;">
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="ck-alert error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Banner đăng nhập (chỉ hiện với guest) -->
        <?php if (!$is_logged): ?>
        <div class="ck-login-banner">
            <span><i class="fa-solid fa-circle-info"></i> Đăng nhập để mua hàng tiện lợi và nhận nhiều ưu đãi hơn nữa</span>
            <a href="#" onclick="document.getElementById('authModal')?.classList.add('active'); return false;">Đăng nhập</a>
        </div>
        <?php endif; ?>

        <!-- Form thông tin giao hàng -->
        <form method="POST" id="checkoutForm">
            <!-- Hidden: giỏ hàng JSON từ JS sẽ điền vào đây -->
            <input type="hidden" name="items_json" id="itemsJsonInput">

            <div class="ck-card">
                <div class="ck-card-title">
                    <i class="fa-solid fa-location-dot"></i> Thông tin giao hàng
                </div>
                <div class="ck-form-grid">
                    <div class="ck-field full">
                        <label>Họ và tên <span style="color:#e74c3c">*</span></label>
                        <input type="text" name="fullname" id="fullname" placeholder="Nhập họ và tên"
                               value="<?= htmlspecialchars($user_info['UserName'] ?? '') ?>" required>
                    </div>
                    <div class="ck-field">
                        <label>Số điện thoại <span style="color:#e74c3c">*</span></label>
                        <input type="tel" name="phone" id="phone" placeholder="0xxx xxx xxx"
                               value="<?= htmlspecialchars($user_info['Phone'] ?? '') ?>" required>
                    </div>
                    <div class="ck-field">
                        <label>Email (không bắt buộc)</label>
                        <input type="email" name="email" id="email" placeholder="email@example.com">
                    </div>
                    <div class="ck-field full">
                        <label>Quốc gia</label>
                        <input type="text" value="Việt Nam" readonly style="background:#f0f0f0;cursor:default;">
                    </div>
                    <div class="ck-field full">
                        <label>Tỉnh/TP, Quận/Huyện, Phường/Xã <span style="color:#e74c3c">*</span></label>
                        <input type="text" name="district" id="district"
                               placeholder="VD: TP.HCM, Quận 1, Phường Bến Nghé"
                               value="<?= htmlspecialchars($user_info['Address'] ?? '') ?>" require>
                    </div>
                    <div class="ck-field full">
                        <label>Địa chỉ, tên đường <span style="color:#e74c3c">*</span></label>
                        <input type="text" name="address_detail" id="address_detail"
                               placeholder="Số nhà, tên đường..." required>
                    </div>
                </div>
            </div>

            <!-- Phương thức thanh toán -->
            <div class="ck-card">
                <div class="ck-card-title">
                    <i class="fa-solid fa-credit-card"></i> Phương thức thanh toán
                </div>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment" value="COD" checked onchange="handlePaymentChange(this)">
                        <div class="payment-option-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                        <div class="payment-option-text">
                            <div class="payment-option-title">Thanh toán khi giao hàng (COD)</div>
                            <div class="payment-option-sub">Trả tiền mặt khi nhận hàng</div>
                        </div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment" value="BANK" onchange="handlePaymentChange(this)">
                        <div class="payment-option-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="payment-option-text">
                            <div class="payment-option-title">Chuyển khoản qua ngân hàng</div>
                            <div class="payment-option-sub">Thông tin sẽ được gửi qua email sau khi đặt</div>
                        </div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment" value="QR_BIDV" onchange="handlePaymentChange(this)">
                        <div class="payment-option-icon"><i class="fa-solid fa-qrcode"></i></div>
                        <div class="payment-option-text">
                            <div class="payment-option-title">Chuyển khoản qua QR</div>
                            <div class="payment-option-sub">Quét mã QR thanh toán ngay</div>
                        </div>
                    </label>
                </div>

                <!-- QR Section (hiện khi chọn QR BIDV) -->
                <div id="qrSection">
                    <img src="../Resource/Image/qr_placeholder.png"
                         onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=BIDV-DREAMBOOK'"
                         alt="QR Code BIDV">
                    <strong>BIDV – Dream Book Store</strong>
                    <p>STK: <strong>12345678901</strong> | Chi nhánh: Hà Nội</p>
                    <p style="margin-top:6px;">Nội dung CK: <strong id="qrNote">DH_[Mã đơn hàng]</strong></p>
                </div>
            </div>

            <!-- Ghi chú đơn hàng -->
            <div class="ck-card">
                <div class="ck-card-title">
                    <i class="fa-solid fa-note-sticky"></i> Ghi chú đơn hàng
                </div>
                <div class="ck-field" style="margin-top:14px;">
                    <textarea name="note" placeholder="Ghi chú thêm về đơn hàng (không bắt buộc)..."></textarea>
                </div>
            </div>
        </form>

        <?php if ($success): ?>
            </div> <!-- Đóng thẻ div ẩn form -->
        <?php endif; ?>

    </div><!-- /.ck-left -->

    <!-- ── CỘT PHẢI: Tóm tắt đơn hàng ────────────────────────── -->
    <div class="ck-right">

        <!-- Giỏ hàng review -->
        <div class="ck-card">
            <div class="ck-card-title">
                <i class="fa-solid fa-cart-shopping"></i> Giỏ hàng
            </div>
            <!-- JS render vào đây -->
            <div id="orderItemsList">
                <div style="text-align:center;color:#ccc;padding:20px 0;">
                    <i class="fa-solid fa-spinner fa-spin"></i> Đang tải giỏ hàng...
                </div>
            </div>
        </div>

        <!-- Mã khuyến mãi -->
        <div class="ck-card">
            <div class="ck-card-title">
                <i class="fa-solid fa-tag"></i> Mã khuyến mãi
            </div>
            <div class="ck-coupon">
                <input type="text" id="couponInput" placeholder="Nhập mã khuyến mãi">
                <button class="ck-coupon-btn" onclick="applyCoupon()">Áp dụng</button>
            </div>
            <div id="couponMsg" style="font-size:13px;color:#e74c3c;display:none;"></div>
        </div>

        <!-- Tóm tắt -->
        <div class="ck-card">
            <div class="ck-card-title">
                <i class="fa-solid fa-receipt"></i> Tóm tắt đơn hàng
            </div>
            <div class="ck-summary">
                <div class="ck-summary-row">
                    <span>Tổng tiền hàng</span>
                    <span id="summarySubtotal">0 ₫</span>
                </div>
                <div class="ck-summary-row">
                    <span>Phí vận chuyển</span>
                    <span style="color:#2e7d32;font-weight:600;">Miễn phí</span>
                </div>
                <div class="ck-summary-row">
                    <span>Giảm giá</span>
                    <span id="summaryDiscount" style="color:#2e7d32;">—</span>
                </div>
                <div class="ck-summary-row total">
                    <span>Tổng thanh toán</span>
                    <span id="summaryTotal">0 ₫</span>
                </div>
            </div>

            <?php if (!$success): ?>
            <button type="button" class="ck-place-btn" onclick="submitOrder()">
                <i class="fa-solid fa-bag-shopping"></i> Đặt hàng
            </button>
            <?php endif; ?>
        </div>

    </div><!-- /.ck-right -->
</div><!-- /.ck-layout -->

<script src="../JS/cart.js"></script>
<script>
/**
 * ================================================================
 *  checkout.js (inline) — Logic trang thanh toán
 *  A. renderOrderItems() — Hiển thị giỏ hàng từ sessionStorage
 *  B. updateSummary()    — Tính và hiển thị tổng tiền
 *  C. submitOrder()      — Điền JSON vào hidden input, submit form
 *  D. handlePaymentChange() — Hiện/ẩn QR section
 *  E. applyCoupon()      — Áp dụng mã KM (mock)
 * ================================================================
 */

let _discount = 0; // Giá trị giảm (VND) từ mã KM

/* ── A. Render giỏ hàng bên phải ──────────────────────────── */
function renderOrderItems() {
    const items = cartLoad();
    const wrap  = document.getElementById('orderItemsList');
    if (!wrap) return;

    if (!items || items.length === 0) {
        wrap.innerHTML = `
            <div style="text-align:center;color:#bbb;padding:24px 0;">
                <i class="fa-solid fa-cart-shopping" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                Giỏ hàng trống
            </div>`;
        updateSummary(0);
        return;
    }

    let html = '';
    let subtotal = 0;
    items.forEach(item => {
        const linePrice = item.price * item.qty;
        subtotal += linePrice;
        const imgHtml = item.img
            ? `<img class="order-item-img" src="../${item.img}" alt="">`
            : `<div class="order-item-img no-img"><i class="fa-solid fa-book"></i></div>`;
        html += `
            <div class="order-item">
                ${imgHtml}
                <div class="order-item-info">
                    <div class="order-item-name">${escHtml(item.name)}</div>
                    <div class="order-item-qty">SL: ${item.qty}</div>
                </div>
                <div class="order-item-price">${linePrice.toLocaleString('vi-VN')} ₫</div>
            </div>`;
    });
    wrap.innerHTML = html;
    updateSummary(subtotal);
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── B. Cập nhật tóm tắt ──────────────────────────────────── */
function updateSummary(subtotal) {
    const fmt = v => v.toLocaleString('vi-VN') + ' ₫';
    document.getElementById('summarySubtotal').textContent = fmt(subtotal);

    const discEl = document.getElementById('summaryDiscount');
    if (_discount > 0) {
        discEl.textContent = '−' + fmt(_discount);
    } else {
        discEl.textContent = '—';
    }

    const total = Math.max(0, subtotal - _discount);
    document.getElementById('summaryTotal').textContent = fmt(total);
}

/* ── C. Submit form đặt hàng ──────────────────────────────── */
function submitOrder() {
    const items = cartLoad();
    if (!items || items.length === 0) {
        alert('Giỏ hàng của bạn đang trống!');
        return;
    }
    // Điền JSON giỏ vào hidden input
    document.getElementById('itemsJsonInput').value = JSON.stringify(items);
    document.getElementById('checkoutForm').submit();
}

/* ── D. Hiện/ẩn QR section ────────────────────────────────── */
function handlePaymentChange(radio) {
    const qr = document.getElementById('qrSection');
    qr.style.display = radio.value === 'QR_BIDV' ? 'block' : 'none';
}

/* ── E. Mã khuyến mãi (mock — mở rộng sau) ──────────────── */
function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim().toUpperCase();
    const msg  = document.getElementById('couponMsg');
    // Mock: mã DREAMBOOK → giảm 20.000 đ
    if (code === 'DREAMBOOK') {
        _discount = 20000;
        msg.style.color = '#2e7d32';
        msg.textContent = '✓ Áp dụng mã thành công! Giảm 20.000 ₫';
        msg.style.display = 'block';
    } else if (code === '') {
        msg.textContent = '';
        msg.style.display = 'none';
    } else {
        _discount = 0;
        msg.style.color = '#e74c3c';
        msg.textContent = 'Mã khuyến mãi không hợp lệ!';
        msg.style.display = 'block';
    }
    renderOrderItems();
}

/* ── KHỞI TẠO ────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    // Nếu vừa reload trang sau khi login, tải lại giỏ từ sessionStorage
    renderOrderItems();

    <?php if ($success): ?>
    // Đặt hàng thành công → xóa giỏ phía client
    sessionStorage.removeItem('dreambook_cart');
    <?php endif; ?>
});
</script>
</body>
</html>
