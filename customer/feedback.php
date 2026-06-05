<?php
// ============================================================
// FILE: customer/feedback.php
// FOLDER: foodflow/customer/feedback.php
// PURPOSE: Submit feedback and view own past reviews
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireCustomer();
$page_title = 'Leave Feedback';

$uid = $_SESSION['user_id'];
$msg = ''; $err = '';

// SUBMIT FEEDBACK
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $rating  = (int)($_POST['rating'] ?? 5);
    if (!$message) {
        $err = 'Please write a message.';
    } elseif ($rating < 1 || $rating > 5) {
        $err = 'Rating must be between 1 and 5.';
    } else {
        $pdo->prepare("INSERT INTO feedback (user_id,message,rating) VALUES (?,?,?)")->execute([$uid,$message,$rating]);
        logActivity($pdo,$uid,"Left feedback with $rating stars");
        $msg = 'Thank you! Your feedback has been submitted.';
    }
}

// My feedback history
$my_feedback = $pdo->prepare("SELECT * FROM feedback WHERE user_id=? ORDER BY created_at DESC");
$my_feedback->execute([$uid]);
$my_feedback = $my_feedback->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Leave Feedback ⭐</h1>
    <p>Share your experience and help us improve</p>
</div>

<?php if ($msg): ?><div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-ff alert-ff-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="row g-3">
    <!-- FORM -->
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-chat-quote me-2" style="color:var(--accent)"></i>Write a Review</div>
            </div>
            <div style="padding:24px">
                <form method="POST">
                    <!-- Star rating picker -->
                    <div class="mb-4">
                        <label class="form-label">Your Rating</label>
                        <div id="star-picker" style="display:flex;gap:8px;font-size:2rem;cursor:pointer">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <span class="star-btn" data-val="<?= $i ?>" style="color:<?= $i<=5?'var(--gold)':'#2a3040' ?>;transition:.2s" onmouseover="hoverStars(<?= $i ?>)" onmouseout="resetStars()" onclick="selectStar(<?= $i ?>)">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating-input" value="5">
                        <div id="rating-label" style="font-size:.8rem;color:var(--muted);margin-top:6px">Excellent — 5 stars</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your Message <span style="color:var(--danger)">*</span></label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Tell us about your experience…" required></textarea>
                    </div>
                    <button type="submit" class="btn-ff w-100" style="padding:13px">
                        <i class="bi bi-send me-2"></i>Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- MY REVIEWS -->
    <div class="col-lg-7">
        <div class="panel-header" style="background:transparent;border:none;padding:0;margin-bottom:16px">
            <div class="panel-title" style="font-size:1rem"><i class="bi bi-clock-history me-2" style="color:var(--accent)"></i>My Reviews (<?= count($my_feedback) ?>)</div>
        </div>
        <?php if (empty($my_feedback)): ?>
        <div class="panel">
            <div class="empty-state">
                <div class="empty-icon">💬</div>
                <div class="empty-title">No reviews yet</div>
                <div class="empty-sub">Share your first review using the form!</div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach($my_feedback as $fb): ?>
        <div class="panel mb-2">
            <div style="padding:18px 20px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
                    <div style="color:var(--gold);font-size:1.1rem;letter-spacing:2px">
                        <?php for($i=1;$i<=5;$i++) echo $i<=$fb['rating']?'★':'☆'; ?>
                    </div>
                    <div style="font-size:.75rem;color:var(--muted)"><?= date('M j, Y', strtotime($fb['created_at'])) ?></div>
                </div>
                <p style="color:var(--muted);font-size:.875rem;line-height:1.6;margin:0">"<?= htmlspecialchars($fb['message']) ?>"</p>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const labels = ['','Terrible','Poor','Average','Good','Excellent'];
let selected = 5;

function hoverStars(n){
    document.querySelectorAll('.star-btn').forEach((s,i)=>{
        s.style.color = i<n ? 'var(--gold)' : '#2a3040';
        s.style.transform = i<n ? 'scale(1.15)' : 'scale(1)';
    });
}
function resetStars(){
    document.querySelectorAll('.star-btn').forEach((s,i)=>{
        s.style.color = i<selected ? 'var(--gold)' : '#2a3040';
        s.style.transform = 'scale(1)';
    });
}
function selectStar(n){
    selected = n;
    document.getElementById('rating-input').value = n;
    document.getElementById('rating-label').textContent = labels[n] + ' — ' + n + ' star' + (n!==1?'s':'');
    resetStars();
}
</script>

<?php include 'includes/footer.php'; ?>