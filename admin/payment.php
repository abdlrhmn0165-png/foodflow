<?php
// ============================================================
// FILE: admin/payments.php
// FOLDER: foodflow/admin/payments.php
// PURPOSE: View all payment records with totals and filters
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Payments';

// ── STATS ─────────────────────────────────────────────────
$total_paid    = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Paid'")->fetchColumn();
$total_pending = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Pending'")->fetchColumn();
$count_paid    = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status='Paid'")->fetchColumn();
$count_pending = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status='Pending'")->fetchColumn();

// ── FILTERS ───────────────────────────────────────────────
$status_f = $_GET['status'] ?? '';
$method_f = $_GET['method'] ?? '';

$sql = "SELECT p.*, o.order_status, u.full_name, u.email
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE 1=1";
$params = [];
if ($status_f) { $sql .= " AND p.payment_status=?"; $params[] = $status_f; }
if ($method_f) { $sql .= " AND p.payment_method=?"; $params[] = $method_f; }
$sql .= " ORDER BY p.payment_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Method breakdown
$method_stats = $pdo->query("SELECT payment_method, COUNT(*) AS cnt, SUM(amount) AS total FROM payments WHERE payment_status='Paid' GROUP BY payment_method")->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb-ff">
        <a href="dashboard.php">Dashboard</a>
        <i class="bi bi-chevron-right" style="font-size:.6rem"></i>
        <span>Payments</span>
    </div>
    <h1>Payments</h1>
    <p>Track all transactions and payment records</p>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="--glow-color:rgba(34,197,94,0.1)">
            <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.12);color:var(--success)"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value" style="color:var(--success)">$<?= number_format($total_paid,0) ?></div>
            <div class="stat-trend" style="color:var(--muted)"><?= $count_paid ?> paid transactions</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="--glow-color:rgba(245,158,11,0.1)">
            <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.12);color:var(--warning)"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-label">Pending Amount</div>
            <div class="stat-value" style="color:var(--warning)">$<?= number_format($total_pending,2) ?></div>
            <div class="stat-trend" style="color:var(--muted)"><?= $count_pending ?> pending</div>
        </div>
    </div>
    <?php foreach($method_stats as $ms): ?>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:rgba(249,115,22,0.1);color:var(--accent)">
                <i class="bi bi-<?= $ms['payment_method']==='Card'?'credit-card':($ms['payment_method']==='Online'?'globe':($ms['payment_method']==='Wallet'?'wallet2':'cash-coin')) ?>"></i>
            </div>
            <div class="stat-label"><?= $ms['payment_method'] ?></div>
            <div class="stat-value" style="font-size:1.4rem">$<?= number_format($ms['total'],0) ?></div>
            <div class="stat-trend" style="color:var(--muted)"><?= $ms['cnt'] ?> transactions</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- FILTERS -->
<div class="panel mb-3">
    <div style="padding:14px 20px">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
            <div>
                <label class="form-label" style="font-size:.75rem">Payment Status</label>
                <select name="status" class="form-select" style="min-width:150px">
                    <option value="">All Status</option>
                    <option value="Paid"     <?= $status_f==='Paid'    ?'selected':'' ?>>Paid</option>
                    <option value="Pending"  <?= $status_f==='Pending' ?'selected':'' ?>>Pending</option>
                    <option value="Failed"   <?= $status_f==='Failed'  ?'selected':'' ?>>Failed</option>
                    <option value="Refunded" <?= $status_f==='Refunded'?'selected':'' ?>>Refunded</option>
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size:.75rem">Method</label>
                <select name="method" class="form-select" style="min-width:150px">
                    <option value="">All Methods</option>
                    <option value="Cash"   <?= $method_f==='Cash'  ?'selected':'' ?>>Cash</option>
                    <option value="Card"   <?= $method_f==='Card'  ?'selected':'' ?>>Card</option>
                    <option value="Online" <?= $method_f==='Online'?'selected':'' ?>>Online</option>
                    <option value="Wallet" <?= $method_f==='Wallet'?'selected':'' ?>>Wallet</option>
                </select>
            </div>
            <button type="submit" class="btn-primary-ff" style="padding:10px 20px"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="payments.php" class="btn-ghost" style="padding:10px 14px">Clear</a>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title">All Transactions</div>
        <span style="font-size:.8rem;color:var(--muted)"><?= count($payments) ?> record<?= count($payments)!=1?'s':'' ?></span>
    </div>
    <?php if (empty($payments)): ?>
    <div class="empty-state">
        <div class="empty-icon">💳</div>
        <div class="empty-title">No payments found</div>
        <div class="empty-sub">No transactions match your filters</div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pay ID</th><th>Order</th><th>Customer</th>
                    <th>Amount</th><th>Method</th><th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($payments as $p):
                $ps = strtolower($p['payment_status']);
            ?>
            <tr>
                <td style="color:var(--muted)">#<?= $p['id'] ?></td>
                <td><a href="order_detail.php?id=<?= $p['order_id'] ?>" style="color:var(--accent);text-decoration:none">#<?= $p['order_id'] ?></a></td>
                <td>
                    <div style="font-weight:500;font-size:.875rem"><?= htmlspecialchars($p['full_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($p['email']) ?></div>
                </td>
                <td style="font-weight:700;color:var(--gold)">$<?= number_format($p['amount'],2) ?></td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:6px;padding:4px 10px;font-size:.8rem">
                        <i class="bi bi-<?= $p['payment_method']==='Card'?'credit-card':($p['payment_method']==='Online'?'globe':($p['payment_method']==='Wallet'?'wallet2':'cash-coin')) ?>"></i>
                        <?= $p['payment_method'] ?>
                    </span>
                </td>
                <td>
                    <span class="badge-status badge-<?= $ps ?>">
                        <?= $p['payment_status'] ?>
                    </span>
                </td>
                <td style="color:var(--muted);font-size:.8rem">
                    <?= date('M j, Y', strtotime($p['payment_date'])) ?><br>
                    <span style="font-size:.72rem"><?= date('g:i a', strtotime($p['payment_date'])) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- TOTAL ROW -->
    <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:20px">
        <span style="color:var(--muted);font-size:.875rem">Showing <?= count($payments) ?> records</span>
        <span style="font-weight:700;color:var(--gold)">
            Total: $<?= number_format(array_sum(array_column($payments,'amount')),2) ?>
        </span>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>