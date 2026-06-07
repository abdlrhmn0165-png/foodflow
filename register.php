<?php
// ============================================================
// FILE: register.php  (ROOT: foodflow/register.php)
// PURPOSE: Customer registration page
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'customer/dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,'customer')");
            $stmt->execute([$name, $email, $hash]);
            $new_id = $pdo->lastInsertId();
            logActivity($pdo, $new_id, 'New customer registered');
            $success = 'Account created! Redirecting to login...';
            header('Refresh: 2; URL=login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – FoodFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root{ --bg:#060810;--card:#111827;--border:rgba(255,255,255,0.08);--accent:#f97316;--gold:#fbbf24;--text:#f1f5f9;--muted:#94a3b8; }
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
             background-image:radial-gradient(ellipse 60% 50% at 50% 0%,rgba(249,115,22,0.12),transparent)}
        .auth-wrap{width:100%;max-width:440px}
        .brand{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:var(--accent);text-align:center;margin-bottom:6px}
        .brand span{color:var(--text)}
        .tagline{text-align:center;color:var(--muted);font-size:.9rem;margin-bottom:32px}
        .card{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:36px;box-shadow:0 40px 80px rgba(0,0,0,0.5)}
        .card h2{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:4px}
        .card p.sub{color:var(--muted);font-size:.875rem;margin-bottom:28px}
        .form-group{margin-bottom:16px}
        label{display:block;font-size:.825rem;font-weight:500;color:var(--muted);margin-bottom:6px}
        .input-wrap{position:relative}
        .input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:1rem;pointer-events:none}
        input{width:100%;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:12px;padding:12px 14px 12px 40px;
              color:var(--text);font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:.2s}
        input:focus{border-color:rgba(249,115,22,0.5);background:rgba(249,115,22,0.04)}
        .btn-submit{width:100%;background:linear-gradient(135deg,var(--accent),#ea580c);color:#fff;border:none;border-radius:12px;
                    padding:13px;font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;
                    box-shadow:0 0 20px rgba(249,115,22,0.3);transition:.3s;margin-top:4px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 0 35px rgba(249,115,22,0.45)}
        .alert{padding:12px 16px;border-radius:10px;font-size:.875rem;margin-bottom:20px}
        .alert-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#fca5a5}
        .alert-success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#86efac}
        .login-link{text-align:center;margin-top:20px;font-size:.875rem;color:var(--muted)}
        .login-link a{color:var(--accent);text-decoration:none;font-weight:500}
        .back-link{display:flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none;font-size:.85rem;margin-bottom:20px;transition:.2s}
        .back-link:hover{color:var(--accent)}
        .password-hint{font-size:.75rem;color:var(--muted);margin-top:4px}
    </style>
</head>
<body>
<div class="auth-wrap">
    <a href="index.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Home</a>
    <div class="brand">Food<span>Flow</span></div>
    <div class="tagline">Create your account today</div>
    <div class="card">
        <h2>Create Account ✨</h2>
        <p class="sub">Join FoodFlow and start ordering amazing food</p>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrap">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" name="full_name" placeholder="John Doe" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" placeholder="Min 6 characters" required>
                </div>
                <div class="password-hint">Must be at least 6 characters</div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" name="confirm_password" placeholder="Repeat your password" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Create Account <i class="bi bi-arrow-right ms-1"></i></button>
        </form>
        <div class="login-link">Already have an account? <a href="login.php">Sign in</a></div>
    </div>
</div>
</body>
</html>