<?php
// ============================================================
// FILE: admin/order_detail.php
// PURPOSE: Detailed view of a single order with items
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: orders.php'); exit; }

$order = $pdo->prepare("SELECT o.*,u.full_name,u.email FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
$order->execute([$id]);
$order = $order->fetch();
if (!$order) { header('Location: orders.php'); exit; }

$items = $pdo->prepare("SELECT oi.*,m.item_name FROM order_items oi JOIN menu_items m ON oi.menu_item_id=m.id WHERE oi.order_id=?");
$items->execute([$id]);
$items = $items->fetchAll();

$payment = $pdo->prepare("SELECT * FROM payments WHERE order_id=? LIMIT 1");
$payment->execute([$id]);
$payment = $payment->fetch();

$page_title = "Order #$id";
include 'includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb-ff">
        <a href="dashboard.php">Dashboard</a> <i class="bi bi-chevron-right" style="font-size:.6rem"></i>
        <a href="orders.php">Orders</a> <i class="bi bi-chevron-right" style="font-size:.6rem"></i>
        <span>Order #<?= $id ?></span>
    </div>
    <h1>Order #<?= $id ?></h1>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel mb-3">
            <div class="panel-header"><div class="panel-title">Order Items</div></div>
            <table class="data-table">
                <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach($items as $it): ?>
                <tr>
                    <td style="font-weight:500"><?= htmlspecialchars($it['item_name']) ?></td>
                    <td style="color:var(--info)"><?= $it['quantity'] ?></td>
                    <td>$<?= number_format($it['price'],2) ?></td>
                    <td style="color:var(--gold);font-weight:600">$<?= number_format($it['price']*$it['quantity'],2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div style="padding:16px 20px;text-align:right;border-top:1px solid var(--border)">
                <span style="font-size:1.1rem;font-weight:700;color:var(--gold)">Total: $<?= number_format($order['total_amount'],2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel mb-3">
            <div class="panel-header"><div class="panel-title">Customer</div></div>
            <div style="padding:20px">
                <div style="margin-bottom:12px"><span style="color:var(--muted);font-size:.8rem">Name</span><br><?= htmlspecialchars($order['full_name']) ?></div>
                <div style="margin-bottom:12px"><span style="color:var(--muted);font-size:.8rem">Email</span><br><?= htmlspecialchars($order['email']) ?></div>
                <div><span style="color:var(--muted);font-size:.8rem">Placed</span><br><?= date('M j, Y g:i a',strtotime($order['created_at'])) ?></div>
            </div>
        </div>
        <div class="panel mb-3">
            <div class="panel-header"><div class="panel-title">Status</div></div>
            <div style="padding:20px">
                <?php $sc=strtolower(str_replace(' ','',$order['order_status'])); ?>
                <span class="badge-status badge-<?= $sc ?>"><?= $order['order_status'] ?></span>
                <div style="margin-top:16px"><a href="orders.php" class="btn-ghost" style="font-size:.85rem">← Back to Orders</a></div>
            </div>
        </div>
        <?php if ($payment): ?>
        <div class="panel">
            <div class="panel-header"><div class="panel-title">Payment</div></div>
            <div style="padding:20px">
                <div style="margin-bottom:10px"><span style="color:var(--muted);font-size:.8rem">Method</span><br><?= $payment['payment_method'] ?></div>
                <div style="margin-bottom:10px"><span style="color:var(--muted);font-size:.8rem">Status</span><br>
                    <span class="badge-status badge-<?= strtolower($payment['payment_status']) ?>"><?= $payment['payment_status'] ?></span>
                </div>
                <div><span style="color:var(--muted);font-size:.8rem">Amount</span><br><span style="color:var(--gold);font-weight:700">$<?= number_format($payment['amount'],2) ?></span></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>