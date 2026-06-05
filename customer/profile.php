<?php
// ============================================================
// FILE: customer/profile.php
// FOLDER: foodflow/customer/profile.php
// PURPOSE: View and update customer profile + change password
// ============================================================
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireCustomer();
$page_title = 'My Profile';

$uid = $_SESSION['user_id'];
$msg = ''; $err = '';

// Fetch current user data
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user_stmt->execute([$uid]);
$user = $user_stmt->fetch();

// ── UPDATE PROFILE ────────────────────────────────────────
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    if (!$full_name || !$email) {
        $err = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Please enter a valid email address.';
    } else {
        // Check email not taken by another user
        $check = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $check->execute([$email, $uid]);
        if ($check->fetch()) {
            $err = 'That email is already in use by another account.';
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, email=? WHERE id=?")->execute([$full_name, $email, $uid]);
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email']     = $email;
            logActivity($pdo, $uid, 'Updated profile details');
            $msg = 'Profile updated successfully!';
            // Re-fetch
            $user_stmt->execute([$uid]);
            $user = $user_stmt->fetch();
        }
    }
}

// ── CHANGE PASSWORD ───────────────────────────────────────
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new_pw  = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new_pw || !$confirm) {
        $err = 'Please fill in all password fields.';
    } elseif (!password_verify($current, $user['password'])) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($new_pw) < 6) {
        $err = 'New password must be at least 6 characters.';
    } elseif ($new_pw !== $confirm) {
        $err = 'New passwords do not match.';
    } else {
        $hash = password_hash($new_pw, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
        logActivity($pdo, $uid, 'Changed password');
        $msg = 'Password changed successfully!';
    }
}

// ── STATS ─────────────────────────────────────────────────
$total_orders    = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?"); $total_orders->execute([$uid]); $total_orders = $total_orders->fetchColumn();
$total_spent     = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND order_status='Delivered'"); $total_spent->execute([$uid]); $total_spent = $total_spent->fetchColumn();
$total_feedback  = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE user_id=?"); $total_feedback->execute([$uid]); $total_feedback = $total_feedback->fetchColumn();
$avg_rating      = $pdo->prepare("SELECT ROUND(AVG(rating),1) FROM feedback WHERE user_id=?"); $avg_rating->execute([$uid]); $avg_rating = $avg_rating->fetchColumn() ?: 0;

include 'includes/header.php';
?>

<div class="page-header">
    <h1>My Profile 👤</h1>
    <p>Manage your account information and preferences</p>
</div>

<?php if ($msg): ?><div class="alert-ff alert-ff-success"><i class="bi bi-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert-ff alert-ff-error"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="row g-3">

    <!-- LEFT: Profile card + Stats -->
    <div class="col-lg-4">

        <!-- Avatar Card -->
        <div class="panel mb-3" style="text-align:center">
            <div style="padding:32px 24px">
                <div style="width:80px;height:80px;border-radius:22px;background:linear-gradient(135deg,var(--accent),#ea580c);display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:800;margin:0 auto 16px">
                    <?= strtoupper(substr($user['full_name'],0,1)) ?>
                </div>
                <h4 style="font-family:'Syne',sans-serif;font-weight:800;margin-bottom:4px"><?= htmlspecialchars($user['full_name']) ?></h4>
                <div style="color:var(--muted);font-size:.85rem;margin-bottom:8px"><?= htmlspecialchars($user['email']) ?></div>
                <span style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:var(--success);border-radius:50px;padding:3px 12px;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em">
                    <i class="bi bi-person-check-fill me-1"></i>Verified Customer
                </span>
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);font-size:.78rem;color:var(--muted)">
                    <i class="bi bi-calendar3 me-1"></i>Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                </div>
            </div>
        </div>

        <!-- Stat Pills -->
        <div class="panel">
            <div class="panel-header"><div class="panel-title"><i class="bi bi-bar-chart me-2" style="color:var(--accent)"></i>My Stats</div></div>
            <div style="padding:0">
                <?php
                $profile_stats = [
                    ['Total Orders',   $total_orders,  'bi-bag-check',     'var(--accent)'],
                    ['Total Spent',    '$'.number_format($total_spent,2), 'bi-cash-stack', 'var(--gold)'],
                    ['Reviews Given',  $total_feedback,'bi-star-fill',     'var(--warning)'],
                    ['Avg Rating',     $avg_rating.'★','bi-emoji-smile',   'var(--success)'],
                ];
                foreach($profile_stats as [$label,$val,$icon,$color]):
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--border)">
                    <div style="display:flex;align-items:center;gap:10px">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:1rem;width:18px;text-align:center"></i>
                        <span style="font-size:.875rem;color:var(--muted)"><?= $label ?></span>
                    </div>
                    <span style="font-weight:700;color:<?= $color ?>"><?= $val ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- RIGHT: Forms -->
    <div class="col-lg-8">

        <!-- Update Profile -->
        <div class="panel mb-3">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-pencil-square me-2" style="color:var(--accent)"></i>Edit Profile</div>
            </div>
            <div style="padding:24px">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <div style="position:relative">
                                <i class="bi bi-person" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                                <input type="text" name="full_name" class="form-control" style="padding-left:36px"
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <div style="position:relative">
                                <i class="bi bi-envelope" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                                <input type="email" name="email" class="form-control" style="padding-left:36px"
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="Customer" disabled
                                   style="opacity:.5;cursor:not-allowed">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Created</label>
                            <input type="text" class="form-control" value="<?= date('M j, Y', strtotime($user['created_at'])) ?>" disabled
                                   style="opacity:.5;cursor:not-allowed">
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update_profile" class="btn-ff" style="padding:11px 28px">
                                <i class="bi bi-check-lg me-1"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><i class="bi bi-shield-lock me-2" style="color:var(--info)"></i>Change Password</div>
            </div>
            <div style="padding:24px">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current Password</label>
                            <div style="position:relative">
                                <i class="bi bi-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                                <input type="password" name="current_password" class="form-control" style="padding-left:36px" placeholder="Your current password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <div style="position:relative">
                                <i class="bi bi-lock-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                                <input type="password" name="new_password" class="form-control" style="padding-left:36px" placeholder="Min 6 characters">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <div style="position:relative">
                                <i class="bi bi-lock-fill" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                                <input type="password" name="confirm_password" class="form-control" style="padding-left:36px" placeholder="Repeat new password">
                            </div>
                        </div>
                        <div class="col-12">
                            <div style="background:rgba(56,189,248,0.06);border:1px solid rgba(56,189,248,0.15);border-radius:10px;padding:12px 16px;font-size:.8rem;color:var(--muted);margin-bottom:4px">
                                <i class="bi bi-info-circle me-1" style="color:var(--info)"></i>
                                Passwords must be at least 6 characters. Use a mix of letters, numbers and symbols for security.
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="change_password" class="btn-ghost-ff" style="padding:11px 28px">
                                <i class="bi bi-shield-check me-1"></i>Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>