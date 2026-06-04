<?php
// ============================================================
// FILE: admin/orders.php
// PURPOSE: View and manage all orders, update order status
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Orders';

$msg = '';

// ── UPDATE STATUS ─────────────────────────────────────────
if (isset($_POST['update_status'])) {
    $id     = (int)$_POST['order_id'];
    $status = $_POST['order_status'];
    $allowed = ['Pending','Confirmed','Preparing','Ready','Delivered','Cancelled'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE orders SET order_status=? WHERE id=?")->execute([$status,$id]);
        logActivity($pdo, $_SESSION['user_id'], "Updated order #$id status to $status");
        $msg = "Order #$id status updated to $status.";
    }
}

// ── FILTERS ───────────────────────────────────────────────
$status_f = $_GET['status'] ?? '';
$search   = trim($_GET['search'] ?? '');

$sql = "SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id=u.id WHERE 1=1";
$params = [];
if ($status_f) { $sql .= " AND o.order_status=?"; $params[] = $status_f; }
if ($search)   { $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// counts per status
$status_counts = [];
$sc = $pdo->query("SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status")->fetchAll();
foreach($sc as $s) $status_counts[$s['order_status']] = $s['cnt'];

include 'includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb-ff"><a href="dashboard.php">Dashboard</a> <i class="bi bi-chevron-right" style="font-size:.6rem"></i> <span>Orders</span></div>
    <h1>Orders</h1>
    <p>Track and manage all customer orders</p>
</div>

<?php if ($msg): ?><div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<!-- STATUS FILTER PILLS -->
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px">
    <a href="orders.php" class="badge-status <?= !$status_f ? 'badge-available' : '' ?>" style="text-decoration:none;padding:8px 16px;font-size:.8rem">All (<?= array_sum($status_counts) ?>)</a>
    <?php
    $pill_styles = ['Pending'=>'badge-pending','Confirmed'=>'badge-confirmed','Preparing'=>'badge-preparing','Ready'=>'badge-ready','Delivered'=>'badge-delivered','Cancelled'=>'badge-cancelled'];
    foreach($pill_styles as $st => $cls): ?>
    <a href="orders.php?status=<?= $st ?>" class="badge-status <?= $status_f===$st ? $cls : '' ?>" style="text-decoration:none;padding:8px 16px;font-size:.8rem;<?= $status_f!==$st ? 'background:rgba(255,255,255,0.04);color:var(--muted);border:1px solid var(--border)' : '' ?>">
        <?= $st ?> (<?= $status_counts[$st] ?? 0 ?>)
    </a>
    <?php endforeach; ?>
</div>

<!-- SEARCH BAR -->
<div class="panel mb-3">
    <div style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status_f) ?>">
            <div style="position:relative;flex:1">
                <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                <input type="text" name="search" class="form-control" placeholder="Search by customer name or email…" value="<?= htmlspecialchars($search) ?>" style="padding-left:36px">
            </div>
            <button type="submit" class="btn-primary-ff" style="padding:10px 20px">Search</button>
            <a href="orders.php" class="btn-ghost" style="padding:10px 14px">Clear</a>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Order List</div>
        <span style="font-size:.8rem;color:var(--muted)"><?= count($orders) ?> order<?= count($orders)!=1?'s':'' ?></span>
    </div>
    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <div class="empty-title">No orders found</div>
        <div class="empty-sub">No orders match your current filters</div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach($orders as $ord): 
                $sc2 = strtolower(str_replace(' ','',$ord['order_status']));
            ?>
            <tr>
                <td style="font-weight:700;color:var(--accent)">#<?= $ord['id'] ?></td>
                <td>
                    <div style="font-weight:500"><?= htmlspecialchars($ord['full_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($ord['email']) ?></div>
                </td>
                <td style="color:var(--gold);font-weight:700">$<?= number_format($ord['total_amount'],2) ?></td>
                <td><span class="badge-status badge-<?= $sc2 ?>"><?= $ord['order_status'] ?></span></td>
                <td style="color:var(--muted);font-size:.82rem"><?= date('M j, Y g:i a', strtotime($ord['created_at'])) ?></td>
                <td>
                    <button onclick="openStatusModal(<?= $ord['id'] ?>, '<?= $ord['order_status'] ?>')" class="btn-icon" title="Update Status">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <a href="order_detail.php?id=<?= $ord['id'] ?>" class="btn-icon" title="View Detail"><i class="bi bi-eye"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- STATUS MODAL -->
<div id="statusModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px" class="d-none">
    <div style="background:var(--card);border:1px solid var(--border);border-radius:20px;padding:32px;width:100%;max-width:380px">
        <h5 class="font-syne mb-3"><i class="bi bi-pencil-square me-2" style="color:var(--accent)"></i>Update Order Status</h5>
        <form method="POST">
            <input type="hidden" name="order_id" id="modal_order_id">
            <div class="mb-3">
                <label class="form-label">New Status</label>
                <select name="order_status" id="modal_status" class="form-select">
                    <option>Pending</option>
                    <option>Confirmed</option>
                    <option>Preparing</option>
                    <option>Ready</option>
                    <option>Delivered</option>
                    <option>Cancelled</option>
                </select>
            </div>
            <div style="display:flex;gap:10px">
                <button type="submit" name="update_status" class="btn-primary-ff" style="flex:1">Update</button>
                <button type="button" onclick="document.getElementById('statusModal').classList.add('d-none')" class="btn-ghost" style="flex:1">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStatusModal(id, status) {
    document.getElementById('modal_order_id').value = id;
    document.getElementById('modal_status').value = status;
    document.getElementById('statusModal').classList.remove('d-none');
    document.getElementById('statusModal').style.display = 'flex';
}
</script>

<?php include 'includes/footer.php'; ?>