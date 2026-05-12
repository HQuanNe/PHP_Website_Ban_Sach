<?php
/**
 * voucher_api.php — API xác thực voucher
 * GET: ?code=XXXX&subtotal=YYYY
 * Trả JSON: { valid, type, value, discount_amount, message }
 */
include '../Connect/connect.php';
header('Content-Type: application/json');

$code     = strtoupper(trim($_GET['code'] ?? ''));
$subtotal = floatval($_GET['subtotal'] ?? 0);
$today    = date('Y-m-d');

if (empty($code)) {
    echo json_encode(['valid' => false, 'message' => 'Vui lòng nhập mã voucher.']);
    exit;
}

$escaped = $conn->real_escape_string($code);
$result  = $conn->query("SELECT * FROM vouchers WHERE Code = '$escaped' LIMIT 1");

if (!$result || $result->num_rows === 0) {
    echo json_encode(['valid' => false, 'message' => 'Mã voucher không tồn tại.']);
    exit;
}

$v = $result->fetch_assoc();

// Kiểm tra trạng thái
if ($v['Status'] !== 'active') {
    echo json_encode(['valid' => false, 'message' => 'Mã voucher đã bị vô hiệu hoá.']);
    exit;
}

// Kiểm tra ngày hiệu lực
if ($today < $v['Start_date']) {
    echo json_encode(['valid' => false, 'message' => 'Mã voucher chưa đến ngày áp dụng.']);
    exit;
}
if ($today > $v['End_date']) {
    echo json_encode(['valid' => false, 'message' => 'Mã voucher đã hết hạn.']);
    exit;
}

// Kiểm tra lượt dùng
if ($v['Quantity'] >= 0 && $v['Used'] >= $v['Quantity']) {
    echo json_encode(['valid' => false, 'message' => 'Mã voucher đã hết lượt sử dụng.']);
    exit;
}

// Kiểm tra đơn tối thiểu
if ($subtotal < $v['Min_order']) {
    $min_fmt = number_format($v['Min_order'], 0, ',', '.');
    echo json_encode(['valid' => false, 'message' => "Đơn hàng tối thiểu {$min_fmt}₫ để áp dụng mã này."]);
    exit;
}

// Tính giảm giá
$discount = 0;
if ($v['Type'] === 'percent') {
    $discount = $subtotal * ($v['Value'] / 100);
    if ($v['Max_discount'] !== null && $discount > $v['Max_discount']) {
        $discount = $v['Max_discount'];
    }
} else {
    $discount = $v['Value'];
}
$discount = min($discount, $subtotal); // Không giảm quá tổng đơn

$disc_fmt = number_format($discount, 0, ',', '.');
$label = $v['Type'] === 'percent' 
    ? "Giảm {$v['Value']}%" . ($v['Max_discount'] ? " (tối đa " . number_format($v['Max_discount'],0,',','.') . "₫)" : '')
    : "Giảm " . number_format($v['Value'],0,',','.') . "₫";

echo json_encode([
    'valid'           => true,
    'type'            => $v['Type'],
    'value'           => $v['Value'],
    'discount_amount' => $discount,
    'message'         => "✓ Áp dụng thành công! $label — Bạn được giảm {$disc_fmt}₫"
]);
