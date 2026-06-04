<?php
// ============================================================
// FILE: admin/dashboard.php
// PURPOSE: Main admin analytics dashboard with charts
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Dashboard';

// ── STATS ──────────────────────────────────────────────────
$total_items    = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$available_items= $pdo->query("SELECT COUNT(*) FROM menu_items WHERE status='Available'")->fetchColumn();
$total_orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_customers= $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$total_revenue  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Paid'")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='Pending'")->fetchColumn();
$today_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$today_revenue  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Paid' AND DATE(payment_date)=CURDATE()")->fetchColumn();

// ── MONTHLY REVENUE (last 6 months) ───────────────────────
$monthly = $pdo->query("
    SELECT DATE_FORMAT(payment_date,'%b') AS month,
           MONTH(payment_date) AS mo,
           COALESCE(SUM(amount),0) AS revenue
    FROM payments
    WHERE payment_status='Paid'
      AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(payment_date), MONTH(payment_date)
    ORDER BY YEAR(payment_date), MONTH(payment_date)
")->fetchAll();

$chart_months   = json_encode(array_column($monthly,'month'));
$chart_revenue  = json_encode(array_column($monthly,'revenue'));

// ── ORDERS PER MONTH ──────────────────────────────────────
$orders_monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%b') AS month,
           COUNT(*) AS cnt
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
")->fetchAll();
$chart_order_months = json_encode(array_column($orders_monthly,'month'));
$chart_order_counts = json_encode(array_column($orders_monthly,'cnt'));

// ── CATEGORY DISTRIBUTION ─────────────────────────────────
$categories = $pdo->query("
    SELECT c.category_name, COUNT(m.id) AS cnt
    FROM categories c
    LEFT JOIN menu_items m ON m.category_id=c.id
    GROUP BY c.id ORDER BY cnt DESC
")->fetchAll();
$cat_max = max(array_column($categories,'cnt') ?: [1]);

// ── RECENT ORDERS ─────────────────────────────────────────
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name
    FROM orders o JOIN users u ON o.user_id=u.id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();

// ── RECENT ACTIVITY ───────────────────────────────────────
$activities = $pdo->query("
    SELECT a.*, u.full_name
    FROM activity_logs a JOIN users u ON a.user_id=u.id
    ORDER BY a.created_at DESC LIMIT 8
")->fetchAll();

// ── TOP ITEMS ─────────────────────────────────────────────
$top_items = $pdo->query("
    SELECT m.item_name, SUM(oi.quantity) AS total_sold, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi JOIN menu_items m ON oi.menu_item_id=m.id
    GROUP BY m.id ORDER BY total_sold DESC LIMIT 5
")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb-ff"><span>Admin</span> <i class="bi bi-chevron-right" style="font-size:.6rem"></i> <span>Dashboard</span></div>
    <h1>
Good <?= (date('H') < 12) ? 'Morning' : ((date('H') < 17) ? 'Afternoon' : 'Evening') ?>,
<?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?> 👋
</h1>
    <p>Here's what's happening at your restaurant today.</p>
</div>

<!-- STAT CARDS ROW 1 -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(249,115,22,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(249,115,22,0.12);color:var(--accent)"><i class="bi bi-grid-3x3-gap-fill"></i></div>
            <div class="stat-label">Total Menu Items</div>
            <div class="stat-value" style="color:var(--accent)"><?= $total_items ?></div>
            <div class="stat-trend"><span style="color:var(--success)"><i class="bi bi-arrow-up-short"></i></span> <?= $available_items ?> available</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(56,189,248,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(56,189,248,0.12);color:var(--info)"><i class="bi bi-bag-check-fill"></i></div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value" style="color:var(--info)"><?= $total_orders ?></div>
            <div class="stat-trend"><span style="color:var(--warning)"><i class="bi bi-clock"></i></span> <?= $pending_orders ?> pending</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(34,197,94,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.12);color:var(--success)"><i class="bi bi-people-fill"></i></div>
            <div class="stat-label">Customers</div>
            <div class="stat-value" style="color:var(--success)"><?= $total_customers ?></div>
            <div class="stat-trend"><span style="color:var(--muted)"><i class="bi bi-person-plus"></i></span> Registered users</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="--glow-color:rgba(251,191,36,0.12)">
            <div class="stat-icon-wrap" style="background:rgba(251,191,36,0.12);color:var(--gold)"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="color:var(--gold)">$<?= number_format($total_revenue,0) ?></div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i> $<?= number_format($today_revenue,2) ?> today</div>
        </div>
    </div>
</div>

<!-- CHARTS ROW -->
<div class="row g-3 mb-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-graph-up-arrow me-2" style="color:var(--accent)"></i>Revenue Overview</div>
                <span style="font-size:.75rem;color:var(--muted)">Last 6 months</span>
            </div>
            <div style="padding:20px">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <!-- Orders Chart -->
    <div class="col-lg-4">
        <div class="panel" style="height:100%">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-bar-chart me-2" style="color:var(--info)"></i>Orders/Month</div>
            </div>
            <div style="padding:20px">
                <canvas id="ordersChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- CATEGORIES + ACTIVITY ROW -->
<div class="row g-3 mb-4">
    <!-- Category Table -->
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-pie-chart me-2" style="color:var(--gold)"></i>Menu by Category</div>
            </div>
            <div style="padding:20px">
                <?php foreach($categories as $cat): ?>
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:5px">
                        <span><?= htmlspecialchars($cat['category_name']) ?></span>
                        <span style="color:var(--muted)"><?= $cat['cnt'] ?> items</span>
                    </div>
                    <div class="progress-ff">
                        <div class="progress-fill" style="width:<?= $cat_max > 0 ? round($cat['cnt']/$cat_max*100) : 0 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Recent Activity -->
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-activity me-2" style="color:var(--success)"></i>Recent Activity</div>
            </div>
            <div style="padding:20px">
                <?php foreach($activities as $a): ?>
                <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:16px">
                    <div style="width:8px;height:8px;border-radius:50%;background:var(--accent);margin-top:6px;flex-shrink:0"></div>
                    <div style="flex:1">
                        <div style="font-size:.875rem;font-weight:500"><?= htmlspecialchars($a['activity']) ?></div>
                        <div style="font-size:.75rem;color:var(--muted);margin-top:2px">
                            <?= htmlspecialchars($a['full_name']) ?> &bull; <?= date('M j, g:i a', strtotime($a['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- TOP ITEMS + RECENT ORDERS -->
<div class="row g-3">
    <!-- Top Selling Items -->
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-trophy me-2" style="color:var(--gold)"></i>Top Selling Items</div>
            </div>
            <table class="data-table">
                <thead><tr><th>#</th><th>Item</th><th>Sold</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php foreach($top_items as $i => $item): ?>
                <tr>
                    <td><span style="color:var(--muted)"><?= $i+1 ?></span></td>
                    <td style="font-weight:500"><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><span style="color:var(--info)"><?= $item['total_sold'] ?></span></td>
                    <td><span style="color:var(--success)">$<?= number_format($item['revenue'],2) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Recent Orders -->
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-clock-history me-2" style="color:var(--info)"></i>Recent Orders</div>
                <a href="<?= SITE_URL ?>/admin/orders.php" style="font-size:.8rem;color:var(--accent);text-decoration:none">View all</a>
            </div>
            <table class="data-table">
                <thead><tr><th>ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach($recent_orders as $ord): 
                    $sc = strtolower(str_replace(' ','',$ord['order_status']));
                ?>
                <tr>
                    <td style="color:var(--muted)">#<?= $ord['id'] ?></td>
                    <td style="font-weight:500"><?= htmlspecialchars($ord['full_name']) ?></td>
                    <td style="color:var(--gold)">$<?= number_format($ord['total_amount'],2) ?></td>
                    <td><span class="badge-status badge-<?= $sc ?>"><?= $ord['order_status'] ?></span></td>
                    <td style="color:var(--muted);font-size:.8rem"><?= date('M j', strtotime($ord['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// Revenue Chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= $chart_months ?>,
        datasets: [{
            label: 'Revenue ($)',
            data: <?= $chart_revenue ?>,
            borderColor: '#f97316',
            backgroundColor: 'rgba(249,115,22,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#f97316',
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)' } },
            y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { callback: v => '$'+v } }
        }
    }
});

// Orders Chart
new Chart(document.getElementById('ordersChart'), {
    type: 'bar',
    data: {
        labels: <?= $chart_order_months ?>,
        datasets: [{
            label: 'Orders',
            data: <?= $chart_order_counts ?>,
            backgroundColor: 'rgba(56,189,248,0.2)',
            borderColor: '#38bdf8',
            borderWidth: 1.5,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { stepSize: 1 } }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>