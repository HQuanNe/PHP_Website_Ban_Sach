<?php
/**
 * order_tracking.php — Trang Tra cứu đơn hàng
 */
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'Connect/connect.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo '<div style="max-width:600px; margin: 60px auto; text-align: center; padding: 40px; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">';
    echo '<i class="fa-solid fa-lock" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>';
    echo '<h2>Vui lòng đăng nhập</h2>';
    echo '<p style="color: #666; margin-top: 10px;">Bạn cần đăng nhập để xem lịch sử đơn hàng của mình.</p>';
    echo '</div>';
    exit;
}

// Fetch tất cả đơn hàng của User
$sql = "SELECT * FROM orders WHERE User_ID = $user_id ORDER BY Order_date DESC";
$orders_res = $conn->query($sql);

$active_orders = [];
$completed_orders = [];

if ($orders_res && $orders_res->num_rows > 0) {
    while ($order = $orders_res->fetch_assoc()) {
        $order_id = $order['ID'];
        
        // Lấy chi tiết đơn hàng
        $items = [];
        $detail_sql = "SELECT od.*, p.Name as product_name, p.Image_URL as Image 
                       FROM order_detail od 
                       JOIN products p ON od.Product_ID = p.ID 
                       WHERE od.Order_ID = $order_id";
        $detail_res = $conn->query($detail_sql);
        if ($detail_res) {
            while ($item = $detail_res->fetch_assoc()) {
                $items[] = $item;
            }
        }
        $order['items'] = $items;

        // Phân loại đơn hàng
        if (in_array($order['Status'], ['Chờ xử lý', 'Đang vận chuyển'])) {
            $active_orders[] = $order;
        } else {
            // Hoàn thành, Đã huỷ
            $completed_orders[] = $order;
        }
    }
}

function renderOrderList($orders) {
    if (empty($orders)) {
        return '<div class="empty-state"><i class="fa-solid fa-box-open"></i><p>Không có đơn hàng nào.</p></div>';
    }
    
    $html = '';
    foreach ($orders as $order) {
        $status_class = 'status-default';
        if ($order['Status'] === 'Hoàn thành') $status_class = 'status-success';
        if ($order['Status'] === 'Đã huỷ') $status_class = 'status-danger';
        if ($order['Status'] === 'Đang vận chuyển') $status_class = 'status-info';
        if ($order['Status'] === 'Chờ xử lý') $status_class = 'status-warning';

        $html .= '<div class="order-card">';
        $html .= '  <div class="order-header">';
        $html .= '      <div class="order-id">Đơn hàng #'.$order['ID'].' <span class="order-date">('.date('d/m/Y H:i', strtotime($order['Order_date'])).')</span></div>';
        $html .= '      <div class="order-status '.$status_class.'">'.$order['Status'].'</div>';
        $html .= '  </div>';
        
        $html .= '  <div class="order-items">';
        foreach ($order['items'] as $item) {
            $img = !empty($item['Image']) ? $item['Image'] : 'Resource/images/placeholder.png';
            $html .= '      <div class="order-item">';
            $html .= '          <img src="'.htmlspecialchars($img).'" alt="">';
            $html .= '          <div class="item-info">';
            $html .= '              <div class="item-name">'.htmlspecialchars($item['product_name']).'</div>';
            $html .= '              <div class="item-qty">x'.$item['Quantity'].'</div>';
            $html .= '          </div>';
            $html .= '          <div class="item-price">'.number_format($item['Price_at_order'], 0, ',', '.').'₫</div>';
            $html .= '      </div>';
        }
        $html .= '  </div>';

        $html .= '  <div class="order-footer">';
        $html .= '      <div class="order-total">Thành tiền: <span>'.number_format($order['Total_amount'], 0, ',', '.').'₫</span></div>';
        $html .= '  </div>';
        $html .= '</div>';
    }
    return $html;
}
?>

<style>
.tracking-wrapper {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
    color: var(--text-color);
}
.tracking-title {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 24px;
    color: var(--primary-color);
}
.tabs-header {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid var(--border-color);
}
.tab-btn {
    padding: 12px 24px;
    border: none;
    background: transparent;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-muted);
    cursor: pointer;
    position: relative;
    transition: color 0.2s;
}
.tab-btn:hover {
    color: var(--primary-color);
}
.tab-btn.active {
    color: var(--primary-color);
}
.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary-color);
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}

.order-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    overflow: hidden;
}
.order-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-color);
}
.order-id {
    font-weight: 700;
    font-size: 15px;
}
.order-date {
    color: var(--text-muted);
    font-weight: 400;
    font-size: 13px;
}
.order-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
}
.status-success { background: #e8f5e9; color: #2e7d32; }
.status-danger { background: #fce4e4; color: #c0392b; }
.status-info { background: #e3f2fd; color: #1565c0; }
.status-warning { background: #fff8e1; color: #f57f17; }
.status-default { background: #f5f5f5; color: #616161; }

.order-items {
    padding: 20px;
}
.order-item {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px dashed var(--border-color);
}
.order-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.order-item img {
    width: 60px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}
.item-info {
    flex: 1;
}
.item-name {
    font-weight: 600;
    font-size: 15px;
    margin-bottom: 4px;
}
.item-qty {
    font-size: 13px;
    color: var(--text-muted);
}
.item-price {
    font-weight: 700;
    color: var(--primary-color);
}
.order-footer {
    padding: 16px 20px;
    background: var(--bg-color);
    border-top: 1px solid var(--border-color);
    text-align: right;
}
.order-total {
    font-size: 15px;
    color: var(--text-muted);
}
.order-total span {
    font-size: 20px;
    font-weight: 800;
    color: #e65100;
    margin-left: 10px;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}
.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    color: #e0e0e0;
}
</style>

<div class="tracking-wrapper">
    <h2 class="tracking-title">Tra Cứu Đơn Hàng</h2>

    <div class="tabs-header">
        <button class="tab-btn active" onclick="switchTab('active_orders', this)">Đơn Đang Giao (<?= count($active_orders) ?>)</button>
        <button class="tab-btn" onclick="switchTab('completed_orders', this)">Đã Hoàn Thành (<?= count($completed_orders) ?>)</button>
    </div>

    <div id="active_orders" class="tab-content active">
        <?= renderOrderList($active_orders) ?>
    </div>

    <div id="completed_orders" class="tab-content">
        <?= renderOrderList($completed_orders) ?>
    </div>
</div>

<script>
function switchTab(tabId, btnElement) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    btnElement.classList.add('active');
}
</script>
