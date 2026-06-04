<?php
// ============================================================
// FILE: admin/includes/header.php
// PURPOSE: Admin sidebar + topbar layout (included on every admin page)
// ============================================================
$unread_notifs = getUnreadNotifications($pdo);
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($page_title ?? 'Dashboard') ?> – FoodFlow Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --bg:#07090f; --sidebar:#0d1117; --surface:#111827;
            --card:#141d2b; --border:rgba(255,255,255,0.07);
            --accent:#f97316; --accent2:#fb923c; --gold:#fbbf24;
            --success:#22c55e; --danger:#ef4444; --warning:#f59e0b; --info:#38bdf8;
            --text:#f1f5f9; --muted:#64748b; --sidebar-w:260px;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%;background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;overflow-x:hidden}
        h1,h2,h3,h4,h5,h6,.font-syne{font-family:'Syne',sans-serif}

        /* SIDEBAR */
        .sidebar {
            position:fixed; top:0; left:0; height:100vh; width:var(--sidebar-w);
            background:var(--sidebar); border-right:1px solid var(--border);
            display:flex; flex-direction:column; z-index:900;
            transition:transform .3s cubic-bezier(.2,.8,.2,1);
        }
        .sidebar-brand {
            padding:24px 20px; border-bottom:1px solid var(--border);
            font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:800;
            color:var(--accent); display:flex; align-items:center; gap:10px;
        }
        .sidebar-brand .icon { font-size:1.6rem; }
        .sidebar-brand span { color:var(--text); }
        .sidebar-nav { flex:1; overflow-y:auto; padding:16px 12px; }
        .sidebar-nav::-webkit-scrollbar { width:4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }
        .nav-section-label {
            font-size:.65rem; text-transform:uppercase; letter-spacing:.15em;
            color:var(--muted); padding:14px 10px 6px; font-weight:600;
        }
        .nav-item { margin-bottom:2px; }
        .nav-link {
            display:flex; align-items:center; gap:12px; padding:10px 12px;
            border-radius:12px; color:var(--muted); text-decoration:none;
            font-size:.9rem; font-weight:500; transition:all .2s; position:relative;
        }
        .nav-link:hover { background:rgba(249,115,22,0.08); color:var(--text); }
        .nav-link.active {
            background:linear-gradient(135deg,rgba(249,115,22,0.15),rgba(249,115,22,0.05));
            color:var(--accent); border:1px solid rgba(249,115,22,0.2);
        }
        .nav-link .nav-icon { font-size:1.1rem; width:20px; text-align:center; }
        .nav-link .badge-count {
            margin-left:auto; background:var(--accent); color:#fff;
            border-radius:50px; font-size:.65rem; padding:2px 7px; font-weight:700;
        }
        .sidebar-footer {
            padding:16px 12px; border-top:1px solid var(--border);
        }
        .user-chip {
            display:flex; align-items:center; gap:10px; padding:10px 12px;
            background:var(--card); border-radius:12px; border:1px solid var(--border);
        }
        .avatar-sm {
            width:36px; height:36px; border-radius:10px;
            background:linear-gradient(135deg,var(--accent),#ea580c);
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:.85rem;
        }
        .user-info .name { font-size:.85rem; font-weight:600; }
        .user-info .role { font-size:.72rem; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; }

        /* TOPBAR */
        .topbar {
            position:fixed; top:0; left:var(--sidebar-w); right:0; height:64px;
            background:rgba(7,9,15,0.9); backdrop-filter:blur(16px);
            border-bottom:1px solid var(--border); z-index:800;
            display:flex; align-items:center; padding:0 28px; gap:16px;
        }
        .topbar-title { font-family:'Syne',sans-serif; font-size:1.15rem; font-weight:700; flex:1; }
        .topbar-action {
            width:38px; height:38px; background:var(--card); border:1px solid var(--border);
            border-radius:10px; display:flex; align-items:center; justify-content:center;
            color:var(--muted); cursor:pointer; transition:.2s; text-decoration:none; position:relative;
        }
        .topbar-action:hover { border-color:rgba(249,115,22,0.3); color:var(--accent); }
        .notif-dot {
            position:absolute; top:6px; right:6px; width:8px; height:8px;
            background:var(--accent); border-radius:50%; border:2px solid var(--bg);
        }
        .hamburger { display:none; }

        /* MAIN CONTENT */
        .main-content {
            margin-left:var(--sidebar-w); margin-top:64px;
            padding:28px; min-height:calc(100vh - 64px);
        }

        /* CARDS */
        .stat-card {
            background:var(--card); border:1px solid var(--border); border-radius:20px;
            padding:24px; transition:.3s; position:relative; overflow:hidden;
        }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 20px 50px rgba(0,0,0,0.3); }
        .stat-card::before {
            content:''; position:absolute; top:-30%; right:-10%;
            width:160px; height:160px; border-radius:50%;
            background:radial-gradient(circle, var(--glow-color,rgba(249,115,22,0.08)), transparent);
        }
        .stat-label { font-size:.75rem; color:var(--muted); text-transform:uppercase; letter-spacing:.1em; margin-bottom:8px; }
        .stat-value { font-size:2rem; font-weight:800; font-family:'Syne',sans-serif; }
        .stat-icon-wrap {
            position:absolute; top:20px; right:20px; width:44px; height:44px;
            border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;
        }
        .stat-trend { font-size:.8rem; margin-top:8px; }
        .trend-up { color:var(--success); }
        .trend-down { color:var(--danger); }

        /* DATA TABLE */
        .data-table { width:100%; border-collapse:separate; border-spacing:0; }
        .data-table th { background:rgba(255,255,255,0.03); padding:12px 16px; font-size:.75rem; text-transform:uppercase; letter-spacing:.1em; color:var(--muted); font-weight:600; border-bottom:1px solid var(--border); }
        .data-table td { padding:14px 16px; border-bottom:1px solid var(--border); font-size:.875rem; vertical-align:middle; }
        .data-table tbody tr { transition:.2s; }
        .data-table tbody tr:hover { background:rgba(255,255,255,0.02); }
        .data-table tbody tr:last-child td { border-bottom:none; }

        /* BADGES */
        .badge-status {
            display:inline-flex; align-items:center; gap:5px;
            padding:4px 12px; border-radius:50px; font-size:.72rem; font-weight:600;
        }
        .badge-status::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
        .badge-available,.badge-delivered,.badge-paid { background:rgba(34,197,94,0.12); color:var(--success); }
        .badge-outstock,.badge-cancelled,.badge-failed { background:rgba(239,68,68,0.12); color:var(--danger); }
        .badge-pending { background:rgba(245,158,11,0.12); color:var(--warning); }
        .badge-preparing,.badge-confirmed { background:rgba(56,189,248,0.12); color:var(--info); }
        .badge-ready { background:rgba(168,85,247,0.12); color:#a855f7; }

        /* PANEL */
        .panel { background:var(--card); border:1px solid var(--border); border-radius:20px; overflow:hidden; }
        .panel-header { padding:18px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        .panel-title { font-family:'Syne',sans-serif; font-weight:700; font-size:1rem; }
        .panel-body { padding:0; }

        /* FORMS */
        .form-control, .form-select {
            background:rgba(255,255,255,0.04); border:1px solid var(--border); border-radius:10px;
            color:var(--text); padding:10px 14px; font-family:'DM Sans',sans-serif; font-size:.875rem;
            transition:.2s;
        }
        .form-control:focus, .form-select:focus {
            background:rgba(249,115,22,0.04); border-color:rgba(249,115,22,0.4);
            box-shadow:0 0 0 3px rgba(249,115,22,0.1); color:var(--text);
        }
        .form-label { font-size:.825rem; color:var(--muted); font-weight:500; margin-bottom:6px; }
        .form-select option { background:#1a2030; }

        /* BUTTONS */
        .btn-primary-ff {
            background:linear-gradient(135deg,var(--accent),#ea580c); color:#fff;
            border:none; border-radius:10px; padding:10px 20px; font-weight:600;
            font-family:'Syne',sans-serif; cursor:pointer; transition:.3s;
            box-shadow:0 0 16px rgba(249,115,22,0.25);
        }
        .btn-primary-ff:hover { transform:translateY(-2px); box-shadow:0 0 28px rgba(249,115,22,0.4); }
        .btn-ghost { background:rgba(255,255,255,0.04); border:1px solid var(--border); color:var(--text); border-radius:10px; padding:10px 20px; cursor:pointer; transition:.2s; }
        .btn-ghost:hover { border-color:rgba(249,115,22,0.3); color:var(--accent); }
        .btn-danger-ff { background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.2); color:var(--danger); border-radius:10px; padding:8px 16px; cursor:pointer; transition:.2s; font-size:.825rem; }
        .btn-danger-ff:hover { background:rgba(239,68,68,0.2); }
        .btn-icon { width:34px; height:34px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; transition:.2s; font-size:.9rem; border:1px solid var(--border); background:transparent; color:var(--muted); }
        .btn-icon:hover { background:rgba(249,115,22,0.08); border-color:rgba(249,115,22,0.2); color:var(--accent); }
        .btn-icon.danger:hover { background:rgba(239,68,68,0.08); border-color:rgba(239,68,68,0.2); color:var(--danger); }

        /* ALERTS */
        .alert-ff { padding:14px 18px; border-radius:12px; font-size:.875rem; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .alert-ff-success { background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); color:#86efac; }
        .alert-ff-error { background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); color:#fca5a5; }

        /* EMPTY STATE */
        .empty-state { text-align:center; padding:60px 20px; }
        .empty-icon { font-size:4rem; margin-bottom:16px; opacity:.4; }
        .empty-title { font-family:'Syne',sans-serif; font-size:1.1rem; font-weight:700; margin-bottom:8px; }
        .empty-sub { color:var(--muted); font-size:.875rem; }

        /* PROGRESS BAR */
        .progress-ff { height:6px; background:rgba(255,255,255,0.06); border-radius:50px; overflow:hidden; }
        .progress-fill { height:100%; border-radius:50px; background:linear-gradient(90deg,var(--accent),var(--gold)); }

        /* PAGE HEADER */
        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:1.8rem; font-weight:800; margin-bottom:4px; }
        .page-header p { color:var(--muted); font-size:.9rem; }
        .breadcrumb-ff { display:flex; align-items:center; gap:6px; font-size:.8rem; color:var(--muted); margin-bottom:8px; }
        .breadcrumb-ff a { color:var(--muted); text-decoration:none; }
        .breadcrumb-ff a:hover { color:var(--accent); }

        @media(max-width:992px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:none}
            .topbar{left:0}
            .main-content{margin-left:0}
            .hamburger{display:flex}
            .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:850}
            .overlay.show{display:block}
        }
    </style>
</head>
<body>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <span class="icon">🍽️</span>
        Food<span>Flow</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-link <?= $current_page==='dashboard'?'active':'' ?>">
                <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
            </a>
        </div>
        <div class="nav-section-label">Menu</div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/menu.php" class="nav-link <?= $current_page==='menu'?'active':'' ?>">
                <i class="bi bi-grid-3x3-gap nav-icon"></i> Menu Items
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/categories.php" class="nav-link <?= $current_page==='categories'?'active':'' ?>">
                <i class="bi bi-tags nav-icon"></i> Categories
            </a>
        </div>
        <div class="nav-section-label">Operations</div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/orders.php" class="nav-link <?= $current_page==='orders'?'active':'' ?>">
                <i class="bi bi-bag-check nav-icon"></i> Orders
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/payments.php" class="nav-link <?= $current_page==='payments'?'active':'' ?>">
                <i class="bi bi-credit-card nav-icon"></i> Payments
            </a>
        </div>
        <div class="nav-section-label">Customers</div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/customers.php" class="nav-link <?= $current_page==='customers'?'active':'' ?>">
                <i class="bi bi-people nav-icon"></i> Customers
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/feedback.php" class="nav-link <?= $current_page==='feedback'?'active':'' ?>">
                <i class="bi bi-star nav-icon"></i> Feedback
            </a>
        </div>
        <div class="nav-section-label">Analytics</div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/reports.php" class="nav-link <?= $current_page==='reports'?'active':'' ?>">
                <i class="bi bi-bar-chart-line nav-icon"></i> Reports
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= SITE_URL ?>/admin/notifications.php" class="nav-link <?= $current_page==='notifications'?'active':'' ?>">
                <i class="bi bi-bell nav-icon"></i> Notifications
                <?php if ($unread_notifs > 0): ?>
                <span class="badge-count"><?= $unread_notifs ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar-sm"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/logout.php" style="display:flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;font-size:.825rem;padding:10px 12px;margin-top:6px;border-radius:10px;transition:.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--muted)'">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</aside>

<!-- TOPBAR -->
<header class="topbar">
    <button class="hamburger topbar-action border-0" onclick="toggleSidebar()">
        <i class="bi bi-list fs-5"></i>
    </button>
    <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
    <div style="display:flex;align-items:center;gap:8px">
        <a href="<?= SITE_URL ?>/admin/notifications.php" class="topbar-action" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($unread_notifs > 0): ?><span class="notif-dot"></span><?php endif; ?>
        </a>
        <a href="<?= SITE_URL ?>/index.php" class="topbar-action" target="_blank" title="View Site">
            <i class="bi bi-box-arrow-up-right"></i>
        </a>
        <a href="<?= SITE_URL ?>/logout.php" class="topbar-action" title="Logout">
            <i class="bi bi-power"></i>
        </a>
    </div>
</header>

<!-- MAIN -->
<main class="main-content">
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show')}
</script>