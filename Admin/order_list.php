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

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status   = $conn->real_escape_string($_POST['status'] ?? '');
    if ($order_id > 0 && in_array($status, ['Chờ xử lý', 'Hoàn thành', 'Đã huỷ'])) {
        $conn->query("UPDATE orders SET Status = '$status' WHERE ID = $order_id");
    }
    echo "<script>window.location.href='admin_dashboard.php?page=order_list';</script>";
    exit;
}

// Lấy danh sách đơn hàng
$sql_orders = "SELECT o.*, u.UserName as account_name 
               FROM orders o 
               LEFT JOIN users u ON o.User_ID = u.ID 
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
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Mã ĐH</th>
                <th>Khách hàng</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Phương thức</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <?php while ($row = $orders_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $row['ID'] ?></strong></td>
                        <td>
                            <?= htmlspecialchars($row['Full_name'] ?? 'Guest') ?><br>
                            <small style="color: #6c757d;"><?= htmlspecialchars($row['Phone'] ?? '') ?></small>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($row['Order_date'])) ?></td>
                        <td style="font-weight: bold; color: #d9534f;"><?= number_format($row['Total_amount'] ?? 0, 0, ',', '.') ?> ₫</td>
                        <td><?= htmlspecialchars($row['Payment_method'] ?? 'COD') ?></td>
                        <td>
                            <?php 
                                $statusClass = 'badge-warning';
                                if ($row['Status'] === 'Hoàn thành') $statusClass = 'badge-success';
                                if ($row['Status'] === 'Đã huỷ') $statusClass = 'badge-danger';
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($row['Status']) ?></span>
                        </td>
                        <td>
                            <button class="btn btn-info" onclick='openModal(<?= json_encode($row) ?>)'>Xem chi tiết</button>
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
                <div class="detail-item"><strong>Phương thức:</strong> <span id="dtPayment"></span></div>
                <div class="detail-item"><strong>Ghi chú:</strong> <span id="dtNote"></span></div>
                <div class="detail-item" style="grid-column: 1 / -1; margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                    <form method="POST" class="status-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="updateOrderId">
                        <strong style="margin-right: 10px; line-height: 35px;">Trạng thái đơn:</strong>
                        <select name="status" id="dtStatus" class="status-select">
                            <option value="Chờ xử lý">Chờ xử lý</option>
                            <option value="Hoàn thành">Hoàn thành</option>
                            <option value="Đã huỷ">Đã huỷ</option>
                        </select>
                        <button type="submit" class="btn btn-update">Cập nhật trạng thái</button>
                    </form>
                </div>
            </div>

            <h3 style="margin-bottom: 15px; font-size: 16px; border-bottom: 2px solid #007bff; display: inline-block; padding-bottom: 5px;">Sản phẩm đã đặt</h3>
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
        document.getElementById('dtPayment').textContent = orderData.Payment_method || 'COD';
        document.getElementById('dtNote').textContent = orderData.Note || 'Không có ghi chú';
        document.getElementById('dtStatus').value = orderData.Status;
        document.getElementById('updateOrderId').value = orderData.ID;
        
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

