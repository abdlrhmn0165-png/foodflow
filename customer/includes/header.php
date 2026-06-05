<?php
// ============================================================
// FILE: customer/includes/header.php
// FOLDER: foodflow/customer/includes/header.php
// PURPOSE: Customer layout header — topnav + sidebar
// ============================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($page_title ?? 'Portal') ?> – FoodFlow</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root{
            --bg:#07090f;--sidebar:#0d1117;--surface:#111827;--card:#141d2b;
            --border:rgba(255,255,255,0.07);--accent:#f97316;--gold:#fbbf24;
            --success:#22c55e;--danger:#ef4444;--warning:#f59e0b;--info:#38bdf8;
            --text:#f1f5f9;--muted:#64748b;--sidebar-w:240px;
        }
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%;background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;overflow-x:hidden}
        h1,h2,h3,h4,h5,h6,.font-syne{font-family:'Syne',sans-serif}

        .sidebar{position:fixed;top:0;left:0;height:100vh;width:var(--sidebar-w);background:var(--sidebar);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:900;transition:transform .3s}
        .sidebar-brand{padding:22px 18px;border-bottom:1px solid var(--border);font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;color:var(--accent)}
        .sidebar-brand span{color:var(--text)}
        .sidebar-nav{flex:1;overflow-y:auto;padding:14px 10px}
        .nav-section-label{font-size:.62rem;text-transform:uppercase;letter-spacing:.15em;color:var(--muted);padding:12px 10px 4px;font-weight:600}
        .nav-item{margin-bottom:2px}
        .nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none;font-size:.875rem;font-weight:500;transition:.2s}
        .nav-link:hover{background:rgba(249,115,22,0.08);color:var(--text)}
        .nav-link.active{background:linear-gradient(135deg,rgba(249,115,22,0.15),rgba(249,115,22,0.05));color:var(--accent);border:1px solid rgba(249,115,22,0.2)}
        .nav-link .nav-icon{font-size:1rem;width:18px;text-align:center}
        .sidebar-footer{padding:14px 10px;border-top:1px solid var(--border)}
        .user-chip{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--card);border-radius:10px;border:1px solid var(--border)}
        .avatar-sm{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--accent),#ea580c);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem}
        .user-info .name{font-size:.82rem;font-weight:600}
        .user-info .role{font-size:.68rem;color:var(--muted);text-transform:uppercase;letter-spacing:.08em}

        .topbar{position:fixed;top:0;left:var(--sidebar-w);right:0;height:60px;background:rgba(7,9,15,0.92);backdrop-filter:blur(16px);border-bottom:1px solid var(--border);z-index:800;display:flex;align-items:center;padding:0 24px;gap:12px}
        .topbar-title{font-family:'Syne',sans-serif;font-size:1.05rem;font-weight:700;flex:1}
        .topbar-action{width:36px;height:36px;background:var(--card);border:1px solid var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--muted);cursor:pointer;transition:.2s;text-decoration:none}
        .topbar-action:hover{border-color:rgba(249,115,22,0.3);color:var(--accent)}

        .main-content{margin-left:var(--sidebar-w);margin-top:60px;padding:24px;min-height:calc(100vh - 60px)}

        /* shared components */
        .panel{background:var(--card);border:1px solid var(--border);border-radius:18px;overflow:hidden}
        .panel-header{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
        .panel-title{font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem}
        .stat-card{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:22px;transition:.3s;position:relative;overflow:hidden}
        .stat-card:hover{transform:translateY(-3px)}
        .stat-label{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px}
        .stat-value{font-size:1.8rem;font-weight:800;font-family:'Syne',sans-serif}
        .stat-icon-wrap{position:absolute;top:18px;right:18px;width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
        .badge-status{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:50px;font-size:.7rem;font-weight:600}
        .badge-status::before{content:'';width:5px;height:5px;border-radius:50%;background:currentColor}
        .badge-available,.badge-delivered,.badge-paid{background:rgba(34,197,94,0.12);color:var(--success)}
        .badge-outstock,.badge-cancelled,.badge-failed{background:rgba(239,68,68,0.12);color:var(--danger)}
        .badge-pending{background:rgba(245,158,11,0.12);color:var(--warning)}
        .badge-preparing,.badge-confirmed{background:rgba(56,189,248,0.12);color:var(--info)}
        .badge-ready{background:rgba(168,85,247,0.12);color:#a855f7}
        .data-table{width:100%;border-collapse:separate;border-spacing:0}
        .data-table th{background:rgba(255,255,255,0.025);padding:11px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border)}
        .data-table td{padding:13px 16px;border-bottom:1px solid var(--border);font-size:.85rem;vertical-align:middle}
        .data-table tbody tr:hover{background:rgba(255,255,255,0.02)}
        .data-table tbody tr:last-child td{border-bottom:none}
        .form-control,.form-select{background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;color:var(--text);padding:10px 14px;font-family:'DM Sans',sans-serif;font-size:.875rem;transition:.2s}
        .form-control:focus,.form-select:focus{background:rgba(249,115,22,0.04);border-color:rgba(249,115,22,0.4);box-shadow:0 0 0 3px rgba(249,115,22,0.1);color:var(--text)}
        .form-label{font-size:.8rem;color:var(--muted);font-weight:500;margin-bottom:5px}
        .form-select option{background:#1a2030}
        .btn-ff{background:linear-gradient(135deg,var(--accent),#ea580c);color:#fff;border:none;border-radius:10px;padding:10px 20px;font-weight:600;font-family:'Syne',sans-serif;cursor:pointer;transition:.3s;box-shadow:0 0 14px rgba(249,115,22,0.2)}
        .btn-ff:hover{transform:translateY(-2px);box-shadow:0 0 24px rgba(249,115,22,0.35);color:#fff;text-decoration:none}
        .btn-ghost-ff{background:rgba(255,255,255,0.04);border:1px solid var(--border);color:var(--text);border-radius:10px;padding:9px 18px;cursor:pointer;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:.875rem}
        .btn-ghost-ff:hover{border-color:rgba(249,115,22,0.3);color:var(--accent)}
        .alert-ff{padding:12px 16px;border-radius:10px;font-size:.85rem;margin-bottom:18px;display:flex;align-items:center;gap:8px}
        .alert-ff-success{background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);color:#86efac}
        .alert-ff-error{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#fca5a5}
        .empty-state{text-align:center;padding:50px 20px}
        .empty-icon{font-size:3.5rem;margin-bottom:12px;opacity:.4}
        .empty-title{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:6px}
        .empty-sub{color:var(--muted);font-size:.85rem}
        .page-header{margin-bottom:24px}
        .page-header h1{font-size:1.7rem;font-weight:800;margin-bottom:3px}
        .page-header p{color:var(--muted);font-size:.875rem}
        .progress-ff{height:5px;background:rgba(255,255,255,0.06);border-radius:50px;overflow:hidden}
        .progress-fill{height:100%;border-radius:50px;background:linear-gradient(90deg,var(--accent),var(--gold))}

        @media(max-width:992px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:none}
            .topbar{left:0}
            .main-content{margin-left:0}
            .hamburger{display:flex!important}
            .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:850}
            .overlay.show{display:block}
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">🍽️ Food<span>Flow</span></div>
    <nav class="sidebar-nav">
        <?php $cp = basename($_SERVER['PHP_SELF'],'.php'); ?>
        <div class="nav-section-label">Menu</div>
        <div class="nav-item"><a href="<?= SITE_URL ?>/customer/dashboard.php" class="nav-link <?= $cp==='dashboard'?'active':'' ?>"><i class="bi bi-house nav-icon"></i> Home</a></div>
        <div class="nav-item"><a href="<?= SITE_URL ?>/customer/menu.php" class="nav-link <?= $cp==='menu'?'active':'' ?>"><i class="bi bi-grid nav-icon"></i> Browse Menu</a></div>
        <div class="nav-section-label">Orders</div>
        <div class="nav-item"><a href="<?= SITE_URL ?>/customer/cart.php" class="nav-link <?= $cp==='cart'?'active':'' ?>"><i class="bi bi-cart3 nav-icon"></i> My Cart</a></div>
        <div class="nav-item"><a href="<?= SITE_URL ?>/customer/orders.php" class="nav-link <?= $cp==='orders'?'active':'' ?>"><i class="bi bi-bag-check nav-icon"></i> Order History</a></div>
        <div class="nav-section-label">Account</div>
        <div class="nav-item"><a href="<?= SITE_URL ?>/customer/feedback.php" class="nav-link <?= $cp==='feedback'?'active':'' ?>"><i class="bi bi-star nav-icon"></i> Leave Feedback</a></div>
        <div class="nav-item"><a href="<?= SITE_URL ?>/customer/profile.php" class="nav-link <?= $cp==='profile'?'active':'' ?>"><i class="bi bi-person-circle nav-icon"></i> My Profile</a></div>
    </nav>
    <div class="sidebar-footer">
        <div class="user-chip">
            <div class="avatar-sm"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars(explode(' ',$_SESSION['full_name'])[0]) ?></div>
                <div class="role">Customer</div>
            </div>
        </div>
        <a href="<?= SITE_URL ?>/logout.php" style="display:flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;font-size:.8rem;padding:8px 12px;margin-top:4px;border-radius:8px;transition:.2s" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='var(--muted)'">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</aside>

<header class="topbar">
    <button class="hamburger topbar-action border-0 d-none" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <div class="topbar-title"><?= $page_title ?? 'Customer Portal' ?></div>
    <a href="<?= SITE_URL ?>/customer/cart.php" class="topbar-action" title="Cart"><i class="bi bi-cart3"></i></a>
    <a href="<?= SITE_URL ?>/customer/profile.php" class="topbar-action" title="Profile"><i class="bi bi-person-circle"></i></a>
    <a href="<?= SITE_URL ?>/logout.php" class="topbar-action" title="Logout"><i class="bi bi-power"></i></a>
</header>

<main class="main-content">
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('overlay').classList.toggle('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show')}
</script>