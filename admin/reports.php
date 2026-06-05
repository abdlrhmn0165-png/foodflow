<?php
// ============================================================
// FILE: admin/reports.php
// FOLDER: foodflow/admin/reports.php
// PURPOSE: Advanced analytics — revenue, orders, category charts
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Reports & Analytics';

// ── OVERVIEW STATS ────────────────────────────────────────
$total_revenue   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Paid'")->fetchColumn();
$total_orders    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// ── MONTHLY REVENUE (12 months) ───────────────────────────
$monthly_rev = $pdo->query("
    SELECT DATE_FORMAT(payment_date,'%b %Y') AS label,
           DATE_FORMAT(payment_date,'%Y-%m') AS ym,
           SUM(amount) AS revenue,
           COUNT(*) AS txn_count
    FROM payments
    WHERE payment_status='Paid'
      AND payment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
    ORDER BY ym ASC
")->fetchAll();

$rev_labels  = json_encode(array_column($monthly_rev,'label'));
$rev_data    = json_encode(array_column($monthly_rev,'revenue'));
$rev_counts  = json_encode(array_column($monthly_rev,'txn_count'));

// ── ORDER STATUS BREAKDOWN ────────────────────────────────
$order_status_data = $pdo->query("SELECT order_status, COUNT(*) AS cnt FROM orders GROUP BY order_status")->fetchAll();
$os_labels = json_encode(array_column($order_status_data,'order_status'));
$os_data   = json_encode(array_column($order_status_data,'cnt'));

// ── PAYMENT METHOD BREAKDOWN ──────────────────────────────
$pay_methods = $pdo->query("SELECT payment_method, COUNT(*) AS cnt, SUM(amount) AS total FROM payments WHERE payment_status='Paid' GROUP BY payment_method")->fetchAll();
$pm_labels = json_encode(array_column($pay_methods,'payment_method'));
$pm_data   = json_encode(array_column($pay_methods,'cnt'));

// ── CATEGORY SALES ────────────────────────────────────────
$cat_sales = $pdo->query("
    SELECT c.category_name, COUNT(oi.id) AS items_sold, SUM(oi.quantity*oi.price) AS revenue
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id=m.id
    JOIN categories c ON m.category_id=c.id
    GROUP BY c.id ORDER BY revenue DESC
")->fetchAll();
$cat_labels  = json_encode(array_column($cat_sales,'category_name'));
$cat_revenue = json_encode(array_column($cat_sales,'revenue'));

// ── TOP 5 ITEMS ───────────────────────────────────────────
$top_items = $pdo->query("
    SELECT m.item_name, SUM(oi.quantity) AS qty, SUM(oi.quantity*oi.price) AS revenue
    FROM order_items oi JOIN menu_items m ON oi.menu_item_id=m.id
    GROUP BY m.id ORDER BY qty DESC LIMIT 5
")->fetchAll();

// ── DAILY ORDERS LAST 14 DAYS ─────────────────────────────
$daily = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%a') AS day_label, DATE(created_at) AS day_date, COUNT(*) AS cnt
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day_date ASC
")->fetchAll();
$daily_labels = json_encode(array_column($daily,'day_label'));
$daily_data   = json_encode(array_column($daily,'cnt'));

include 'includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb-ff">
        <a href="dashboard.php">Dashboard</a>
        <i class="bi bi-chevron-right" style="font-size:.6rem"></i>
        <span>Reports</span>
    </div>
    <h1>Reports & Analytics</h1>
    <p>In-depth business performance overview</p>
</div>

<!-- KPI CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(251,191,36,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(251,191,36,0.12);color:var(--gold)"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="color:var(--gold)">$<?= number_format($total_revenue,0) ?></div>
            <div class="stat-trend" style="color:var(--muted)">All time earnings</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(56,189,248,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(56,189,248,0.12);color:var(--info)"><i class="bi bi-bag-fill"></i></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value" style="color:var(--info)"><?= $total_orders ?></div>
            <div class="stat-trend" style="color:var(--muted)">All orders placed</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(34,197,94,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.12);color:var(--success)"><i class="bi bi-people-fill"></i></div>
            <div class="stat-label">Total Customers</div>
            <div class="stat-value" style="color:var(--success)"><?= $total_customers ?></div>
            <div class="stat-trend" style="color:var(--muted)">Registered users</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(249,115,22,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(249,115,22,0.12);color:var(--accent)"><i class="bi bi-receipt"></i></div>
            <div class="stat-label">Avg Order Value</div>
            <div class="stat-value" style="color:var(--accent)">$<?= number_format($avg_order_value,2) ?></div>
            <div class="stat-trend" style="color:var(--muted)">Per order average</div>
        </div>
    </div>
</div>

<!-- MAIN CHARTS ROW -->
<div class="row g-3 mb-3">
    <!-- Revenue 12 months -->
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-graph-up-arrow me-2" style="color:var(--accent)"></i>Monthly Revenue (12 Months)</div>
            </div>
            <div style="padding:20px"><canvas id="revenueChart" height="90"></canvas></div>
        </div>
    </div>
    <!-- Payment Methods Doughnut -->
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-pie-chart me-2" style="color:var(--info)"></i>Payment Methods</div>
            </div>
            <div style="padding:20px;display:flex;align-items:center;justify-content:center">
                <canvas id="paymentChart" height="220" style="max-width:220px"></canvas>
            </div>
            <div style="padding:0 20px 16px">
                <?php foreach($pay_methods as $pm): ?>
                <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:4px 0">
                    <span style="color:var(--muted)"><?= $pm['payment_method'] ?></span>
                    <span style="font-weight:600">$<?= number_format($pm['total'],0) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <!-- Order Status Breakdown -->
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-clipboard-data me-2" style="color:var(--gold)"></i>Order Status</div>
            </div>
            <div style="padding:20px;display:flex;align-items:center;justify-content:center">
                <canvas id="statusChart" height="220" style="max-width:220px"></canvas>
            </div>
        </div>
    </div>
    <!-- Category Revenue -->
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-bar-chart-horizontal me-2" style="color:var(--success)"></i>Revenue by Category</div>
            </div>
            <div style="padding:20px"><canvas id="catChart" height="220"></canvas></div>
        </div>
    </div>
    <!-- Daily Orders -->
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-calendar3 me-2" style="color:var(--info)"></i>Daily Orders (14 Days)</div>
            </div>
            <div style="padding:20px"><canvas id="dailyChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<!-- TOP ITEMS TABLE -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title"><i class="bi bi-trophy me-2" style="color:var(--gold)"></i>Top Performing Items</div>
    </div>
    <table class="data-table">
        <thead>
            <tr><th>Rank</th><th>Item Name</th><th>Units Sold</th><th>Revenue</th><th>Share</th></tr>
        </thead>
        <tbody>
        <?php
        $max_rev = !empty($top_items) ? max(array_column($top_items,'revenue')) : 1;
        foreach($top_items as $i => $item):
            $pct = round($item['revenue'] / $max_rev * 100);
        ?>
        <tr>
            <td>
                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;
                    background:<?= $i===0?'rgba(251,191,36,0.15)':($i===1?'rgba(148,163,184,0.1)':($i===2?'rgba(249,115,22,0.1)':'rgba(255,255,255,0.04)')) ?>;
                    color:<?= $i===0?'var(--gold)':($i===1?'#94a3b8':($i===2?'var(--accent)':'var(--muted)')) ?>;
                    font-weight:700;font-size:.8rem">
                    <?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':$i+1)) ?>
                </span>
            </td>
            <td style="font-weight:500"><?= htmlspecialchars($item['item_name']) ?></td>
            <td style="color:var(--info);font-weight:600"><?= $item['qty'] ?></td>
            <td style="color:var(--gold);font-weight:700">$<?= number_format($item['revenue'],2) ?></td>
            <td style="min-width:120px">
                <div class="progress-ff">
                    <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

const colors = ['#f97316','#38bdf8','#22c55e','#fbbf24','#a855f7','#ef4444','#06b6d4','#ec4899'];

// Revenue
new Chart(document.getElementById('revenueChart'),{
    type:'line',
    data:{
        labels:<?= $rev_labels ?>,
        datasets:[{
            label:'Revenue',data:<?= $rev_data ?>,
            borderColor:'#f97316',backgroundColor:'rgba(249,115,22,0.07)',
            borderWidth:2.5,tension:0.4,fill:true,pointRadius:4,pointHoverRadius:6,pointBackgroundColor:'#f97316'
        },{
            label:'Transactions',data:<?= $rev_counts ?>,
            borderColor:'#38bdf8',backgroundColor:'transparent',
            borderWidth:1.5,tension:0.4,borderDash:[4,4],pointRadius:3,yAxisID:'y2'
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{labels:{color:'#94a3b8',boxWidth:12}}},
        scales:{
            x:{grid:{color:'rgba(255,255,255,0.04)'}},
            y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{callback:v=>'$'+v}},
            y2:{position:'right',grid:{display:false},ticks:{stepSize:1}}
        }
    }
});

// Payment Methods Doughnut
new Chart(document.getElementById('paymentChart'),{
    type:'doughnut',
    data:{labels:<?= $pm_labels ?>,datasets:[{data:<?= $pm_data ?>,backgroundColor:colors,borderColor:'#141d2b',borderWidth:3,hoverOffset:6}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#94a3b8',padding:12,boxWidth:10}}}}
});

// Order Status Doughnut
new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{labels:<?= $os_labels ?>,datasets:[{data:<?= $os_data ?>,backgroundColor:['#f59e0b','#38bdf8','#f97316','#a855f7','#22c55e','#ef4444'],borderColor:'#141d2b',borderWidth:3,hoverOffset:6}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:'#94a3b8',padding:10,boxWidth:10}}}}
});

// Category Revenue Bar
new Chart(document.getElementById('catChart'),{
    type:'bar',
    data:{
        labels:<?= $cat_labels ?>,
        datasets:[{label:'Revenue',data:<?= $cat_revenue ?>,backgroundColor:'rgba(34,197,94,0.2)',borderColor:'#22c55e',borderWidth:1.5,borderRadius:6}]
    },
    options:{
        responsive:true,indexAxis:'y',
        plugins:{legend:{display:false}},
        scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{callback:v=>'$'+v}},y:{grid:{display:false}}}
    }
});

// Daily Orders
new Chart(document.getElementById('dailyChart'),{
    type:'bar',
    data:{
        labels:<?= $daily_labels ?>,
        datasets:[{label:'Orders',data:<?= $daily_data ?>,backgroundColor:'rgba(56,189,248,0.2)',borderColor:'#38bdf8',borderWidth:1.5,borderRadius:6}]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{x:{grid:{display:false}},y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{stepSize:1}}}
    }
});
</script>

<?php include 'includes/footer.php'; ?>