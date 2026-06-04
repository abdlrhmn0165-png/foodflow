<?php
// ============================================================
// FILE: admin/categories.php
// PURPOSE: Manage food categories (add/edit/delete)
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Categories';
$msg = ''; $err = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id=?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $err = 'Cannot delete: category has menu items. Remove items first.';
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        $msg = 'Category deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if (!$name) { $err = 'Category name is required.'; }
    else {
        if ($id > 0) {
            $pdo->prepare("UPDATE categories SET category_name=?,description=? WHERE id=?")->execute([$name,$desc,$id]);
            $msg = 'Category updated.';
        } else {
            $pdo->prepare("INSERT INTO categories (category_name,description) VALUES (?,?)")->execute([$name,$desc]);
            $msg = 'Category added.';
        }
    }
}

$edit_cat = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $edit_cat = $s->fetch();
}

$cats = $pdo->query("SELECT c.*,COUNT(m.id) AS item_count FROM categories c LEFT JOIN menu_items m ON m.category_id=c.id GROUP BY c.id ORDER BY c.category_name")->fetchAll();
include 'includes/header.php';
?>
<div class="page-header">
    <div class="breadcrumb-ff"><a href="dashboard.php">Dashboard</a> <i class="bi bi-chevron-right" style="font-size:.6rem"></i> <span>Categories</span></div>
    <h1>Categories</h1>
</div>
<?php if ($msg): ?><div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-ff alert-ff-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-<?= $edit_cat?'pencil':'plus-circle' ?> me-2" style="color:var(--accent)"></i><?= $edit_cat?'Edit':'Add' ?> Category</div>
                <?php if ($edit_cat): ?><a href="categories.php" style="font-size:.8rem;color:var(--muted);text-decoration:none">Cancel</a><?php endif; ?>
            </div>
            <div style="padding:24px">
                <form method="POST">
                    <?php if ($edit_cat): ?><input type="hidden" name="id" value="<?= $edit_cat['id'] ?>"><?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Category Name <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="category_name" class="form-control" placeholder="e.g. Burgers" value="<?= htmlspecialchars($edit_cat['category_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional description…"><?= htmlspecialchars($edit_cat['description'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary-ff w-100"><?= $edit_cat?'Update':'Add' ?> Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-header"><div class="panel-title">All Categories</div><span style="font-size:.8rem;color:var(--muted)"><?= count($cats) ?> total</span></div>
            <table class="data-table">
                <thead><tr><th>Category</th><th>Description</th><th>Items</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($cats as $cat): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($cat['category_name']) ?></td>
                    <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars(substr($cat['description'],0,50)) ?: '—' ?></td>
                    <td><span style="color:var(--info)"><?= $cat['item_count'] ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn-icon"><i class="bi bi-pencil"></i></a>
                            <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn-icon danger" onclick="return confirm('Delete this category?')"><i class="bi bi-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>