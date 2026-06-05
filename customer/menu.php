<?php
// ============================================================
// FILE: customer/menu.php
// FOLDER: foodflow/customer/menu.php
// PURPOSE: Browse full menu, filter by category, add items to cart (session-based)
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireCustomer();
$page_title = 'Browse Menu';

// ── CART stored in session ─────────────────────────────────
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Add to cart
if (isset($_GET['add'])) {
    $item_id = (int)$_GET['add'];
    $check = $pdo->prepare("SELECT id,item_name,price FROM menu_items WHERE id=? AND status='Available'");
    $check->execute([$item_id]);
    $item = $check->fetch();
    if ($item) {
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['qty']++;
        } else {
            $_SESSION['cart'][$item_id] = ['id'=>$item_id,'name'=>$item['item_name'],'price'=>$item['price'],'qty'=>1];
        }
        $msg = htmlspecialchars($item['item_name']) . ' added to cart!';
    }
}

// ── FILTERS ───────────────────────────────────────────────
$cat_f    = (int)($_GET['cat'] ?? 0);
$search   = trim($_GET['search'] ?? '');

$sql = "SELECT m.*,c.category_name FROM menu_items m JOIN categories c ON m.category_id=c.id WHERE m.status='Available'";
$params = [];
if ($cat_f)  { $sql .= " AND m.category_id=?"; $params[] = $cat_f; }
if ($search) { $sql .= " AND (m.item_name LIKE ? OR m.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY c.category_name, m.item_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$cats = $pdo->query("SELECT c.*,COUNT(m.id) AS cnt FROM categories c JOIN menu_items m ON m.category_id=c.id WHERE m.status='Available' GROUP BY c.id ORDER BY c.category_name")->fetchAll();
$cart_count = array_sum(array_column($_SESSION['cart'],'qty'));
$food_emojis = ['🍔','🍕','🍝','🥗','🍰','🥤','🫓','🥩'];

include 'includes/header.php';
?>

<div class="page-header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
        <div>
            <h1>Browse Menu 🍽️</h1>
            <p>Discover our full selection of handcrafted dishes</p>
        </div>
        <a href="cart.php" class="btn-ff" style="text-decoration:none;display:flex;align-items:center;gap:8px">
            <i class="bi bi-cart3"></i> Cart
            <?php if ($cart_count > 0): ?>
            <span style="background:rgba(0,0,0,0.25);border-radius:50px;padding:1px 8px;font-size:.75rem"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<?php if (!empty($msg)): ?>
<div class="alert-ff alert-ff-success"><i class="bi bi-cart-check"></i> <?= $msg ?></div>
<?php endif; ?>

<!-- SEARCH + FILTERS -->
<div class="panel mb-3">
    <div style="padding:14px 18px">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
            <div style="position:relative;flex:1;min-width:180px">
                <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.9rem"></i>
                <input type="text" name="search" class="form-control" placeholder="Search dishes…" value="<?= htmlspecialchars($search) ?>" style="padding-left:34px">
            </div>
            <input type="hidden" name="cat" value="<?= $cat_f ?>">
            <button type="submit" class="btn-ff" style="padding:10px 18px">Search</button>
            <?php if ($search || $cat_f): ?><a href="menu.php" class="btn-ghost-ff">Clear</a><?php endif; ?>
        </form>
    </div>
</div>

<!-- CATEGORY PILLS -->
<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px">
    <a href="menu.php<?= $search?'?search='.urlencode($search):'' ?>" class="badge-status <?= !$cat_f?'badge-available':'' ?>"
       style="text-decoration:none;padding:7px 16px;font-size:.8rem;<?= $cat_f?'background:rgba(255,255,255,0.05);color:var(--muted);border:1px solid var(--border)':'' ?>">
       All
    </a>
    <?php foreach($cats as $cat): ?>
    <a href="menu.php?cat=<?= $cat['id'] ?><?= $search?'&search='.urlencode($search):'' ?>"
       class="badge-status <?= $cat_f==$cat['id']?'badge-available':'' ?>"
       style="text-decoration:none;padding:7px 16px;font-size:.8rem;<?= $cat_f!=$cat['id']?'background:rgba(255,255,255,0.05);color:var(--muted);border:1px solid var(--border)':'' ?>">
        <?= htmlspecialchars($cat['category_name']) ?> <span style="opacity:.6">(<?= $cat['cnt'] ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- RESULTS COUNT -->
<div style="font-size:.82rem;color:var(--muted);margin-bottom:16px"><?= count($items) ?> item<?= count($items)!=1?'s':'' ?> found</div>

<?php if (empty($items)): ?>
<div class="panel">
    <div class="empty-state">
        <div class="empty-icon">🍽️</div>
        <div class="empty-title">No items found</div>
        <div class="empty-sub">Try a different search or category</div>
    </div>
</div>
<?php else: ?>
<!-- MENU GRID -->
<div class="row g-3">
    <?php foreach($items as $item):
        $emoji = $food_emojis[($item['category_id']-1) % count($food_emojis)];
        $in_cart = isset($_SESSION['cart'][$item['id']]);
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="panel" style="transition:.35s;height:100%"
             onmouseover="this.style.borderColor='rgba(249,115,22,0.3)';this.style.transform='translateY(-4px)'"
             onmouseout="this.style.borderColor='var(--border)';this.style.transform='none'">
            <!-- Image -->
            <div style="height:160px;background:linear-gradient(135deg,#1a2030,#0f1520);display:flex;align-items:center;justify-content:center;font-size:4rem;position:relative">
                <?= $emoji ?>
                <span style="position:absolute;top:10px;right:10px;background:rgba(249,115,22,0.85);color:#fff;border-radius:50px;font-size:.68rem;padding:3px 9px;font-weight:600"><?= htmlspecialchars($item['category_name']) ?></span>
                <?php if ($in_cart): ?>
                <span style="position:absolute;top:10px;left:10px;background:rgba(34,197,94,0.85);color:#fff;border-radius:50px;font-size:.68rem;padding:3px 9px;font-weight:600"><i class="bi bi-check2"></i> In Cart</span>
                <?php endif; ?>
            </div>
            <!-- Body -->
            <div style="padding:16px">
                <div style="font-weight:700;font-size:.95rem;margin-bottom:5px"><?= htmlspecialchars($item['item_name']) ?></div>
                <div style="font-size:.8rem;color:var(--muted);line-height:1.5;margin-bottom:14px;min-height:36px"><?= htmlspecialchars(substr($item['description'],0,80)) ?>…</div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <div style="font-size:1.15rem;font-weight:800;color:var(--accent);font-family:'Syne',sans-serif">$<?= number_format($item['price'],2) ?></div>
                    <a href="menu.php?add=<?= $item['id'] ?><?= $cat_f?'&cat='.$cat_f:'' ?><?= $search?'&search='.urlencode($search):'' ?>"
                       class="btn-ff" style="text-decoration:none;padding:8px 16px;font-size:.8rem;border-radius:8px">
                        <i class="bi bi-cart-plus me-1"></i><?= $in_cart ? 'Add More' : 'Add to Cart' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- STICKY CART BAR -->
<?php if ($cart_count > 0): ?>
<div style="position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:1000;animation:slideUp .3s ease">
    <a href="cart.php" class="btn-ff" style="text-decoration:none;padding:14px 32px;font-size:1rem;border-radius:50px;box-shadow:0 8px 32px rgba(249,115,22,0.4);display:flex;align-items:center;gap:10px">
        <i class="bi bi-cart3"></i>
        View Cart &bull; <?= $cart_count ?> item<?= $cart_count!=1?'s':'' ?>
    </a>
</div>
<style>@keyframes slideUp{from{transform:translateX(-50%) translateY(20px);opacity:0}to{transform:translateX(-50%) translateY(0);opacity:1}}</style>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>