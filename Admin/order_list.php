<?php
/**
 * order_list.php — Quản lý Đơn hàng (Admin)
 * ============================================
 */
require_once '../Connect/connect.php';

// Xử lý AJAX cho các trình duyệt còn lưu cache JS cũ (gọi thẳng vào order_list.php)
if (isset($_GET['ajax_details'])) {
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

// Xử lý xoá đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $check = $conn->query("SELECT Status FROM orders WHERE ID = $order_id");
    if ($check && $row = $check->fetch_assoc()) {
        if ($row['Status'] === 'Đã huỷ') {
            $conn->query("DELETE FROM order_detail WHERE Order_ID = $order_id");
            $conn->query("DELETE FROM orders WHERE ID = $order_id");
        }
    }
    $tab = $_GET['tab'] ?? 'all';
    echo "<script>window.location.href='admin_dashboard.php?page=order_list&tab=$tab';</script>";
    exit;
}

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status   = $conn->real_escape_string($_POST['status'] ?? '');
    if ($order_id > 0 && in_array($status, ['Chờ xử lý', 'Đang vận chuyển', 'Hoàn thành', 'Đã huỷ'])) {
        $updateSql = "UPDATE orders SET Status = '$status'";
        if ($status === 'Hoàn thành') {
            $updateSql .= ", Received_date = NOW()";
        }
        $updateSql .= " WHERE ID = $order_id";
        $conn->query($updateSql);
    }
    $tab = $_GET['tab'] ?? 'all';
    echo "<script>window.location.href='admin_dashboard.php?page=order_list&tab=$tab';</script>";
    exit;
}

// Tự động cập nhật Schema Database để hỗ trợ trạng thái mới
$conn->query("ALTER TABLE orders MODIFY Status ENUM('Chờ xử lý', 'Đang vận chuyển', 'Hoàn thành', 'Đã huỷ') DEFAULT 'Chờ xử lý'");

// Lấy danh sách đơn hàng theo Tab
$current_tab = $_GET['tab'] ?? 'all';
$status_filter = "";
if ($current_tab === 'pending') $status_filter = "WHERE o.Status = 'Chờ xử lý'";
elseif ($current_tab === 'shipping') $status_filter = "WHERE o.Status = 'Đang vận chuyển'";
elseif ($current_tab === 'completed') $status_filter = "WHERE o.Status = 'Hoàn thành'";
elseif ($current_tab === 'cancelled') $status_filter = "WHERE o.Status = 'Đã huỷ'";

$sql_orders = "SELECT o.*, u.UserName as account_name 
               FROM orders o 
               LEFT JOIN users u ON o.User_ID = u.ID 
               $status_filter
               ORDER BY o.Order_date DESC";
$orders_result = $conn->query($sql_orders);

