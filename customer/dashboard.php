<?php
// ============================================================
// FILE: customer/dashboard.php
// FOLDER: foodflow/customer/dashboard.php
// PURPOSE: Customer home dashboard — stats, recent orders, featured menu
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireCustomer();
$page_title = 'My Dashboard';

$uid = $_SESSION['user_id'];

// Stats
$my_orders    = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $my_orders->execute([$uid]); $my_orders = $my_orders->fetchColumn();
$my_spent     = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND order_status='Delivered'"); $my_spent->execute([$uid]); $my_spent = $my_spent->fetchColumn();
$pending_cnt  = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=? AND order_status NOT IN ('Delivered','Cancelled')"); $pending_cnt->execute([$uid]); $pending_cnt = $pending_cnt->fetchColumn();
$avail_items  = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE status='Available'")->fetchColumn();

// Recent orders
$recent = $pdo->prepare("SELECT o.*,(SELECT COUNT(*) FROM order_items WHERE order_id=o.id) AS item_count FROM orders o WHERE o.user_id=? ORDER BY o.created_at DESC LIMIT 5");
$recent->execute([$uid]); $recent = $recent->fetchAll();

// Featured menu (random 6)
$featured = $pdo->query("SELECT m.*,c.category_name FROM menu_items m JOIN categories c ON m.category_id=c.id WHERE m.status='Available' ORDER BY RAND() LIMIT 6")->fetchAll();

$food_emojis = ['🍔','🍕','🍝','🥗','🍰','🥤','🫓','🥩'];

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Welcome back, <?= htmlspecialchars(explode(' ',$_SESSION['full_name'])[0]) ?> 👋</h1>
    <p>Here's a summary of your activity</p>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="--glow-color:rgba(249,115,22,0.1)">
            <div class="stat-icon-wrap" style="background:rgba(249,115,22,0.12);color:var(--accent)"><i class="bi bi-bag-fill"></i></div>
            <div class="stat-label">My Orders</div>
            <div class="stat-value" style="color:var(--accent)"><?= $my_orders ?></div>
            <div style="font-size:.75rem;color:var(--muted);margin-top:6px">Total placed</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:rgba(251,191,36,0.12);color:var(--gold)"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-label">Total Spent</div>
            <div class="stat-value" style="color:var(--gold)">$<?= number_format($my_spent,0) ?></div>
            <div style="font-size:.75rem;color:var(--muted);margin-top:6px">On delivered orders</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:rgba(56,189,248,0.12);color:var(--info)"><i class="bi bi-clock-history"></i></div>
            <div class="stat-label">Active Orders</div>
            <div class="stat-value" style="color:var(--info)"><?= $pending_cnt ?></div>
            <div style="font-size:.75rem;color:var(--muted);margin-top:6px">In progress</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.12);color:var(--success)"><i class="bi bi-grid-3x3-gap-fill"></i></div>
            <div class="stat-label">Menu Items</div>
            <div class="stat-value" style="color:var(--success)"><?= $avail_items ?></div>
            <div style="font-size:.75rem;color:var(--muted);margin-top:6px">Available now</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Recent Orders -->
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-clock-history me-2" style="color:var(--accent)"></i>Recent Orders</div>
                <a href="orders.php" class="btn-ghost-ff" style="padding:6px 12px;font-size:.78rem">View All</a>
            </div>
            <?php if (empty($recent)): ?>
            <div class="empty-state">
                <div class="empty-icon">🛍️</div>
                <div class="empty-title">No orders yet</div>
                <div class="empty-sub"><a href="menu.php" style="color:var(--accent)">Browse the menu</a> to place your first order!</div>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Order</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach($recent as $ord):
                    $sc = strtolower(str_replace(' ','',$ord['order_status']));
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--accent)">#<?= $ord['id'] ?></div>
                        <div style="font-size:.72rem;color:var(--muted)"><?= date('M j, Y',strtotime($ord['created_at'])) ?></div>
                    </td>
                    <td style="color:var(--muted)"><?= $ord['item_count'] ?> item<?= $ord['item_count']!=1?'s':'' ?></td>
                    <td style="color:var(--gold);font-weight:700">$<?= number_format($ord['total_amount'],2) ?></td>
                    <td><span class="badge-status badge-<?= $sc ?>"><?= $ord['order_status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-5">
        <div class="panel" style="height:100%">
            <div class="panel-header"><div class="panel-title">⚡ Quick Actions</div></div>
            <div style="padding:20px;display:flex;flex-direction:column;gap:10px">
                <a href="menu.php" class="btn-ff" style="text-align:center;text-decoration:none;display:block;padding:14px">
                    <i class="bi bi-grid me-2"></i>Browse Full Menu
                </a>
                <a href="cart.php" class="btn-ghost-ff" style="justify-content:center;padding:13px">
                    <i class="bi bi-cart3"></i> View Cart
                </a>
                <a href="orders.php" class="btn-ghost-ff" style="justify-content:center;padding:13px">
                    <i class="bi bi-bag-check"></i> Order History
                </a>
                <a href="feedback.php" class="btn-ghost-ff" style="justify-content:center;padding:13px">
                    <i class="bi bi-star"></i> Leave Feedback
                </a>
                <a href="profile.php" class="btn-ghost-ff" style="justify-content:center;padding:13px">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Featured Menu -->
<div class="panel-header" style="background:transparent;border:none;padding:0;margin-bottom:16px">
    <div class="panel-title" style="font-size:1.1rem"><i class="bi bi-stars me-2" style="color:var(--gold)"></i>Featured Today</div>
    <a href="menu.php" class="btn-ghost-ff" style="padding:6px 14px;font-size:.78rem">Full Menu</a>
</div>
<div class="row g-3">
    <?php foreach($featured as $item):
        $emoji = $food_emojis[($item['category_id']-1) % count($food_emojis)];
    ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="panel" style="transition:.3s;cursor:pointer" onmouseover="this.style.borderColor='rgba(249,115,22,0.3)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='var(--border)';this.style.transform='none'">
            <div style="height:90px;background:linear-gradient(135deg,#1a2030,#0f1520);display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
                <?= $emoji ?>
            </div>
            <div style="padding:12px">
                <div style="font-size:.8rem;font-weight:600;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($item['item_name']) ?></div>
                <div style="color:var(--accent);font-weight:700;font-size:.9rem">$<?= number_format($item['price'],2) ?></div>
                <a href="menu.php?add=<?= $item['id'] ?>" class="btn-ff" style="display:block;text-align:center;padding:7px;font-size:.75rem;margin-top:8px;text-decoration:none;border-radius:8px">
                    Add to Cart
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>