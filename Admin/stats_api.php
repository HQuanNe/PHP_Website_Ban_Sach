<?php
/**
 * stats_api.php — API thống kê cho biểu đồ Admin
 * Phải có session admin active.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
include '../Connect/connect.php';
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

/* ── Doanh thu theo tháng/quý/năm (chỉ đơn Hoàn thành) ── */
if ($type === 'revenue') {
    $period = $_GET['period'] ?? 'month';
    $year   = intval($_GET['year'] ?? date('Y'));

    if ($period === 'month') {
        $sql = "SELECT MONTH(Order_date) as label, SUM(Total_amount) as total 
                FROM orders WHERE Status='Hoàn thành' AND YEAR(Order_date)=$year 
                GROUP BY MONTH(Order_date) ORDER BY label";
        $result = $conn->query($sql);
        $data = array_fill(1, 12, 0);
        $labels = ['','Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'];
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $data[intval($r['label'])] = floatval($r['total']);
            }
        }
        $out_labels = array_slice($labels, 1);
        $out_data   = array_values($data);
        echo json_encode(['labels' => $out_labels, 'data' => $out_data, 'year' => $year]);

    } elseif ($period === 'quarter') {
        $sql = "SELECT QUARTER(Order_date) as label, SUM(Total_amount) as total 
                FROM orders WHERE Status='Hoàn thành' AND YEAR(Order_date)=$year 
                GROUP BY QUARTER(Order_date) ORDER BY label";
        $result = $conn->query($sql);
        $data = [1=>0, 2=>0, 3=>0, 4=>0];
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $data[intval($r['label'])] = floatval($r['total']);
            }
        }
        echo json_encode([
            'labels' => ['Q1','Q2','Q3','Q4'],
            'data'   => array_values($data),
            'year'   => $year
        ]);

    } elseif ($period === 'year') {
        $sql = "SELECT YEAR(Order_date) as label, SUM(Total_amount) as total 
                FROM orders WHERE Status='Hoàn thành' 
                GROUP BY YEAR(Order_date) ORDER BY label";
        $result = $conn->query($sql);
        $labels = []; $data = [];
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $labels[] = $r['label'];
                $data[]   = floatval($r['total']);
            }
        }
        if (empty($labels)) { $labels = [date('Y')]; $data = [0]; }
        echo json_encode(['labels' => $labels, 'data' => $data]);
    }
    exit;
}

/* ── Top sản phẩm bán chạy ── */
if ($type === 'top_products') {
    $limit = intval($_GET['limit'] ?? 10);
    $sql = "SELECT p.Name, SUM(od.Quantity) as total_sold, SUM(od.Quantity * od.Price_at_order) as revenue
            FROM order_detail od
            JOIN products p ON od.Product_ID = p.ID
            JOIN orders o ON od.Order_ID = o.ID
            WHERE o.Status = 'Hoàn thành'
            GROUP BY od.Product_ID
            ORDER BY total_sold DESC
            LIMIT $limit";
    $result = $conn->query($sql);
    $items = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $items[] = [
                'name'    => $r['Name'],
                'sold'    => intval($r['total_sold']),
                'revenue' => floatval($r['revenue'])
            ];
        }
    }
    echo json_encode($items);
    exit;
}

/* ── Tỷ lệ đơn hàng theo trạng thái ── */
if ($type === 'order_status') {
    $sql = "SELECT Status, COUNT(*) as cnt FROM orders GROUP BY Status";
    $result = $conn->query($sql);
    $data = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $data[] = ['label' => $r['Status'], 'count' => intval($r['cnt'])];
        }
    }
    echo json_encode($data);
    exit;
}

/* ── Top khách hàng VIP ── */
if ($type === 'top_customers') {
    $sql = "SELECT u.UserName, COUNT(o.ID) as order_count, SUM(o.Total_amount) as total_spent
            FROM orders o JOIN users u ON o.User_ID = u.ID
            WHERE o.Status = 'Hoàn thành'
            GROUP BY o.User_ID
            ORDER BY total_spent DESC LIMIT 5";
    $result = $conn->query($sql);
    $items = [];
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $items[] = [
                'name'   => $r['UserName'],
                'orders' => intval($r['order_count']),
                'spent'  => floatval($r['total_spent'])
            ];
        }
    }
    echo json_encode($items);
    exit;
}

echo json_encode(['error' => 'Invalid type']);
