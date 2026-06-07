<?php
// ============================================================
// FILE: index.php  (ROOT: foodflow/index.php)
// PURPOSE: Public landing page — all buttons fully functional
//          Logged-in users are auto-redirected to their dashboard
// ============================================================
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect already-logged-in users to their dashboard
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'customer/dashboard.php'));
    exit;
}

// Fetch featured menu items for homepage display
$featured = $pdo->query(
    "SELECT m.*, c.category_name
     FROM menu_items m
     JOIN categories c ON m.category_id = c.id
     WHERE m.status = 'Available'
     ORDER BY RAND() LIMIT 6"
)->fetchAll();

// Live stats from DB
$stats_items     = $pdo->query("SELECT COUNT(*) FROM menu_items WHERE status='Available'")->fetchColumn();
$stats_orders    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$stats_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$stats_revenue   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Paid'")->fetchColumn();

// Testimonials
$testimonials = $pdo->query(
    "SELECT f.*, u.full_name
     FROM feedback f JOIN users u ON f.user_id = u.id
     ORDER BY f.created_at DESC LIMIT 3"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFlow – Premium Restaurant Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --bg:#060810; --surface:#0d1117; --card:#111827;
            --border:rgba(255,255,255,0.07); --accent:#f97316;
            --gold:#fbbf24; --text:#f1f5f9; --muted:#94a3b8;
            --glow:rgba(249,115,22,0.35);
        }
        *{margin:0;padding:0;box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{background:var(--bg);color:var(--text);font-family:'DM Sans',sans-serif;overflow-x:hidden}
        h1,h2,h3,.brand{font-family:'Syne',sans-serif}

        /* ── NAV ─────────────────────────────────────────── */
        nav.navbar{background:rgba(6,8,16,0.88);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:14px 0;position:sticky;top:0;z-index:999;transition:.3s}
        .navbar-brand{font-size:1.6rem;font-weight:800;color:var(--accent)!important;text-decoration:none}
        .navbar-brand span{color:var(--text)}
        .nav-link{color:var(--muted)!important;font-weight:500;transition:.2s;padding:6px 14px!important;border-radius:8px}
        .nav-link:hover{color:var(--text)!important;background:rgba(255,255,255,0.05)}

        /* ── BUTTONS ─────────────────────────────────────── */
        .btn-glow{background:linear-gradient(135deg,var(--accent),#ea580c);color:#fff;border:none;border-radius:50px;padding:10px 26px;font-weight:700;font-family:'Syne',sans-serif;box-shadow:0 0 22px var(--glow);transition:all .3s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
        .btn-glow:hover{transform:translateY(-2px);box-shadow:0 0 38px var(--glow);color:#fff}
        .btn-glow.btn-lg{padding:13px 32px;font-size:1rem}
        .btn-outline-glow{background:transparent;border:1px solid rgba(249,115,22,0.4);color:var(--accent);border-radius:50px;padding:10px 26px;font-weight:700;font-family:'Syne',sans-serif;transition:.3s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
        .btn-outline-glow:hover{background:rgba(249,115,22,0.1);border-color:var(--accent);color:var(--accent)}
        .btn-outline-glow.btn-lg{padding:13px 32px;font-size:1rem}

        /* ── HERO ─────────────────────────────────────────── */
        .hero{min-height:100vh;display:flex;align-items:center;position:relative;overflow:hidden;background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(249,115,22,0.15),transparent)}
        .hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23f97316' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
        .hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.25);border-radius:50px;padding:6px 18px;font-size:.82rem;color:var(--accent);margin-bottom:22px;animation:fadeDown .6s ease}
        .hero-title{font-size:clamp(2.8rem,7vw,5.5rem);font-weight:800;line-height:1.05;animation:fadeDown .8s ease}
        .hero-title .hl{background:linear-gradient(135deg,var(--accent),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .hero-sub{font-size:1.1rem;color:var(--muted);max-width:500px;line-height:1.7;margin:18px 0 32px;animation:fadeDown 1s ease}
        .hero-actions{display:flex;gap:14px;flex-wrap:wrap;animation:fadeDown 1.1s ease}

        /* Floating stat cards */
        .float-badge{border-radius:16px;padding:14px 18px;min-width:155px;animation:floatA 3s ease-in-out infinite}
        .float-badge.right{position:absolute;top:-30px;right:-10px;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.2)}
        .float-badge.left{position:absolute;bottom:-30px;left:-10px;background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.15);animation-name:floatB;animation-duration:3.5s}
        @keyframes floatA{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
        @keyframes floatB{0%,100%{transform:translateY(0)}50%{transform:translateY(10px)}}
        @keyframes fadeDown{from{opacity:0;transform:translateY(-16px)}to{opacity:1;transform:none}}

        .food-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;text-align:center}
        .food-tile{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:18px 10px;font-size:1.9rem;transition:.3s;cursor:default}
        .food-tile:hover{transform:scale(1.08);border-color:rgba(249,115,22,0.4);background:rgba(249,115,22,0.05)}
        .food-tile p{font-size:.68rem;color:var(--muted);margin-top:6px}
        .hero-visual-wrap{width:100%;max-width:420px;margin:0 auto;background:radial-gradient(circle at 50% 50%,rgba(249,115,22,0.18),transparent 70%);border-radius:28px;padding:28px;border:1px solid var(--border);position:relative}

        /* ── STATS STRIP ─────────────────────────────────── */
        .stats-strip{background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:40px 0}
        .stat-num{font-size:2.6rem;font-weight:800;font-family:'Syne',sans-serif;background:linear-gradient(135deg,var(--accent),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .stat-lbl{font-size:.8rem;color:var(--muted);text-transform:uppercase;letter-spacing:.1em;margin-top:4px}

        /* ── SECTION HELPERS ─────────────────────────────── */
        .section-tag{display:inline-block;background:rgba(249,115,22,0.1);border:1px solid rgba(249,115,22,0.22);border-radius:50px;padding:4px 16px;font-size:.75rem;color:var(--accent);text-transform:uppercase;letter-spacing:.12em;margin-bottom:10px}
        .section-title{font-size:clamp(1.7rem,4vw,2.6rem);font-weight:800}
        .section-title .hl{background:linear-gradient(135deg,var(--accent),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent}

        /* ── MENU CARDS ──────────────────────────────────── */
        .menu-card{background:var(--card);border:1px solid var(--border);border-radius:20px;overflow:hidden;transition:.35s;height:100%;display:flex;flex-direction:column}
        .menu-card:hover{transform:translateY(-6px);border-color:rgba(249,115,22,0.3);box-shadow:0 20px 50px rgba(249,115,22,0.08)}
        .menu-img{height:170px;background:linear-gradient(135deg,#1a2030,#0f1520);display:flex;align-items:center;justify-content:center;font-size:3.8rem;position:relative}
        .cat-pill{position:absolute;top:10px;right:10px;background:rgba(249,115,22,0.88);color:#fff;border-radius:50px;font-size:.68rem;padding:3px 10px;font-weight:600}
        .menu-body{padding:18px;flex:1;display:flex;flex-direction:column}
        .menu-name{font-weight:700;font-size:.95rem;margin-bottom:5px}
        .menu-desc{font-size:.8rem;color:var(--muted);line-height:1.5;flex:1;margin-bottom:12px}
        .menu-price{font-size:1.15rem;font-weight:800;color:var(--accent);font-family:'Syne',sans-serif}
        .btn-order{background:rgba(249,115,22,0.12);color:var(--accent);border:1px solid rgba(249,115,22,0.28);border-radius:50px;padding:6px 16px;font-size:.78rem;font-weight:600;text-decoration:none;transition:.2s;white-space:nowrap}
        .btn-order:hover{background:rgba(249,115,22,0.22);color:var(--accent)}

        /* ── FEATURE CARDS ───────────────────────────────── */
        .feature-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:30px;height:100%;transition:.3s}
        .feature-card:hover{border-color:rgba(249,115,22,0.3);transform:translateY(-4px)}
        .feature-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:18px;background:linear-gradient(135deg,rgba(249,115,22,0.18),rgba(249,115,22,0.04));border:1px solid rgba(249,115,22,0.18)}

        /* ── TESTIMONIALS ────────────────────────────────── */
        .testi-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:26px;height:100%;transition:.3s}
        .testi-card:hover{border-color:rgba(249,115,22,0.2)}
        .stars{color:var(--gold);font-size:.95rem;margin-bottom:12px;letter-spacing:2px}
        .reviewer{display:flex;align-items:center;gap:12px;margin-top:18px}
        .ava{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#ea580c);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.88rem}

        /* ── CTA ─────────────────────────────────────────── */
        .cta-box{background:linear-gradient(135deg,rgba(249,115,22,0.13),rgba(251,191,36,0.07));border:1px solid rgba(249,115,22,0.18);border-radius:28px;padding:64px 40px;text-align:center;margin:80px 0}

        /* ── FOOTER ──────────────────────────────────────── */
        footer{background:var(--surface);border-top:1px solid var(--border);padding:60px 0 28px}
        .footer-link{color:var(--muted);text-decoration:none;display:block;margin-bottom:8px;font-size:.88rem;transition:.2s}
        .footer-link:hover{color:var(--accent)}

        /* ── SCROLL REVEAL ───────────────────────────────── */
        .reveal{opacity:0;transform:translateY(28px);transition:.7s cubic-bezier(.2,.8,.2,1)}
        .reveal.visible{opacity:1;transform:none}

        /* ── HOW IT WORKS ────────────────────────────────── */
        .step-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:28px 24px;text-align:center;transition:.3s;position:relative}
        .step-card:hover{transform:translateY(-4px);border-color:rgba(249,115,22,0.25)}
        .step-num{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--accent),#ea580c);color:#fff;font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
        .step-connector{position:absolute;top:42px;right:-24px;width:48px;height:2px;background:linear-gradient(90deg,rgba(249,115,22,0.4),transparent);z-index:1}
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════ NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">🍽️ Food<span>Flow</span></a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <i class="bi bi-list text-white fs-4"></i>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="#how">How It Works</a></li>
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#testimonials">Reviews</a></li>
            </ul>
            <div class="d-flex gap-2 mt-2 mt-lg-0 align-items-center">
                <a href="login.php" class="btn-outline-glow" style="padding:8px 20px;font-size:.88rem">Login</a>
                <a href="register.php" class="btn-glow" style="padding:8px 20px;font-size:.88rem">Get Started <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════════════ HERO -->
<section class="hero py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left -->
            <div class="col-lg-6">
                <div class="hero-badge"><i class="bi bi-stars"></i> Restaurant Management Reimagined</div>
                <h1 class="hero-title">
                    The Smarter Way to<br>
                    <span class="hl">Run Your Restaurant</span>
                </h1>
                <p class="hero-sub">
                    FoodFlow gives you a complete command center — from live menu management and
                    order tracking to customer analytics and revenue insights. All in one beautiful dashboard.
                </p>
                <div class="hero-actions">
                    <a href="register.php" class="btn-glow btn-lg">
                        Start Ordering <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="login.php?demo=admin" class="btn-outline-glow btn-lg">
                        <i class="bi bi-shield-fill"></i> Admin Demo
                    </a>
                </div>
                <!-- Trust row -->
                <div style="display:flex;align-items:center;gap:20px;margin-top:36px;flex-wrap:wrap">
                    <div style="display:flex;align-items:center;gap-6px">
                        <?php for($i=0;$i<5;$i++): ?>
                        <div style="width:30px;height:30px;border-radius:50%;border:2px solid var(--bg);background:linear-gradient(135deg,#<?= ['f97316','fb923c','fbbf24','34d399','38bdf8'][$i] ?>,#<?= ['ea580c','f97316','f59e0b','22c55e','0ea5e9'][$i] ?>);margin-left:<?= $i?'-8px':'0' ?>;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:700"><?= ['S','M','E','J','P'][$i] ?></div>
                        <?php endfor; ?>
                    </div>
                    <div style="font-size:.82rem;color:var(--muted)">Trusted by <strong style="color:var(--text)"><?= $stats_customers ?>+</strong> happy customers</div>
                    <div style="font-size:.82rem;color:var(--muted)"><span style="color:var(--gold)">★★★★★</span> 4.8 avg rating</div>
                </div>
            </div>
            <!-- Right visual -->
            <div class="col-lg-6">
                <div style="position:relative;padding:20px">
                    <div class="float-badge right">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:1.4rem">🔔</span>
                            <div>
                                <div style="font-size:.72rem;color:var(--muted)">New Order</div>
                                <div style="font-size:.88rem;font-weight:700;color:var(--accent)">+$41.98</div>
                            </div>
                        </div>
                    </div>
                    <div class="hero-visual-wrap">
                        <div class="food-grid">
                            <?php
                            $tiles=[['🍔','Burgers'],['🍕','Pizza'],['🍝','Pasta'],['🥗','Salads'],['🍰','Desserts'],['🥩','Grills']];
                            foreach($tiles as $t): ?>
                            <div class="food-tile">
                                <?= $t[0] ?><p><?= $t[1] ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="float-badge left">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:1.4rem">⭐</span>
                            <div>
                                <div style="font-size:.72rem;color:var(--muted)">Avg Rating</div>
                                <div style="font-size:.88rem;font-weight:700;color:var(--gold)">4.8 / 5.0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ STATS -->
<div class="stats-strip">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3 reveal">
                <div class="stat-num" data-target="<?= $stats_items ?>">0</div>
                <div class="stat-lbl">Menu Items Available</div>
            </div>
            <div class="col-6 col-md-3 reveal">
                <div class="stat-num" data-target="<?= $stats_orders ?>">0</div>
                <div class="stat-lbl">Orders Fulfilled</div>
            </div>
            <div class="col-6 col-md-3 reveal">
                <div class="stat-num" data-target="<?= $stats_customers ?>">0</div>
                <div class="stat-lbl">Happy Customers</div>
            </div>
            <div class="col-6 col-md-3 reveal">
                <div class="stat-num">$<?= number_format($stats_revenue,0) ?>+</div>
                <div class="stat-lbl">Revenue Generated</div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ FEATURED MENU -->
<section id="menu" class="py-5 mt-3">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-tag">Our Menu</div>
            <h2 class="section-title">Today's <span class="hl">Featured Dishes</span></h2>
            <p style="color:var(--muted);max-width:480px;margin:12px auto;font-size:.95rem">
                Handcrafted with premium ingredients. Every bite tells a story.
            </p>
        </div>
        <div class="row g-4">
            <?php
            $emojiMap = [1=>'🍔',2=>'🍕',3=>'🍝',4=>'🥗',5=>'🍰',6=>'🥤',7=>'🫓',8=>'🥩'];
            foreach($featured as $item):
                $emoji = $emojiMap[$item['category_id']] ?? '🍽️';
            ?>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="menu-card">
                    <div class="menu-img">
                        <?= $emoji ?>
                        <span class="cat-pill"><?= htmlspecialchars($item['category_name']) ?></span>
                    </div>
                    <div class="menu-body">
                        <div class="menu-name"><?= htmlspecialchars($item['item_name']) ?></div>
                        <div class="menu-desc"><?= htmlspecialchars(substr($item['description'],0,80)) ?>...</div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <div class="menu-price">$<?= number_format($item['price'],2) ?></div>
                            <!-- ORDER NOW: register if not logged in -->
                            <a href="register.php?redirect=menu" class="btn-order">
                                <i class="bi bi-cart-plus me-1"></i>Order Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5 reveal">
            <a href="register.php" class="btn-glow btn-lg">
                View Full Menu <i class="bi bi-arrow-right"></i>
            </a>
            <div style="margin-top:12px;font-size:.82rem;color:var(--muted)">
                Already have an account? <a href="login.php" style="color:var(--accent);text-decoration:none">Sign in to order →</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ HOW IT WORKS -->
<section id="how" class="py-5" style="background:var(--surface);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-tag">How It Works</div>
            <h2 class="section-title">Order in <span class="hl">3 Simple Steps</span></h2>
        </div>
        <div class="row g-4">
            <?php
            $steps=[
                ['01','Create Account','Register for free in seconds. No credit card required to sign up.','bi-person-plus-fill','var(--accent)'],
                ['02','Browse & Add','Explore our full menu, read descriptions, and add your favourites to the cart.','bi-grid-3x3-gap-fill','var(--info)'],
                ['03','Place Order','Checkout securely with your preferred payment method and track your order live.','bi-bag-check-fill','var(--success)'],
            ];
            foreach($steps as $i=>$s): ?>
            <div class="col-md-4 reveal">
                <div class="step-card">
                    <?php if($i<2): ?><div class="step-connector d-none d-md-block"></div><?php endif; ?>
                    <div class="step-num"><?= $s[0] ?></div>
                    <i class="bi <?= $s[4] ?>" style="color:<?= $s[5] ?>;font-size:2rem;margin-bottom:14px;display:block"></i>
                    <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:8px"><?= $s[1] ?></h5>
                    <p style="color:var(--muted);font-size:.875rem;line-height:1.6;margin:0"><?= $s[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5 reveal">
            <a href="register.php" class="btn-glow btn-lg">
                Get Started Free <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ FEATURES -->
<section id="features" class="py-5 mt-2">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-tag">Features</div>
            <h2 class="section-title">Everything You <span class="hl">Need</span></h2>
            <p style="color:var(--muted);max-width:460px;margin:12px auto;font-size:.95rem">
                Built for restaurant owners and customers alike — FoodFlow covers every corner.
            </p>
        </div>
        <div class="row g-4">
            <?php
            $features=[
                ['🚀','Real-time Order Tracking','Watch orders move from Pending → Preparing → Ready → Delivered, all live.'],
                ['📊','Analytics Dashboard','Charts, revenue graphs, top-selling items and customer insights in one view.'],
                ['🍽️','Full Menu Management','Add, edit, categorise and price your dishes. Mark items available or sold-out instantly.'],
                ['👥','Customer Self-Portal','Customers browse, order, track history, and leave reviews — entirely self-service.'],
                ['💳','Payment Tracking','Cash, card, online, wallet — every transaction logged and reconciled automatically.'],
                ['⭐','Feedback & Ratings','Collect star ratings and reviews to continuously improve your restaurant quality.'],
            ];
            foreach($features as $f): ?>
            <div class="col-md-6 col-lg-4 reveal">
                <div class="feature-card">
                    <div class="feature-icon"><?= $f[0] ?></div>
                    <h5 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:10px"><?= $f[1] ?></h5>
                    <p style="color:var(--muted);font-size:.875rem;line-height:1.65;margin:0"><?= $f[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ TESTIMONIALS -->
<section id="testimonials" class="py-5" style="background:var(--surface);border-top:1px solid var(--border)">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <div class="section-tag">Reviews</div>
            <h2 class="section-title">What Our Customers <span class="hl">Say</span></h2>
        </div>
        <div class="row g-4">
            <?php if (empty($testimonials)): ?>
            <div class="col-12 text-center" style="color:var(--muted)">No reviews yet.</div>
            <?php else: ?>
            <?php foreach($testimonials as $r): ?>
            <div class="col-md-4 reveal">
                <div class="testi-card">
                    <div class="stars">
                        <?= str_repeat('★',$r['rating']) ?><?= str_repeat('☆',5-$r['rating']) ?>
                    </div>
                    <p style="color:var(--muted);font-size:.875rem;line-height:1.7;margin:0">
                        "<?= htmlspecialchars($r['message']) ?>"
                    </p>
                    <div class="reviewer">
                        <div class="ava"><?= strtoupper(substr($r['full_name'],0,1)) ?></div>
                        <div>
                            <div style="font-weight:600;font-size:.875rem"><?= htmlspecialchars($r['full_name']) ?></div>
                            <div style="font-size:.72rem;color:var(--muted)">Verified Customer</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ CTA -->
<div class="container">
    <div class="cta-box reveal">
        <div class="section-tag" style="margin:0 auto 14px;display:table">Ready to Start?</div>
        <h2 class="section-title mb-3">Transform Your Restaurant <span class="hl">Today</span></h2>
        <p style="color:var(--muted);max-width:480px;margin:0 auto 28px;font-size:.95rem">
            Join FoodFlow and bring your restaurant into the modern era. Free to get started.
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
            <a href="register.php" class="btn-glow btn-lg">
                <i class="bi bi-person-plus-fill"></i> Create Free Account
            </a>
            <a href="login.php?demo=admin" class="btn-outline-glow btn-lg">
                <i class="bi bi-shield-fill"></i> View Admin Demo
            </a>
        </div>
        <div style="margin-top:16px;font-size:.8rem;color:var(--muted)">
            Or log in as a customer: <a href="login.php?demo=customer" style="color:var(--accent);text-decoration:none">Customer Demo →</a>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ FOOTER -->
<footer>
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-lg-4">
                <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;color:var(--accent);margin-bottom:10px">🍽️ FoodFlow</div>
                <p style="color:var(--muted);font-size:.875rem;line-height:1.7;max-width:270px">
                    The complete restaurant management platform for modern food businesses.
                </p>
                <div style="display:flex;gap:10px;margin-top:16px">
                    <?php foreach(['twitter','instagram','facebook','linkedin'] as $s): ?>
                    <a href="#" style="width:36px;height:36px;border-radius:9px;background:var(--card);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--muted);text-decoration:none;transition:.2s;font-size:.9rem"
                       onmouseover="this.style.color='var(--accent)';this.style.borderColor='rgba(249,115,22,0.3)'"
                       onmouseout="this.style.color='var(--muted)';this.style.borderColor='var(--border)'">
                        <i class="bi bi-<?= $s ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h6 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:16px">Navigation</h6>
                <a href="#menu" class="footer-link">Menu</a>
                <a href="#how" class="footer-link">How It Works</a>
                <a href="#features" class="footer-link">Features</a>
                <a href="#testimonials" class="footer-link">Reviews</a>
            </div>
            <div class="col-6 col-lg-2">
                <h6 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:16px">Account</h6>
                <a href="register.php" class="footer-link">Register</a>
                <a href="login.php" class="footer-link">Login</a>
                <a href="login.php?demo=admin" class="footer-link">Admin Demo</a>
                <a href="login.php?demo=customer" class="footer-link">Customer Demo</a>
            </div>
            <div class="col-lg-4">
                <h6 style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:16px">Contact</h6>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <a href="mailto:hello@foodflow.com" style="color:var(--muted);text-decoration:none;font-size:.875rem;display:flex;align-items:center;gap:8px;transition:.2s" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--muted)'">
                        <i class="bi bi-envelope" style="color:var(--accent)"></i> hello@foodflow.com
                    </a>
                    <a href="tel:+15550000000" style="color:var(--muted);text-decoration:none;font-size:.875rem;display:flex;align-items:center;gap:8px;transition:.2s" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--muted)'">
                        <i class="bi bi-telephone" style="color:var(--accent)"></i> +1 (555) 000-0000
                    </a>
                    <div style="color:var(--muted);font-size:.875rem;display:flex;align-items:center;gap:8px">
                        <i class="bi bi-geo-alt" style="color:var(--accent)"></i> 123 Food Street, Kitchen City
                    </div>
                </div>
            </div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <div style="color:var(--muted);font-size:.82rem">
                &copy; <?= date('Y') ?> FoodFlow. Built with ❤️ for modern restaurants.
            </div>
            <div style="display:flex;gap:16px">
                <a href="#" style="color:var(--muted);font-size:.8rem;text-decoration:none">Privacy Policy</a>
                <a href="#" style="color:var(--muted);font-size:.8rem;text-decoration:none">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Scroll reveal ──────────────────────────────────────────
const revealObs = new IntersectionObserver(entries => {
    entries.forEach((e, i) => {
        if (e.isIntersecting) {
            setTimeout(() => e.target.classList.add('visible'), i * 70);
        }
    });
}, { threshold: 0.08 });
document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

// ── Animated number counters ───────────────────────────────
const counterObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (!e.isIntersecting) return;
        const el = e.target;
        const target = parseInt(el.getAttribute('data-target')) || 0;
        if (!target) return;
        let start = 0;
        const duration = 1600;
        const step = Math.ceil(target / (duration / 16));
        const timer = setInterval(() => {
            start = Math.min(start + step, target);
            el.textContent = start + '+';
            if (start >= target) clearInterval(timer);
        }, 16);
        counterObs.unobserve(el);
    });
}, { threshold: 0.5 });
document.querySelectorAll('[data-target]').forEach(el => counterObs.observe(el));

// ── Sticky navbar shadow on scroll ────────────────────────
window.addEventListener('scroll', () => {
    const nav = document.querySelector('nav.navbar');
    nav.style.boxShadow = window.scrollY > 40
        ? '0 4px 40px rgba(0,0,0,0.4)'
        : 'none';
});
</script>
</body>
</html>