// Hàm lấy chi tiết một đơn hàng
function getOrderDetails($conn, $order_id) {
    $sql = "SELECT od.*, p.Name as product_name, p.Image_URL as Image 
            FROM order_detail AS od 
            JOIN products AS p ON od.Product_ID = p.ID 
            WHERE od.Order_ID = $order_id";
    return $conn->query($sql);
}
?>
<div class="nav-tabs-container" style="margin-bottom: 20px;">
    <?php 
    $tabs = [
        'all' => 'Tất cả',
        'pending' => 'Chờ xử lý',
        'shipping' => 'Đang vận chuyển',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã huỷ'
    ];
    foreach ($tabs as $key => $label): 
        $activeClass = ($current_tab === $key) ? 'btn-primary' : 'btn-outline-primary';
    ?>
        <a href="admin_dashboard.php?page=order_list&tab=<?= $key ?>" class="btn <?= $activeClass ?>" style="margin-right: 5px;">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Mã ĐH</th>
                <th>Khách hàng</th>
                <th>Thời gian</th>
                <th>Tổng tiền</th>
                <th>Phương thức</th>
                <th>Trạng thái</th>
                <th style="min-width: 250px;">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <?php while ($row = $orders_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $row['ID'] ?></strong></td>
                        <td>
                            <?= htmlspecialchars($row['Full_name'] ?? 'Guest') ?><br>
                            <small style="color: var(--admin-text-muted);"><?= htmlspecialchars($row['Phone'] ?? '') ?></small>
                        </td>
                        <td>
                            <div style="margin-bottom: 4px;"><span style="color: var(--admin-text-muted);">Đặt:</span> <?= date('d/m/Y H:i', strtotime($row['Order_date'])) ?></div>
                            <?php if (!empty($row['Received_date'])): ?>
                                <div style="color: var(--admin-success);"><span>Nhận:</span> <?= date('d/m/Y H:i', strtotime($row['Received_date'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; color: var(--admin-danger);"><?= number_format($row['Total_amount'] ?? 0, 0, ',', '.') ?> ₫</td>
                        <td><?= htmlspecialchars($row['Payment_method'] ?? 'COD') ?></td>
                        <td>
                            <?php 
                                $statusClass = 'badge-secondary';
                                if ($row['Status'] === 'Đang vận chuyển') $statusClass = 'badge-warning';
                                if ($row['Status'] === 'Hoàn thành') $statusClass = 'badge-success';
                                if ($row['Status'] === 'Đã huỷ') $statusClass = 'badge-danger';
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($row['Status']) ?></span>
                        </td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick='openModal(<?= json_encode($row) ?>)' style="margin-right: 5px;">Chi tiết</button>
                            
                            <?php if ($row['Status'] === 'Chờ xử lý'): ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $row['ID'] ?>">
                                    <input type="hidden" name="status" value="Đang vận chuyển">
                                    <button type="submit" class="btn btn-primary btn-sm" style="margin-right: 5px;">Xác nhận</button>
                                </form>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $row['ID'] ?>">
                                    <input type="hidden" name="status" value="Đã huỷ">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn chắc chắn muốn huỷ đơn này?');">Huỷ</button>
                                </form>
                            <?php elseif ($row['Status'] === 'Đang vận chuyển'): ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $row['ID'] ?>">
                                    <input type="hidden" name="status" value="Hoàn thành">
                                    <button type="submit" class="btn btn-success btn-sm">Xác nhận đã giao</button>
                                </form>
                            <?php elseif ($row['Status'] === 'Đã huỷ'): ?>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="order_id" value="<?= $row['ID'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn xoá vĩnh viễn đơn hàng này không?');">Xoá đơn hàng</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center;">Chưa có đơn hàng nào!</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<!-- Modal Chi tiết Đơn hàng -->
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Chi tiết đơn hàng #...</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            
            <div class="detail-grid">
                <div class="detail-item"><strong>Người nhận:</strong> <span id="dtName"></span></div>
                <div class="detail-item"><strong>Số điện thoại:</strong> <span id="dtPhone"></span></div>
                <div class="detail-item"><strong>Địa chỉ giao:</strong> <span id="dtAddress"></span></div>
                <div class="detail-item"><strong>Ngày đặt:</strong> <span id="dtDate"></span></div>
                <div class="detail-item" id="dtReceivedRow" style="display:none;"><strong>Ngày nhận:</strong> <span id="dtReceived" style="color: var(--admin-success); font-weight:600;"></span></div>
                <div class="detail-item"><strong>Phương thức:</strong> <span id="dtPayment"></span></div>
                <div class="detail-item"><strong>Ghi chú:</strong> <span id="dtNote"></span></div>
                <div class="detail-item" style="grid-column: 1 / -1; margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                    <strong style="margin-right: 10px;">Trạng thái đơn:</strong>
                    <span id="dtStatusBadge" class="badge"></span>
                </div>
            </div>

            <h3 style="margin-bottom: 15px; font-size: 16px; border-bottom: 2px solid var(--admin-primary); display: inline-block; padding-bottom: 5px;">Sản phẩm đã đặt</h3>
            <div id="loadingDetails" style="text-align: center; display: none;"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải...</div>
            <table class="table" style="margin-top: 0;">
                <thead>
                    <tr>
                        <th style="width: 60px;">Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody id="detailItemsTable">
                    <!-- JS sẽ điền dữ liệu vào đây thông qua AJAX -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: bold; font-size: 16px;">Tổng cộng:</td>
                        <td id="dtTotal" style="width: 22%; font-weight: bold; font-size: 16px; color: #d9534f;"></td>
                    </tr>
                </tfoot>
            </table>

        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('orderModal');
    
    function openModal(orderData) {
        document.getElementById('modalTitle').textContent = 'Chi tiết đơn hàng #' + orderData.ID;
        document.getElementById('dtName').textContent = orderData.Full_name || 'Guest';
        document.getElementById('dtPhone').textContent = orderData.Phone || 'Không có';
        document.getElementById('dtAddress').textContent = orderData.Shipping_address || 'Không có';
        document.getElementById('dtDate').textContent = orderData.Order_date;
        
        // Hiển thị ngày nhận nếu có
        const receivedRow = document.getElementById('dtReceivedRow');
        if (orderData.Received_date && orderData.Received_date !== null && orderData.Received_date !== '') {
            document.getElementById('dtReceived').textContent = orderData.Received_date;
            receivedRow.style.display = '';
        } else {
            receivedRow.style.display = 'none';
        }
        
        document.getElementById('dtPayment').textContent = orderData.Payment_method || 'COD';
        document.getElementById('dtNote').textContent = orderData.Note || 'Không có ghi chú';
        let statusClass = 'badge-secondary';
        if (orderData.Status === 'Đang vận chuyển') statusClass = 'badge-warning';
        if (orderData.Status === 'Hoàn thành') statusClass = 'badge-success';
        if (orderData.Status === 'Đã huỷ') statusClass = 'badge-danger';
        
        const badge = document.getElementById('dtStatusBadge');
        badge.className = 'badge ' + statusClass;
        badge.textContent = orderData.Status;
        
        let total = parseFloat(orderData.Total_amount) || 0;
        document.getElementById('dtTotal').textContent = total.toLocaleString('vi-VN') + ' ₫';
        
        modal.style.display = "block";
        
        // Gọi API ẩn để lấy chi tiết sản phẩm (sẽ tạo api phụ hoặc dùng chung file)
        fetchOrderDetails(orderData.ID);
    }
    
    function closeModal() {
        modal.style.display = "none";
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    async function fetchOrderDetails(orderId) {
        const tbody = document.getElementById('detailItemsTable');
        const loading = document.getElementById('loadingDetails');
        
        tbody.innerHTML = '';
        loading.style.display = 'block';
        
        try {
            const res = await fetch('admin_dashboard.php?page=order_list&ajax_details=' + orderId);
            const textData = await res.text();
            
            let data;
            try {
                data = JSON.parse(textData);
            } catch (e) {
                throw new Error("Lỗi parse JSON. Server trả về: " + textData.substring(0, 100) + "...");
            }
            
            loading.style.display = 'none';
            
            if (data && data.length > 0) {
                data.forEach(item => {
                    const price = parseFloat(item.Price_at_order) || 0;
                    const qty = parseInt(item.Quantity) || 1;
                    const lineTotal = price * qty;
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><img src="../${item.Image}" style="width: 40px; height: 50px; object-fit: cover; border-radius: 4px;"></td>
                        <td>${item.product_name}</td>
                        <td>${price.toLocaleString('vi-VN')} ₫</td>
                        <td>${qty}</td>
                        <td style="font-weight: bold;">${lineTotal.toLocaleString('vi-VN')} ₫</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Không tìm thấy sản phẩm!</td></tr>';
            }
        } catch (e) {
            loading.style.display = 'none';
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Lỗi tải dữ liệu. <br>${e.message}</td></tr>`;
        }
    }
</script>

