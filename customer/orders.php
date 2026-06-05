<?php
// ============================================================
// FILE: customer/orders.php
// FOLDER: foodflow/customer/orders.php
// PURPOSE: Order history, detailed view, cancel pending orders
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireCustomer();
$page_title = 'My Orders';

$uid = $_SESSION['user_id'];
$msg = ''; $err = '';

// SUCCESS message after checkout
if (isset($_GET['success'])) {
    $msg = '✅ Order #' . (int)$_GET['success'] . ' placed successfully! We\'re preparing your food.';
}

// CANCEL order
if (isset($_GET['cancel'])) {
    $oid = (int)$_GET['cancel'];
    $check = $pdo->prepare("SELECT id,order_status FROM orders WHERE id=? AND user_id=?");
    $check->execute([$oid,$uid]);
    $o = $check->fetch();
    if ($o && $o['order_status'] === 'Pending') {
        $pdo->prepare("UPDATE orders SET order_status='Cancelled' WHERE id=?")->execute([$oid]);
        logActivity($pdo,$uid,"Cancelled order #$oid");
        $msg = "Order #$oid has been cancelled.";
    } else {
        $err = "This order cannot be cancelled (only Pending orders can be cancelled).";
    }
}

// Fetch orders
$status_f = $_GET['status'] ?? '';
$sql = "SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id=o.id) AS item_count FROM orders o WHERE o.user_id=? ";
$params = [$uid];
if ($status_f) { $sql .= " AND o.order_status=?"; $params[] = $status_f; }
$sql .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Counts
$counts = [];
$sc = $pdo->prepare("SELECT order_status,COUNT(*) AS cnt FROM orders WHERE user_id=? GROUP BY order_status");
$sc->execute([$uid]);
foreach($sc->fetchAll() as $r) $counts[$r['order_status']] = $r['cnt'];

