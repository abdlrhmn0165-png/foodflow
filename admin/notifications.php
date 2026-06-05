<?php
// ============================================================
// FILE: admin/notifications.php
// FOLDER: foodflow/admin/notifications.php
// PURPOSE: View and manage system notifications
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Notifications';

// Mark all read
if (isset($_GET['mark_all'])) {
    $pdo->query("UPDATE notifications SET status='read'");
    header('Location: notifications.php');
    exit;
}

// Mark single read
if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE notifications SET status='read' WHERE id=?")->execute([(int)$_GET['read']]);
}

// Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM notifications WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: notifications.php');
    exit;
}

// Add new notification (form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($title && $message) {
        $pdo->prepare("INSERT INTO notifications (title,message,status) VALUES (?,?,'unread')")->execute([$title,$message]);
        $msg_success = 'Notification created.';
    }
}

$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll();
$unread_cnt    = $pdo->query("SELECT COUNT(*) FROM notifications WHERE status='unread'")->fetchColumn();

include 'includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb-ff">
        <a href="dashboard.php">Dashboard</a>
        <i class="bi bi-chevron-right" style="font-size:.6rem"></i>
        <span>Notifications</span>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:10px">
        <div>
            <h1>Notifications
                <?php if ($unread_cnt > 0): ?>
                <span style="font-size:1rem;background:var(--accent);color:#fff;border-radius:50px;padding:2px 12px;vertical-align:middle"><?= $unread_cnt ?></span>
                <?php endif; ?>
            </h1>
            <p>System alerts and activity notifications</p>
        </div>
        <?php if ($unread_cnt > 0): ?>
        <a href="notifications.php?mark_all=1" class="btn-ghost" style="font-size:.85rem">
            <i class="bi bi-check-all me-1"></i>Mark All Read
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($msg_success)): ?>
<div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg_success) ?></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Notifications List -->
    <div class="col-lg-8">
        <?php if (empty($notifications)): ?>
        <div class="panel">
            <div class="empty-state">
                <div class="empty-icon">🔔</div>
                <div class="empty-title">All caught up!</div>
                <div class="empty-sub">No notifications at the moment</div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach($notifications as $n):
            $is_unread = $n['status'] === 'unread';
        ?>
        <div class="panel mb-2" style="border-color:<?= $is_unread?'rgba(249,115,22,0.3)':'var(--border)' ?>;transition:.3s"
             onmouseover="this.style.borderColor='rgba(249,115,22,0.25)'" onmouseout="this.style.borderColor='<?= $is_unread?'rgba(249,115,22,0.3)':'var(--border)' ?>'">
            <div style="padding:16px 20px;display:flex;gap:14px;align-items:flex-start">
                <!-- Icon -->
                <div style="width:42px;height:42px;border-radius:12px;flex-shrink:0;
                    background:<?= $is_unread?'rgba(249,115,22,0.12)':'rgba(255,255,255,0.04)' ?>;
                    border:1px solid <?= $is_unread?'rgba(249,115,22,0.2)':'var(--border)' ?>;
                    display:flex;align-items:center;justify-content:center;
                    color:<?= $is_unread?'var(--accent)':'var(--muted)' ?>;font-size:1.1rem">
                    <i class="bi bi-bell<?= $is_unread?'-fill':'' ?>"></i>
                </div>
                <!-- Content -->
                <div style="flex:1;min-width:0">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                        <div style="font-weight:<?= $is_unread?'700':'500' ?>;font-size:.9rem">
                            <?= htmlspecialchars($n['title']) ?>
                            <?php if ($is_unread): ?>
                            <span style="background:var(--accent);color:#fff;border-radius:50px;font-size:.65rem;padding:2px 8px;margin-left:6px;font-weight:700">NEW</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.72rem;color:var(--muted);white-space:nowrap">
                            <?= date('M j, g:i a', strtotime($n['created_at'])) ?>
                        </div>
                    </div>
                    <p style="color:var(--muted);font-size:.85rem;margin:4px 0 10px;line-height:1.5"><?= htmlspecialchars($n['message']) ?></p>
                    <div style="display:flex;gap:8px">
                        <?php if ($is_unread): ?>
                        <a href="notifications.php?read=<?= $n['id'] ?>" style="font-size:.75rem;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            <i class="bi bi-check2"></i> Mark read
                        </a>
                        <?php endif; ?>
                        <a href="notifications.php?delete=<?= $n['id'] ?>" onclick="return confirm('Delete this notification?')"
                           style="font-size:.75rem;color:var(--danger);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            <i class="bi bi-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Create Notification -->
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-plus-circle me-2" style="color:var(--accent)"></i>Create Notification</div>
            </div>
            <div style="padding:24px">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Notification title…" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Notification message…" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary-ff w-100">
                        <i class="bi bi-send me-1"></i> Send Notification
                    </button>
                </form>
            </div>
        </div>

        <!-- Summary panel -->
        <div class="panel mt-3">
            <div class="panel-header"><div class="panel-title">Summary</div></div>
            <div style="padding:20px">
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
                    <span style="color:var(--muted);font-size:.875rem">Total</span>
                    <span style="font-weight:600"><?= count($notifications) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
                    <span style="color:var(--muted);font-size:.875rem">Unread</span>
                    <span style="color:var(--accent);font-weight:600"><?= $unread_cnt ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:10px 0">
                    <span style="color:var(--muted);font-size:.875rem">Read</span>
                    <span style="color:var(--success);font-weight:600"><?= count($notifications)-$unread_cnt ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>