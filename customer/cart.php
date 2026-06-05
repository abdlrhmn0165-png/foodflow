<?php
// ============================================================
// FILE: customer/cart.php
// FOLDER: foodflow/customer/cart.php
// PURPOSE: Cart view, quantity update, remove item, place order
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireCustomer();
$page_title = 'My Cart';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$msg = ''; $err = '';

// ── REMOVE ITEM ───────────────────────────────────────────
if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$rid]);
    header('Location: cart.php'); exit;
}

// ── UPDATE QTY ────────────────────────────────────────────
if (isset($_POST['update_qty'])) {
    foreach ($_POST['qty'] as $id => $q) {
        $id = (int)$id; $q = max(1,(int)$q);
        if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]['qty'] = $q;
    }
    $msg = 'Cart updated.';
}

// ── PLACE ORDER ───────────────────────────────────────────
if (isset($_POST['place_order'])) {
    $method = $_POST['payment_method'] ?? 'Cash';
    if (empty($_SESSION['cart'])) {
        $err = 'Your cart is empty.';
    } else {
        // Recalculate total from DB prices (never trust session prices in production)
        $total = 0;
        $valid_items = [];
        foreach ($_SESSION['cart'] as $cart_item) {
            $s = $pdo->prepare("SELECT id,price FROM menu_items WHERE id=? AND status='Available'");
            $s->execute([$cart_item['id']]);
            $mi = $s->fetch();
            if ($mi) {
                $valid_items[] = ['id'=>$mi['id'],'price'=>$mi['price'],'qty'=>$cart_item['qty']];
                $total += $mi['price'] * $cart_item['qty'];
            }
        }
        if (empty($valid_items)) {
            $err = 'Some items are no longer available.';
        } else {
            // Insert order
            $pdo->prepare("INSERT INTO orders (user_id,total_amount,order_status) VALUES (?,?,'Pending')")->execute([$_SESSION['user_id'],$total]);
            $order_id = $pdo->lastInsertId();
            // Insert order items
            $oi_stmt = $pdo->prepare("INSERT INTO order_items (order_id,menu_item_id,quantity,price) VALUES (?,?,?,?)");
            foreach($valid_items as $vi) $oi_stmt->execute([$order_id,$vi['id'],$vi['qty'],$vi['price']]);
            // Insert payment
            $pdo->prepare("INSERT INTO payments (order_id,amount,payment_method,payment_status) VALUES (?,?,?,'Pending')")->execute([$order_id,$total,$method]);
            // Notification
            $pdo->prepare("INSERT INTO notifications (title,message) VALUES (?,?)")->execute(["New Order #$order_id","New order from ".$_SESSION['full_name']." for \$$total"]);
            logActivity($pdo,$_SESSION['user_id'],"Placed order #$order_id");
            // Clear cart
            $_SESSION['cart'] = [];
            header("Location: orders.php?success=$order_id"); exit;
        }
    }
}

// ── COMPUTE TOTALS ────────────────────────────────────────
$cart = $_SESSION['cart'];
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
$cart_count = array_sum(array_column($cart,'qty'));

include 'includes/header.php';
?>

<div class="page-header">
    <h1>My Cart 🛒</h1>
    <p>Review your items before placing the order</p>
</div>

<?php if ($msg): ?><div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-ff alert-ff-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<?php if (empty($cart)): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-icon">🛒</div>
        <div class="empty-title">Your cart is empty</div>
        <div class="empty-sub"><a href="menu.php" class="btn-ff" style="text-decoration:none;display:inline-block;margin-top:16px;padding:10px 24px">Browse Menu</a></div>
    </div>
</div>
<?php else: ?>
<div class="row g-3">
    <!-- CART ITEMS -->
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-cart3 me-2" style="color:var(--accent)"></i>Cart Items (<?= $cart_count ?>)</div>
                <a href="menu.php" class="btn-ghost-ff" style="padding:6px 12px;font-size:.78rem"><i class="bi bi-plus me-1"></i>Add More</a>
            </div>
            <form method="POST">
                <table class="data-table">
                    <thead><tr><th>Item</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody>
                    <?php
                    $food_emojis=['🍔','🍕','🍝','🥗','🍰','🥤','🫓','🥩'];
                    foreach($cart as $cid => $ci):
                        // Fetch live item data
                        $s = $pdo->prepare("SELECT m.*,c.category_id FROM menu_items m JOIN categories c ON m.category_id=c.id WHERE m.id=?");
                        $s->execute([$ci['id']]); $live = $s->fetch();
                        $emoji = $food_emojis[(($live['category_id'] ?? 1)-1) % count($food_emojis)];
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:44px;height:44px;background:var(--surface);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0"><?= $emoji ?></div>
                                <div>
                                    <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($ci['name']) ?></div>
                                    <?php if ($live && $live['status']==='Out of Stock'): ?>
                                    <span style="color:var(--danger);font-size:.72rem"><i class="bi bi-exclamation-triangle me-1"></i>Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--muted)">$<?= number_format($ci['price'],2) ?></td>
                        <td>
                            <input type="number" name="qty[<?= $cid ?>]" value="<?= $ci['qty'] ?>" min="1" max="20"
                                   style="width:65px;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:8px;padding:6px 8px;color:var(--text);text-align:center">
                        </td>
                        <td style="color:var(--gold);font-weight:700">$<?= number_format($ci['price']*$ci['qty'],2) ?></td>
                        <td>
                            <a href="cart.php?remove=<?= $cid ?>" style="color:var(--danger);text-decoration:none;font-size:1rem" title="Remove">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding:16px 20px;border-top:1px solid var(--border)">
                    <button type="submit" name="update_qty" class="btn-ghost-ff">
                        <i class="bi bi-arrow-repeat"></i> Update Quantities
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ORDER SUMMARY -->
    <div class="col-lg-4">
        <div class="panel mb-3">
            <div class="panel-header"><div class="panel-title">Order Summary</div></div>
            <div style="padding:20px">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.875rem">
                    <span style="color:var(--muted)">Subtotal</span>
                    <span>$<?= number_format($subtotal,2) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.875rem">
                    <span style="color:var(--muted)">Tax (0%)</span>
                    <span>$0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 0;font-size:1.1rem;font-weight:800;color:var(--gold);font-family:'Syne',sans-serif">
                    <span>Total</span>
                    <span>$<?= number_format($subtotal,2) ?></span>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><div class="panel-title"><i class="bi bi-credit-card me-2" style="color:var(--accent)"></i>Payment</div></div>
            <div style="padding:20px">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Cash">💵 Cash on Delivery</option>
                            <option value="Card">💳 Credit/Debit Card</option>
                            <option value="Online">🌐 Online Transfer</option>
                            <option value="Wallet">👛 Digital Wallet</option>
                        </select>
                    </div>
                    <div style="background:rgba(249,115,22,0.06);border:1px solid rgba(249,115,22,0.15);border-radius:10px;padding:12px;margin-bottom:16px;font-size:.8rem;color:var(--muted)">
                        <i class="bi bi-shield-check me-1" style="color:var(--success)"></i>
                        Your order will be confirmed immediately after placement.
                    </div>
                    <button type="submit" name="place_order" class="btn-ff w-100" style="padding:13px;font-size:1rem">
                        <i class="bi bi-bag-check me-2"></i>Place Order · $<?= number_format($subtotal,2) ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>