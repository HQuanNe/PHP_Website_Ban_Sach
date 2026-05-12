<?php
/**
 * dashboard_stats.php — Trang thống kê tổng quan (Admin)
 * Phải được include từ admin_dashboard.php.
 */
if (!isset($conn)) {
    header('Location: admin_dashboard.php?page=dashboard_stats');
    exit;
}

// Card tổng quan — chỉ đơn Hoàn thành
$rev  = $conn->query("SELECT COALESCE(SUM(Total_amount),0) as t FROM orders WHERE Status='Hoàn thành'")->fetch_assoc()['t'];
$ords = $conn->query("SELECT COUNT(*) as t FROM orders WHERE Status='Hoàn thành'")->fetch_assoc()['t'];
$custs = $conn->query("SELECT COUNT(*) as t FROM users WHERE Role='customer'")->fetch_assoc()['t'];
$prods = $conn->query("SELECT COUNT(*) as t FROM products")->fetch_assoc()['t'];

// Sản phẩm sắp hết hàng
$low_stock = $conn->query("SELECT ID, Name, Quantity, Image_URL FROM products WHERE Quantity < 10 AND Quantity > 0 ORDER BY Quantity ASC LIMIT 8");

$current_year = date('Y');
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<style>
/* ── Cards tổng quan ── */
.stats-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card { background:var(--admin-card); border-radius:14px; padding:20px; display:flex; align-items:center; gap:16px; box-shadow:0 2px 10px rgba(0,0,0,0.06); transition:transform 0.2s, box-shadow 0.2s; }
.stat-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,0.1); }
.stat-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; }
.stat-icon.rev { background:linear-gradient(135deg,#ff9800,#e65100); color:#fff; }
.stat-icon.ord { background:linear-gradient(135deg,#42a5f5,#1565c0); color:#fff; }
.stat-icon.cust { background:linear-gradient(135deg,#66bb6a,#2e7d32); color:#fff; }
.stat-icon.prod { background:linear-gradient(135deg,#ab47bc,#6a1b9a); color:#fff; }
.stat-info h3 { font-size:22px; font-weight:800; color:var(--admin-text); margin-bottom:2px; }
.stat-info p { font-size:12px; color:var(--admin-text-muted); font-weight:500; }

/* ── Charts grid ── */
.charts-grid { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:24px; }
@media(max-width:1000px){ .charts-grid { grid-template-columns:1fr; } }
.chart-card { background:var(--admin-card); border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06); }
.chart-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.chart-card-title { font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; color:var(--admin-text); }
.chart-select { padding:6px 12px; border:1.5px solid var(--admin-border); border-radius:8px; font-size:12px; background:var(--admin-bg); color:var(--admin-text); font-family:inherit; cursor:pointer; }
.chart-select:focus { outline:none; border-color:var(--admin-primary); }
.chart-container { position:relative; width:100%; height:280px; }

/* ── Bảng bottom ── */
.bottom-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:900px){ .bottom-grid { grid-template-columns:1fr; } }
.mini-table { width:100%; border-collapse:collapse; margin-top:12px; }
.mini-table th { background:var(--admin-bg); padding:8px 12px; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:var(--admin-text-muted); text-align:left; }
.mini-table td { padding:8px 12px; font-size:13px; border-bottom:1px solid var(--admin-border); }
.mini-table tr:last-child td { border-bottom:none; }
.rank-badge { width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; color:#fff; }
.rank-1 { background:linear-gradient(135deg,#ffd700,#ffa000); }
.rank-2 { background:linear-gradient(135deg,#bdbdbd,#757575); }
.rank-3 { background:linear-gradient(135deg,#cd7f32,#8d5524); }
.rank-other { background:var(--admin-primary-light); color:var(--admin-text); }
.low-stock-item { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--admin-border); }
.low-stock-item:last-child { border-bottom:none; }
.low-stock-img { width:36px; height:44px; border-radius:6px; object-fit:cover; background:var(--admin-bg); }
.low-stock-name { flex:1; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.low-stock-qty { font-size:12px; font-weight:700; color:#e74c3c; background:#fce4e4; padding:2px 8px; border-radius:10px; }
</style>

<!-- ═══ CARDS TỔNG QUAN ═══ -->
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-icon rev"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="stat-info">
            <h3><?= number_format($rev, 0, ',', '.') ?>₫</h3>
            <p>Tổng doanh thu</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon ord"><i class="fa-solid fa-box"></i></div>
        <div class="stat-info">
            <h3><?= $ords ?></h3>
            <p>Đơn hoàn thành</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon cust"><i class="fa-solid fa-user-group"></i></div>
        <div class="stat-info">
            <h3><?= $custs ?></h3>
            <p>Khách hàng</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon prod"><i class="fa-solid fa-book"></i></div>
        <div class="stat-info">
            <h3><?= $prods ?></h3>
            <p>Sản phẩm</p>
        </div>
    </div>
</div>

<!-- ═══ BIỂU ĐỒ DOANH THU + TRẠNG THÁI ĐƠN ═══ -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-card-header">
            <div class="chart-card-title"><i class="fa-solid fa-chart-line"></i> Doanh thu</div>
            <div style="display:flex;gap:8px;">
                <select id="revPeriod" class="chart-select" onchange="loadRevenue()">
                    <option value="month">Theo tháng</option>
                    <option value="quarter">Theo quý</option>
                    <option value="year">Theo năm</option>
                </select>
                <select id="revYear" class="chart-select" onchange="loadRevenue()">
                    <?php for ($y = $current_year; $y >= $current_year - 4; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="chart-container"><canvas id="revenueChart"></canvas></div>
    </div>
    <div class="chart-card">
        <div class="chart-card-header">
            <div class="chart-card-title"><i class="fa-solid fa-chart-pie"></i> Trạng thái đơn hàng</div>
        </div>
        <div class="chart-container"><canvas id="statusChart"></canvas></div>
    </div>
</div>

<!-- ═══ TOP SẢN PHẨM + CẢNH BÁO TỒN KHO ═══ -->
<div class="bottom-grid">
    <div class="chart-card">
        <div class="chart-card-header">
            <div class="chart-card-title"><i class="fa-solid fa-trophy"></i> Top sản phẩm bán chạy</div>
        </div>
        <table class="mini-table" id="topProductsTable">
            <thead><tr><th>#</th><th>Sản phẩm</th><th>Đã bán</th><th>Doanh thu</th></tr></thead>
            <tbody><tr><td colspan="4" style="text-align:center;color:#ccc;padding:20px"><i class="fa-solid fa-spinner fa-spin"></i></td></tr></tbody>
        </table>
    </div>
    <div class="chart-card">
        <div class="chart-card-header">
            <div class="chart-card-title"><i class="fa-solid fa-triangle-exclamation" style="color:#e74c3c"></i> Sắp hết hàng</div>
        </div>
        <?php if ($low_stock && $low_stock->num_rows > 0): ?>
            <?php while ($ls = $low_stock->fetch_assoc()): ?>
                <div class="low-stock-item">
                    <?php if (!empty($ls['Image_URL'])): ?>
                        <img class="low-stock-img" src="../<?= htmlspecialchars($ls['Image_URL']) ?>" alt="">
                    <?php else: ?>
                        <div class="low-stock-img" style="display:flex;align-items:center;justify-content:center;font-size:14px;color:#ccc;"><i class="fa-solid fa-image"></i></div>
                    <?php endif; ?>
                    <span class="low-stock-name"><?= htmlspecialchars($ls['Name']) ?></span>
                    <span class="low-stock-qty">Còn <?= $ls['Quantity'] ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align:center;padding:30px;color:#999;font-size:13px;">
                <i class="fa-solid fa-circle-check" style="color:#2e7d32;font-size:28px;"></i><br><br>
                Tất cả sản phẩm đều đủ hàng!
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const STAT_API = 'stats_api.php';
let revenueChart, statusChart;

/* ── Biểu đồ doanh thu ── */
async function loadRevenue() {
    const period = document.getElementById('revPeriod').value;
    const year   = document.getElementById('revYear').value;
    const yearSel = document.getElementById('revYear');
    yearSel.style.display = period === 'year' ? 'none' : '';

    const res  = await fetch(`${STAT_API}?type=revenue&period=${period}&year=${year}`);
    const json = await res.json();

    if (revenueChart) revenueChart.destroy();
    const ctx = document.getElementById('revenueChart').getContext('2d');
    revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: json.labels,
            datasets: [{
                label: 'Doanh thu (₫)',
                data: json.data,
                backgroundColor: 'rgba(122,111,99,0.6)',
                borderColor: '#7a6f63',
                borderWidth: 2,
                borderRadius: 6,
                hoverBackgroundColor: 'rgba(122,111,99,0.85)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.parsed.y.toLocaleString('vi-VN') + ' ₫'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => (v >= 1e6 ? (v/1e6).toFixed(1) + 'M' : v >= 1e3 ? (v/1e3).toFixed(0) + 'K' : v)
                    },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}

/* ── Biểu đồ trạng thái đơn ── */
async function loadOrderStatus() {
    const res  = await fetch(`${STAT_API}?type=order_status`);
    const json = await res.json();

    const labels = json.map(i => i.label);
    const data   = json.map(i => i.count);
    const colors = labels.map(l => {
        if (l === 'Hoàn thành') return '#66bb6a';
        if (l === 'Chờ xử lý') return '#ffa726';
        if (l === 'Đã huỷ') return '#ef5350';
        return '#bdbdbd';
    });

    if (statusChart) statusChart.destroy();
    const ctx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 8 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, font: { size: 12, weight: 600 } } }
            },
            cutout: '55%'
        }
    });
}

/* ── Top sản phẩm bán chạy ── */
async function loadTopProducts() {
    const res  = await fetch(`${STAT_API}?type=top_products&limit=10`);
    const json = await res.json();
    const tbody = document.querySelector('#topProductsTable tbody');

    if (!json.length) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:#999">Chưa có dữ liệu bán hàng.</td></tr>';
        return;
    }

    tbody.innerHTML = json.map((p, i) => {
        const rankClass = i < 3 ? `rank-${i+1}` : 'rank-other';
        return `<tr>
            <td><span class="rank-badge ${rankClass}">${i+1}</span></td>
            <td style="font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escH(p.name)}</td>
            <td>${p.sold}</td>
            <td style="font-weight:700;color:#e65100">${p.revenue.toLocaleString('vi-VN')}₫</td>
        </tr>`;
    }).join('');
}

function escH(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

/* ── INIT ── */
document.addEventListener('DOMContentLoaded', () => {
    loadRevenue();
    loadOrderStatus();
    loadTopProducts();
});
</script>