// Detail view
$detail = null;
$detail_items = [];
if (isset($_GET['view'])) {
    $did = (int)$_GET['view'];
    $ds = $pdo->prepare("SELECT o.*,p.payment_method,p.payment_status FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE o.id=? AND o.user_id=?");
    $ds->execute([$did,$uid]);
    $detail = $ds->fetch();
    if ($detail) {
        $di = $pdo->prepare("SELECT oi.*,m.item_name FROM order_items oi JOIN menu_items m ON oi.menu_item_id=m.id WHERE oi.order_id=?");
        $di->execute([$did]);
        $detail_items = $di->fetchAll();
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1>My Orders 📦</h1>
    <p>Track your current and past orders</p>
</div>

<?php if ($msg): ?><div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-ff alert-ff-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- FILTER PILLS -->
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px">
    <a href="orders.php" class="badge-status <?= !$status_f?'badge-available':'' ?>" style="text-decoration:none;padding:7px 16px;font-size:.8rem;<?= $status_f?'background:rgba(255,255,255,0.04);color:var(--muted);border:1px solid var(--border)':'' ?>">All (<?= array_sum($counts) ?>)</a>
    <?php foreach(['Pending'=>'badge-pending','Confirmed'=>'badge-confirmed','Preparing'=>'badge-preparing','Delivered'=>'badge-delivered','Cancelled'=>'badge-cancelled'] as $s=>$cls): ?>
    <a href="orders.php?status=<?= $s ?>" class="badge-status <?= $status_f===$s?$cls:'' ?>"
       style="text-decoration:none;padding:7px 16px;font-size:.8rem;<?= $status_f!==$s?'background:rgba(255,255,255,0.04);color:var(--muted);border:1px solid var(--border)':'' ?>">
        <?= $s ?> (<?= $counts[$s]??0 ?>)
    </a>
    <?php endforeach; ?>
</div>

<?php if (isset($detail) && $detail): ?>
<!-- ORDER DETAIL PANEL -->
<div class="panel mb-3" style="border-color:rgba(249,115,22,0.2)">
    <div class="panel-header">
        <div class="panel-title"><i class="bi bi-receipt me-2" style="color:var(--accent)"></i>Order #<?= $detail['id'] ?> Details</div>
        <a href="orders.php" class="btn-ghost-ff" style="padding:5px 12px;font-size:.78rem">← Back</a>
    </div>
    <div class="row g-0">
        <div class="col-md-8">
            <table class="data-table">
                <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach($detail_items as $di): ?>
                <tr>
                    <td style="font-weight:500"><?= htmlspecialchars($di['item_name']) ?></td>
                    <td style="color:var(--info)"><?= $di['quantity'] ?></td>
                    <td>$<?= number_format($di['price'],2) ?></td>
                    <td style="color:var(--gold);font-weight:600">$<?= number_format($di['price']*$di['quantity'],2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-4" style="border-left:1px solid var(--border);padding:20px">
            <div style="margin-bottom:12px"><span style="color:var(--muted);font-size:.75rem;display:block;margin-bottom:2px">Status</span>
                <?php $sc2=strtolower(str_replace(' ','',$detail['order_status'])); ?>
                <span class="badge-status badge-<?= $sc2 ?>"><?= $detail['order_status'] ?></span>
            </div>
            <div style="margin-bottom:12px"><span style="color:var(--muted);font-size:.75rem;display:block;margin-bottom:2px">Payment Method</span>
                <span style="font-weight:500"><?= $detail['payment_method'] ?? 'N/A' ?></span>
            </div>
            <div style="margin-bottom:12px"><span style="color:var(--muted);font-size:.75rem;display:block;margin-bottom:2px">Payment Status</span>
                <?php $ps=strtolower($detail['payment_status']??'pending'); ?>
                <span class="badge-status badge-<?= $ps ?>"><?= ucfirst($ps) ?></span>
            </div>
            <div style="margin-bottom:12px"><span style="color:var(--muted);font-size:.75rem;display:block;margin-bottom:2px">Date</span>
                <span><?= date('M j, Y g:i a',strtotime($detail['created_at'])) ?></span>
            </div>
            <div style="border-top:1px solid var(--border);padding-top:12px">
                <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:800;color:var(--gold);font-family:'Syne',sans-serif">
                    <span>Total</span><span>$<?= number_format($detail['total_amount'],2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ORDERS TABLE -->
<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Order History</div>
        <span style="font-size:.8rem;color:var(--muted)"><?= count($orders) ?> order<?= count($orders)!=1?'s':'' ?></span>
    </div>
    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <div class="empty-title">No orders found</div>
        <div class="empty-sub"><a href="menu.php" class="btn-ff" style="text-decoration:none;display:inline-block;margin-top:14px;padding:10px 22px">Order Something Delicious</a></div>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead><tr><th>Order ID</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($orders as $ord):
                $sc3=strtolower(str_replace(' ','',$ord['order_status']));
            ?>
            <tr>
                <td style="font-weight:700;color:var(--accent)">#<?= $ord['id'] ?></td>
                <td style="color:var(--muted)"><?= $ord['item_count'] ?> item<?= $ord['item_count']!=1?'s':'' ?></td>
                <td style="color:var(--gold);font-weight:700">$<?= number_format($ord['total_amount'],2) ?></td>
                <td><span class="badge-status badge-<?= $sc3 ?>"><?= $ord['order_status'] ?></span></td>
                <td style="color:var(--muted);font-size:.8rem"><?= date('M j, Y',strtotime($ord['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;align-items:center">
                        <a href="orders.php?view=<?= $ord['id'] ?><?= $status_f?'&status='.$status_f:'' ?>" style="color:var(--accent);text-decoration:none;font-size:.8rem;display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:rgba(249,115,22,0.08);border:1px solid rgba(249,115,22,0.2);border-radius:7px">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <?php if ($ord['order_status'] === 'Pending'): ?>
                        <a href="orders.php?cancel=<?= $ord['id'] ?>" onclick="return confirm('Cancel this order?')"
                           style="color:var(--danger);text-decoration:none;font-size:.8rem;display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:7px">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>