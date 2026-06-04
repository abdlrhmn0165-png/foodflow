<?php
// ============================================================
// FILE: admin/customers.php
// PURPOSE: View all registered customers and their order stats
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Customers';

$search = trim($_GET['search'] ?? '');
$sql = "SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total_amount),0) AS total_spent
        FROM users u LEFT JOIN orders o ON o.user_id=u.id
        WHERE u.role='customer'";
$params = [];
if ($search) { $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include 'includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb-ff"><a href="dashboard.php">Dashboard</a> <i class="bi bi-chevron-right" style="font-size:.6rem"></i> <span>Customers</span></div>
    <h1>Customers</h1>
    <p>All registered customers and their activity</p>
</div>

<div class="panel mb-3">
    <div style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px">
            <div style="position:relative;flex:1">
                <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                <input type="text" name="search" class="form-control" placeholder="Search customers…" value="<?= htmlspecialchars($search) ?>" style="padding-left:36px">
            </div>
            <button type="submit" class="btn-primary-ff" style="padding:10px 20px">Search</button>
            <?php if ($search): ?><a href="customers.php" class="btn-ghost" style="padding:10px 14px">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">All Customers</div>
        <span style="font-size:.8rem;color:var(--muted)"><?= count($customers) ?> registered</span>
    </div>
    <?php if (empty($customers)): ?>
    <div class="empty-state"><div class="empty-icon">👥</div><div class="empty-title">No customers found</div></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Joined</th><th>Orders</th><th>Total Spent</th></tr></thead>
            <tbody>
            <?php foreach($customers as $c): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#ea580c);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;flex-shrink:0">
                            <?= strtoupper(substr($c['full_name'],0,1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:500"><?= htmlspecialchars($c['full_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($c['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td style="color:var(--muted);font-size:.82rem"><?= date('M j, Y',strtotime($c['created_at'])) ?></td>
                <td><span style="color:var(--info);font-weight:600"><?= $c['order_count'] ?></span></td>
                <td style="color:var(--gold);font-weight:700">$<?= number_format($c['total_spent'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>