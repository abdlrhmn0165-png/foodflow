<?php
// ============================================================
// FILE: admin/feedback.php
// FOLDER: foodflow/admin/feedback.php
// PURPOSE: View all customer feedback and ratings
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Customer Feedback';

// DELETE feedback
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM feedback WHERE id=?")->execute([(int)$_GET['delete']]);
    $msg = 'Feedback deleted.';
}

// STATS
$avg_rating   = $pdo->query("SELECT ROUND(AVG(rating),1) FROM feedback")->fetchColumn() ?: 0;
$total_reviews= $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$five_star    = $pdo->query("SELECT COUNT(*) FROM feedback WHERE rating=5")->fetchColumn();
$one_to_three = $pdo->query("SELECT COUNT(*) FROM feedback WHERE rating<=3")->fetchColumn();

// FILTERS
$rating_f = (int)($_GET['rating'] ?? 0);
$sql = "SELECT f.*, u.full_name, u.email FROM feedback f JOIN users u ON f.user_id=u.id WHERE 1=1";
$params = [];
if ($rating_f) { $sql .= " AND f.rating=?"; $params[] = $rating_f; }
$sql .= " ORDER BY f.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedbacks = $stmt->fetchAll();

// Rating distribution
$dist = $pdo->query("SELECT rating, COUNT(*) as cnt FROM feedback GROUP BY rating ORDER BY rating DESC")->fetchAll();
$dist_map = [];
foreach($dist as $d) $dist_map[$d['rating']] = $d['cnt'];

include 'includes/header.php';
?>

<div class="page-header">
    <div class="breadcrumb-ff">
        <a href="dashboard.php">Dashboard</a>
        <i class="bi bi-chevron-right" style="font-size:.6rem"></i>
        <span>Feedback</span>
    </div>
    <h1>Customer Feedback</h1>
    <p>Monitor reviews and customer satisfaction</p>
</div>

<?php if (!empty($msg)): ?>
<div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- STATS ROW -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="--glow-color:rgba(251,191,36,0.1)">
            <div class="stat-icon-wrap" style="background:rgba(251,191,36,0.12);color:var(--gold)"><i class="bi bi-star-fill"></i></div>
            <div class="stat-label">Average Rating</div>
            <div class="stat-value" style="color:var(--gold)"><?= $avg_rating ?><span style="font-size:1rem">★</span></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon-wrap" style="background:rgba(249,115,22,0.12);color:var(--accent)"><i class="bi bi-chat-quote-fill"></i></div>
            <div class="stat-label">Total Reviews</div>
            <div class="stat-value" style="color:var(--accent)"><?= $total_reviews ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="--glow-color:rgba(34,197,94,0.1)">
            <div class="stat-icon-wrap" style="background:rgba(34,197,94,0.12);color:var(--success)"><i class="bi bi-emoji-smile-fill"></i></div>
            <div class="stat-label">5-Star Reviews</div>
            <div class="stat-value" style="color:var(--success)"><?= $five_star ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="--glow-color:rgba(239,68,68,0.1)">
            <div class="stat-icon-wrap" style="background:rgba(239,68,68,0.12);color:var(--danger)"><i class="bi bi-emoji-frown-fill"></i></div>
            <div class="stat-label">Low Ratings (≤3)</div>
            <div class="stat-value" style="color:var(--danger)"><?= $one_to_three ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Rating Distribution -->
    <div class="col-lg-4">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-bar-chart me-2" style="color:var(--gold)"></i>Rating Breakdown</div>
            </div>
            <div style="padding:24px">
                <!-- Big average display -->
                <div style="text-align:center;margin-bottom:24px">
                    <div style="font-size:3.5rem;font-weight:800;font-family:'Syne',sans-serif;color:var(--gold)"><?= $avg_rating ?></div>
                    <div style="font-size:1.3rem;color:var(--gold);letter-spacing:3px;">
                        <?php for($i=1;$i<=5;$i++) echo $i <= round($avg_rating) ? '★' : '☆'; ?>
                    </div>
                    <div style="color:var(--muted);font-size:.8rem;margin-top:4px">Based on <?= $total_reviews ?> reviews</div>
                </div>
                <?php for($r=5;$r>=1;$r--):
                    $cnt = $dist_map[$r] ?? 0;
                    $pct = $total_reviews > 0 ? round($cnt/$total_reviews*100) : 0;
                ?>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <span style="font-size:.8rem;min-width:14px;color:var(--muted)"><?= $r ?></span>
                    <i class="bi bi-star-fill" style="color:var(--gold);font-size:.7rem"></i>
                    <div class="progress-ff" style="flex:1">
                        <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span style="font-size:.75rem;color:var(--muted);min-width:28px"><?= $cnt ?></span>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Filter by rating -->
        <div class="panel mt-3">
            <div class="panel-header"><div class="panel-title">Filter by Rating</div></div>
            <div style="padding:16px 20px;display:flex;flex-wrap:wrap;gap:8px">
                <a href="feedback.php" class="badge-status <?= !$rating_f?'badge-available':'' ?>" style="text-decoration:none;padding:6px 14px">All</a>
                <?php for($r=5;$r>=1;$r--): ?>
                <a href="feedback.php?rating=<?= $r ?>" class="badge-status <?= $rating_f==$r?'badge-available':'' ?>" style="text-decoration:none;padding:6px 14px;<?= $rating_f!=$r?'background:rgba(255,255,255,0.04);color:var(--muted);border:1px solid var(--border)':'' ?>">
                    <?= $r ?>★
                </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Feedback Cards -->
    <div class="col-lg-8">
        <?php if (empty($feedbacks)): ?>
        <div class="panel">
            <div class="empty-state">
                <div class="empty-icon">⭐</div>
                <div class="empty-title">No feedback yet</div>
                <div class="empty-sub">Customer reviews will appear here</div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach($feedbacks as $fb): ?>
        <div class="panel mb-3" style="transition:.3s" onmouseover="this.style.borderColor='rgba(249,115,22,0.2)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--accent),#ea580c);display:flex;align-items:center;justify-content:center;font-weight:700">
                            <?= strtoupper(substr($fb['full_name'],0,1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($fb['full_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars($fb['email']) ?> &bull; <?= date('M j, Y', strtotime($fb['created_at'])) ?></div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="color:var(--gold)">
                            <?php for($i=1;$i<=5;$i++) echo $i<=$fb['rating']?'★':'☆'; ?>
                        </div>
                        <a href="feedback.php?delete=<?= $fb['id'] ?>" class="btn-icon danger" title="Delete" onclick="return confirm('Delete this review?')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
                <p style="color:var(--muted);font-size:.875rem;line-height:1.65;margin:0;padding:14px;background:rgba(255,255,255,0.03);border-radius:10px;border:1px solid var(--border)">
                    "<?= htmlspecialchars($fb['message']) ?>"
                </p>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>