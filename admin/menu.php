<?php
// ============================================================
// FILE: admin/menu.php
// PURPOSE: Full CRUD for menu items (add/edit/delete/search/filter)
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Menu Items';

$msg = '';
$err = '';

// ── DELETE ────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM menu_items WHERE id=?")->execute([$id]);
    logActivity($pdo, $_SESSION['user_id'], "Deleted menu item #$id");
    $msg = 'Menu item deleted.';
}

// ── ADD / EDIT ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $item_name   = trim($_POST['item_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $status      = $_POST['status'] ?? 'Available';
    $image       = trim($_POST['image'] ?? 'default-food.jpg') ?: 'default-food.jpg';

    if (!$item_name || !$category_id || $price <= 0) {
        $err = 'Please fill all required fields correctly.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE menu_items SET item_name=?,category_id=?,description=?,price=?,status=?,image=? WHERE id=?");
            $stmt->execute([$item_name,$category_id,$description,$price,$status,$image,$id]);
            logActivity($pdo, $_SESSION['user_id'], "Updated menu item: $item_name");
            $msg = 'Menu item updated successfully.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO menu_items (item_name,category_id,description,price,status,image) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$item_name,$category_id,$description,$price,$status,$image]);
            logActivity($pdo, $_SESSION['user_id'], "Added new menu item: $item_name");
            $msg = 'Menu item added successfully.';
        }
    }
}

// ── FETCH EDIT DATA ───────────────────────────────────────
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id=?");
    $edit_stmt->execute([(int)$_GET['edit']]);
    $edit_item = $edit_stmt->fetch();
}

// ── FILTERS ───────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$cat_f    = (int)($_GET['cat'] ?? 0);
$status_f = $_GET['status'] ?? '';

$sql = "SELECT m.*, c.category_name FROM menu_items m JOIN categories c ON m.category_id=c.id WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND m.item_name LIKE ?"; $params[] = "%$search%"; }
if ($cat_f)  { $sql .= " AND m.category_id=?"; $params[] = $cat_f; }
if ($status_f) { $sql .= " AND m.status=?"; $params[] = $status_f; }
$sql .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$cats = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
$foodEmojis = ['🍔','🍕','🍝','🥗','🍰','🥤','🫓','🥩'];

include 'includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb-ff"><a href="dashboard.php">Dashboard</a> <i class="bi bi-chevron-right" style="font-size:.6rem"></i> <span>Menu Items</span></div>
    <h1>Menu Items</h1>
    <p>Manage your restaurant's food offerings</p>
</div>

<?php if ($msg): ?><div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-ff alert-ff-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="row g-3">
    <!-- FORM PANEL -->
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-<?= $edit_item ? 'pencil' : 'plus-circle' ?> me-2" style="color:var(--accent)"></i><?= $edit_item ? 'Edit Item' : 'Add New Item' ?></div>
                <?php if ($edit_item): ?>
                <a href="menu.php" style="font-size:.8rem;color:var(--muted);text-decoration:none">Cancel</a>
                <?php endif; ?>
            </div>
            <div style="padding:24px">
                <form method="POST">
                    <?php if ($edit_item): ?><input type="hidden" name="id" value="<?= $edit_item['id'] ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Item Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="item_name" class="form-control" placeholder="e.g. Classic Burger" value="<?= htmlspecialchars($edit_item['item_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span style="color:var(--danger)">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select category…</option>
                            <?php foreach($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($edit_item['category_id'] ?? 0)==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe the dish…"><?= htmlspecialchars($edit_item['description'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Price ($) <span style="color:var(--danger)">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0.01" placeholder="0.00" value="<?= $edit_item['price'] ?? '' ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Available" <?= ($edit_item['status'] ?? 'Available')==='Available'?'selected':'' ?>>Available</option>
                                <option value="Out of Stock" <?= ($edit_item['status'] ?? '')==='Out of Stock'?'selected':'' ?>>Out of Stock</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image Filename</label>
                        <input type="text" name="image" class="form-control" placeholder="burger.jpg" value="<?= htmlspecialchars($edit_item['image'] ?? '') ?>">
                        <div style="font-size:.75rem;color:var(--muted);margin-top:4px">Place images in assets/images/food/</div>
                    </div>
                    <button type="submit" class="btn-primary-ff w-100">
                        <i class="bi bi-<?= $edit_item ? 'check-lg' : 'plus-lg' ?> me-1"></i> <?= $edit_item ? 'Update Item' : 'Add Item' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST PANEL -->
    <div class="col-lg-8">
        <!-- Filter Bar -->
        <div class="panel mb-3">
            <div style="padding:16px 20px">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <div style="position:relative">
                            <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                            <input type="text" name="search" class="form-control" placeholder="Search items…" value="<?= htmlspecialchars($search) ?>" style="padding-left:36px">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="cat" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $cat_f==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Available" <?= $status_f==='Available'?'selected':'' ?>>Available</option>
                            <option value="Out of Stock" <?= $status_f==='Out of Stock'?'selected':'' ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn-primary-ff" style="padding:10px 16px;flex:1"><i class="bi bi-funnel"></i></button>
                        <a href="menu.php" class="btn-ghost" style="padding:10px 12px"><i class="bi bi-x-lg"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div class="panel-title">All Items</div>
                <span style="font-size:.8rem;color:var(--muted)"><?= count($items) ?> item<?= count($items)!=1?'s':'' ?></span>
            </div>
            <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-icon">🍽️</div>
                <div class="empty-title">No items found</div>
                <div class="empty-sub">Try adjusting your filters or add a new item</div>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr><th>Item</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($items as $item):
                        $emoji = $foodEmojis[($item['category_id']-1) % count($foodEmojis)];
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px">
                                <div style="width:40px;height:40px;background:var(--surface);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0"><?= $emoji ?></div>
                                <div>
                                    <div style="font-weight:500;font-size:.875rem"><?= htmlspecialchars($item['item_name']) ?></div>
                                    <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars(substr($item['description'],0,40)) ?>…</div>
                                </div>
                            </div>
                        </td>
                        <td style="color:var(--muted);font-size:.825rem"><?= htmlspecialchars($item['category_name']) ?></td>
                        <td style="color:var(--gold);font-weight:700">$<?= number_format($item['price'],2) ?></td>
                        <td>
                            <span class="badge-status badge-<?= $item['status']==='Available'?'available':'outstock' ?>">
                                <?= $item['status'] ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <a href="menu.php?edit=<?= $item['id'] ?>" class="btn-icon" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="menu.php?delete=<?= $item['id'] ?>" class="btn-icon danger" title="Delete" onclick="return confirm('Delete this item?')"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>