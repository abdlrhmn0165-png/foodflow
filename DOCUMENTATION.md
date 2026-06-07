📖 FoodFlow – Technical Documentation
Version: 1.0.0 | Stack: PHP 8 · MySQL 8 · Bootstrap 5 · Chart.js 4

Table of Contents
Architecture Overview
Database Design
Authentication System
Admin Module
Customer Module
Cart & Checkout Flow
Analytics & Charts
File-by-File Reference
Database Queries Reference
Session & Security Model
UI Component Library
Extending FoodFlow
1. Architecture Overview
FoodFlow follows a procedural PHP + PDO pattern with include-based templating. There is no framework — every page is a standalone PHP file that:

Requires shared includes (db.php, auth.php)
Performs its own database queries
Renders its own HTML using the layout header/footer includes
Request → PHP Page → includes/db.php (PDO connection)
                   → includes/auth.php (session + guards)
                   → Page Logic (queries, form handling)
                   → admin/includes/header.php (sidebar + topbar)
                   → Page HTML output
                   → admin/includes/footer.php (JS, close tags)
Two Protected Zones
┌─────────────────────────────────────────────────────────┐
│                   PUBLIC ZONE                           │
│  index.php  |  login.php  |  register.php               │
└──────────────────────┬──────────────────────────────────┘
                       │ login POST
          ┌────────────┴────────────┐
          │                         │
          ▼                         ▼
┌─────────────────┐       ┌──────────────────────┐
│  ADMIN ZONE     │       │  CUSTOMER ZONE        │
│  role = admin   │       │  role = customer      │
│  requireAdmin() │       │  requireCustomer()    │
└─────────────────┘       └──────────────────────┘
2. Database Design
Entity Relationship Overview
users ──────────────────────────── orders ─────────── order_items
  │                                   │                    │
  │ (one user : many orders)          │ (one order :       │
  │                                   │  many items)       │
  ├── feedback                        │                    │
  │    (user_id FK)                   ├── payments         │
  │                                   │    (order_id FK)   │
  └── activity_logs                   │                    │
       (user_id FK)                   └────────────────    │
                                                           │
categories ──── menu_items ────────────────────────────────┘
                 (category_id FK)        (menu_item_id FK)

notifications (standalone — no FK, admin-managed)
Table: users
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
full_name	VARCHAR(150)	Display name
email	VARCHAR(150) UNIQUE	Login identifier
password	VARCHAR(255)	bcrypt hash via password_hash()
role	ENUM('admin','customer')	Controls access zone
created_at	TIMESTAMP	Auto-set on insert
Key behaviour: Role is set to customer by default on registration. Admin accounts must be created manually in the database or by another admin.

Table: categories
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
category_name	VARCHAR(100)	e.g. "Burgers"
description	TEXT	Optional
created_at	TIMESTAMP	Auto-set
Constraint: A category cannot be deleted if it has menu items (ON DELETE CASCADE is NOT set here intentionally — categories.php checks for items first and shows an error).

Table: menu_items
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
item_name	VARCHAR(150)	Dish name
category_id	INT	FK → categories.id (CASCADE DELETE)
description	TEXT	Dish description
price	DECIMAL(10,2)	Always positive
status	ENUM('Available','Out of Stock')	Controls visibility on customer menu
image	VARCHAR(255)	Filename only — stored in assets/images/food/
created_at	TIMESTAMP	Auto-set
Table: orders
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
user_id	INT	FK → users.id (CASCADE DELETE)
total_amount	DECIMAL(10,2)	Sum of all order_items at time of order
order_status	ENUM	See status flow below
created_at	TIMESTAMP	Auto-set
Order Status Flow:

Pending → Confirmed → Preparing → Ready → Delivered
                                        ↘
                    (any stage)     → Cancelled
Table: order_items
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
order_id	INT	FK → orders.id (CASCADE DELETE)
menu_item_id	INT	FK → menu_items.id (CASCADE DELETE)
quantity	INT	Units ordered
price	DECIMAL(10,2)	Snapshot price at time of order — does not change if menu price changes later
⚠️ price is intentionally stored per order_item (not joined from menu_items) to preserve historical accuracy.

Table: payments
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
order_id	INT	FK → orders.id (CASCADE DELETE)
amount	DECIMAL(10,2)	Should equal orders.total_amount
payment_method	ENUM('Cash','Card','Online','Wallet')	Chosen at checkout
payment_status	ENUM('Paid','Pending','Failed','Refunded')	Starts as Pending
payment_date	TIMESTAMP	Auto-set
Table: feedback
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
user_id	INT	FK → users.id (CASCADE DELETE)
message	TEXT	Review text
rating	TINYINT(1)	1–5 stars
created_at	TIMESTAMP	Auto-set
Table: activity_logs
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
user_id	INT	FK → users.id (CASCADE DELETE)
activity	VARCHAR(255)	Human-readable description
created_at	TIMESTAMP	Auto-set
Auto-logged events: login, logout, registration, order placed, order cancelled, menu item added/edited/deleted, profile updated, password changed, feedback submitted, category added/deleted.

Table: notifications
Column	Type	Notes
id	INT AUTO_INCREMENT	Primary key
title	VARCHAR(150)	Short heading
message	TEXT	Full notification body
status	ENUM('unread','read')	Default: unread
created_at	TIMESTAMP	Auto-set
Note: Notifications are not user-specific — they are system-wide admin alerts. Auto-created when a new order is placed.

3. Authentication System
Files Involved
includes/db.php — defines SITE_URL constant used in redirects
includes/auth.php — all session logic
login.php — login form + POST handler
register.php — registration form + POST handler
logout.php — session destroy
Login Flow
1. User submits email + password via POST
2. login.php queries: SELECT * FROM users WHERE email = ?
3. password_verify($input, $stored_hash) is called
4. If match:
      session_regenerate_id(true)          ← prevents session fixation
      $_SESSION['user_id']   = $user['id']
      $_SESSION['full_name'] = $user['full_name']
      $_SESSION['email']     = $user['email']
      $_SESSION['role']      = $user['role']
      logActivity(...)                     ← writes to activity_logs
5. Redirect:
      role === 'admin'    → admin/dashboard.php
      role === 'customer' → customer/dashboard.php
Registration Flow
1. Validate: all fields filled, valid email, password ≥ 6 chars, passwords match
2. Check: SELECT id FROM users WHERE email = ? → reject if exists
3. Hash: password_hash($password, PASSWORD_DEFAULT)
4. Insert: INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'customer')
5. logActivity(...)
6. Redirect to login.php after 2 seconds (header Refresh)
Guard Functions
// Call at top of every admin page:
requireAdmin();
// → checks $_SESSION['user_id'] AND $_SESSION['role'] === 'admin'
// → redirects to login.php?error=unauthorized if not met

// Call at top of every customer page:
requireCustomer();
// → checks $_SESSION['user_id'] AND $_SESSION['role'] === 'customer'

// Check without redirecting:
isLoggedIn()   → bool
isAdmin()      → bool
isCustomer()   → bool
Demo Login Shortcut
login.php accepts a ?demo=admin or ?demo=customer GET parameter that pre-fills credentials and auto-submits the form logic. This is purely for convenience and uses real database records.

4. Admin Module
4.1 Dashboard (admin/dashboard.php)
Queries performed on load:

-- Stat cards
SELECT COUNT(*) FROM menu_items                            -- total items
SELECT COUNT(*) FROM menu_items WHERE status='Available'   -- available
SELECT COUNT(*) FROM orders                                -- total orders
SELECT COUNT(*) FROM users WHERE role='customer'           -- customers
SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='Paid'
SELECT COUNT(*) FROM orders WHERE order_status='Pending'
SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()

-- Chart data (last 6 months)
SELECT DATE_FORMAT(payment_date,'%b'), SUM(amount) FROM payments
  WHERE payment_status='Paid' AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY YEAR(payment_date), MONTH(payment_date)

-- Category breakdown
SELECT c.category_name, COUNT(m.id) FROM categories c
  LEFT JOIN menu_items m ON m.category_id=c.id GROUP BY c.id

-- Top 5 items by quantity sold
SELECT m.item_name, SUM(oi.quantity), SUM(oi.quantity*oi.price)
  FROM order_items oi JOIN menu_items m ON oi.menu_item_id=m.id
  GROUP BY m.id ORDER BY total_sold DESC LIMIT 5
4.2 Menu Management (admin/menu.php)
Add item: POST with item_name, category_id, description, price, status, image → INSERT INTO menu_items

Edit item: GET ?edit=ID → pre-fills form → POST with id → UPDATE menu_items SET ... WHERE id=?

Delete item: GET ?delete=ID → DELETE FROM menu_items WHERE id=?

Search + Filter: Dynamic SQL built with conditional WHERE clauses:

if ($search)    $sql .= " AND m.item_name LIKE ?";
if ($cat_f)     $sql .= " AND m.category_id=?";
if ($status_f)  $sql .= " AND m.status=?";
4.3 Orders (admin/orders.php)
Status update flow:

Admin clicks pencil icon
→ JavaScript opens modal, sets hidden input order_id + select value
→ Admin chooses new status, submits
→ PHP: UPDATE orders SET order_status=? WHERE id=?
→ logActivity(...)
→ Page reloads with success message
Allowed statuses: Pending, Confirmed, Preparing, Ready, Delivered, Cancelled
(validated server-side via in_array($status, $allowed))

4.4 Reports (admin/reports.php)
Six Chart.js charts, all using live database data:

Chart ID	Type	Data Source
revenueChart	Line (dual axis)	Monthly revenue + transaction count, 12 months
paymentChart	Doughnut	Payment method distribution
statusChart	Doughnut	Order status breakdown
catChart	Horizontal Bar	Revenue by category
dailyChart	Bar	Orders per day, last 14 days
Top Items	HTML Table	Top 5 items by quantity sold
4.5 Notifications (admin/notifications.php)
Action	Method
View all	SELECT * FROM notifications ORDER BY created_at DESC
Mark single read	GET ?read=ID → UPDATE ... SET status='read' WHERE id=?
Mark all read	GET ?mark_all=1 → UPDATE notifications SET status='read'
Delete	GET ?delete=ID → DELETE FROM notifications WHERE id=?
Create new	POST with title + message → INSERT INTO notifications
Unread badge in the sidebar is driven by getUnreadNotifications($pdo) in auth.php:

function getUnreadNotifications($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE status='unread'");
    return $stmt->fetchColumn();
}
5. Customer Module
5.1 Dashboard (customer/dashboard.php)
Loads 4 personal stats using parameterised queries with $uid = $_SESSION['user_id']:

SELECT COUNT(*) FROM orders WHERE user_id=?
SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=? AND order_status='Delivered'
SELECT COUNT(*) FROM orders WHERE user_id=? AND order_status NOT IN ('Delivered','Cancelled')
SELECT COUNT(*) FROM menu_items WHERE status='Available'
Also loads last 5 orders and 6 random featured menu items.

5.2 Menu Browsing (customer/menu.php)
Only shows items with status = 'Available'
Category pills generated from DB (only categories that have available items)
Search matches item_name and description
In-cart indicator: checks isset($_SESSION['cart'][$item['id']]) — shows green "In Cart" badge
Add to cart: GET ?add=ID → validates item exists + is available → adds to $_SESSION['cart']
Sticky cart bar: appears at bottom of screen when cart has ≥1 item
5.3 Profile (customer/profile.php)
Update profile:

-- Check email not taken by another user:
SELECT id FROM users WHERE email=? AND id != $uid
-- If clear:
UPDATE users SET full_name=?, email=? WHERE id=?
-- Update session vars too:
$_SESSION['full_name'] = $full_name;
$_SESSION['email'] = $email;
Change password:

password_verify($current, $user['password'])  // verify old password
password_hash($new_pw, PASSWORD_DEFAULT)       // hash new
UPDATE users SET password=? WHERE id=?
6. Cart & Checkout Flow
Cart Data Structure
The cart lives in $_SESSION['cart'] — a PHP associative array:

$_SESSION['cart'] = [
    7  => ['id' => 7,  'name' => 'Spaghetti Carbonara', 'price' => 14.49, 'qty' => 2],
    1  => ['id' => 1,  'name' => 'Classic Smash Burger', 'price' => 12.99, 'qty' => 1],
    13 => ['id' => 13, 'name' => 'Mango Smoothie',       'price' => 5.49,  'qty' => 1],
    //  key = menu_item id
];
Add to Cart
customer/menu.php?add=7
  → Fetch from DB: SELECT id, item_name, price FROM menu_items WHERE id=7 AND status='Available'
  → If found:
       If item already in cart → increment qty
       If new → add with qty=1
  → Redirect back (keeps filters)
Update Quantity
POST to cart.php with qty[7]=3, qty[1]=2
  → Loop $_POST['qty'], enforce max(1, intval($q))
  → Update $_SESSION['cart'][$id]['qty']
Remove Item
cart.php?remove=7
  → unset($_SESSION['cart'][7])
  → Redirect to cart.php
Place Order (Checkout)
POST to cart.php with payment_method=Card

1. VALIDATE: cart not empty
2. PRICE VERIFICATION (re-fetch from DB — never trust session prices):
      foreach cart item:
        SELECT id, price FROM menu_items WHERE id=? AND status='Available'
        accumulate $total
3. INSERT INTO orders (user_id, total_amount, order_status='Pending')
4. $order_id = $pdo->lastInsertId()
5. foreach valid_items:
      INSERT INTO order_items (order_id, menu_item_id, quantity, price)
6. INSERT INTO payments (order_id, amount, payment_method, payment_status='Pending')
7. INSERT INTO notifications (title, message)   ← auto-alert for admin
8. logActivity(...)
9. $_SESSION['cart'] = []                        ← empty cart
10. header('Location: orders.php?success=' . $order_id)
🔒 Security note: Prices are always re-fetched from the database during checkout. A customer cannot manipulate the session to pay a lower price.

7. Analytics & Charts
All charts use Chart.js 4.4 loaded from CDN. Data is generated server-side as JSON and injected into the page as JavaScript variables.

PHP → JavaScript Data Bridge
// Server side (PHP):
$monthly = $pdo->query("SELECT DATE_FORMAT(payment_date,'%b') AS month,
                         SUM(amount) AS revenue FROM payments ...
                         GROUP BY MONTH(payment_date)")->fetchAll();

$chart_months  = json_encode(array_column($monthly, 'month'));   // ["Jan","Feb","Mar"]
$chart_revenue = json_encode(array_column($monthly, 'revenue')); // [28.98,45.47,19.98]
// Client side (JS):
new Chart(document.getElementById('revenueChart'), {
    data: {
        labels:   <?= $chart_months ?>,   // PHP echoes pre-encoded JSON
        datasets: [{ data: <?= $chart_revenue ?> }]
    }
});
Global Chart Defaults
Chart.defaults.color = '#64748b';                        // muted axis labels
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';  // subtle grid lines
Chart Colour Palette
Orange  #f97316  → Revenue line, primary data
Blue    #38bdf8  → Orders bar, info data
Green   #22c55e  → Success metrics
Gold    #fbbf24  → Revenue totals
Purple  #a855f7  → Order status: Ready
Red     #ef4444  → Cancelled / Failed
8. File-by-File Reference
includes/db.php
Defines 5 constants and creates the global $pdo PDO instance. Throws a styled error page (not a PHP fatal) if connection fails. Must be the first include on every page.

includes/auth.php
Calls session_start() only if not already started
Defines: logActivity(), requireAdmin(), requireCustomer(), requireLogin(), isLoggedIn(), isAdmin(), isCustomer(), getUnreadNotifications()
All redirect functions use SITE_URL constant from db.php
admin/includes/header.php
Full HTML document opening through <main class="main-content">. Outputs:

All <head> content (Google Fonts, Bootstrap, Bootstrap Icons, page CSS)
Sidebar with navigation links (active state set by comparing basename($_SERVER['PHP_SELF']))
Topbar with page title, notification bell, logout
JavaScript for sidebar toggle (mobile hamburger)
admin/includes/footer.php
Closes </main>, loads Bootstrap JS bundle, closes </body></html>.

customer/includes/header.php / footer.php
Same pattern as admin, but with customer navigation links (Menu, Cart, Orders, Feedback, Profile).

9. Database Queries Reference
Most Complex Join — Dashboard Top Items
SELECT
    m.item_name,
    SUM(oi.quantity)           AS total_sold,
    SUM(oi.quantity * oi.price) AS revenue
FROM order_items oi
JOIN menu_items m ON oi.menu_item_id = m.id
GROUP BY m.id
ORDER BY total_sold DESC
LIMIT 5;
Revenue by Category
SELECT
    c.category_name,
    COUNT(oi.id)                 AS items_sold,
    SUM(oi.quantity * oi.price)  AS revenue
FROM order_items oi
JOIN menu_items m  ON oi.menu_item_id = m.id
JOIN categories c  ON m.category_id   = c.id
GROUP BY c.id
ORDER BY revenue DESC;
Customer Spend Summary
SELECT
    u.*,
    COUNT(o.id)                        AS order_count,
    COALESCE(SUM(o.total_amount), 0)   AS total_spent
FROM users u
LEFT JOIN orders o ON o.user_id = u.id
WHERE u.role = 'customer'
GROUP BY u.id
ORDER BY u.created_at DESC;
Daily Orders (Last 14 Days)
SELECT
    DATE_FORMAT(created_at, '%a') AS day_label,
    DATE(created_at)              AS day_date,
    COUNT(*)                      AS cnt
FROM orders
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
GROUP BY DATE(created_at)
ORDER BY day_date ASC;
Payment Full Detail
SELECT
    p.*,
    o.order_status,
    u.full_name,
    u.email
FROM payments p
JOIN orders o ON p.order_id = o.id
JOIN users  u ON o.user_id  = u.id
ORDER BY p.payment_date DESC;
10. Session & Security Model
Session Variables Set on Login
$_SESSION['user_id']   // INT   — user's primary key
$_SESSION['full_name'] // STRING — for display in nav/sidebar
$_SESSION['email']     // STRING — for display in profile
$_SESSION['role']      // 'admin' or 'customer'
Cart Session (Customer Only)
$_SESSION['cart']  // ARRAY — keyed by menu_item.id, see Section 6
SQL Injection Prevention
Every query with user input uses PDO prepared statements:

// ✅ CORRECT — parameterised
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// ❌ NEVER done — string concatenation
$pdo->query("SELECT * FROM users WHERE email = '$email'");
XSS Prevention
All user-controlled data echoed to HTML is wrapped in htmlspecialchars():

<?= htmlspecialchars($user['full_name']) ?>
<?= htmlspecialchars($item['description']) ?>
Password Security
// Registration — never store plain text:
$hash = password_hash($password, PASSWORD_DEFAULT);  // bcrypt, cost 10

// Login — timing-safe comparison:
password_verify($input_password, $stored_hash);      // returns bool
Session Fixation Prevention
// Called immediately after successful login:
session_regenerate_id(true);
// Generates new session ID, invalidates old one
11. UI Component Library
All UI components are built with inline CSS variables. Here are the key reusable patterns:

CSS Variables (Shared)
:root {
    --bg:       #07090f;          /* page background */
    --sidebar:  #0d1117;          /* sidebar background */
    --surface:  #111827;          /* slightly lighter surface */
    --card:     #141d2b;          /* card background */
    --border:   rgba(255,255,255,0.07);
    --accent:   #f97316;          /* primary orange */
    --gold:     #fbbf24;          /* secondary gold */
    --success:  #22c55e;
    --danger:   #ef4444;
    --warning:  #f59e0b;
    --info:     #38bdf8;
    --text:     #f1f5f9;
    --muted:    #64748b;
}
Status Badge System
<!-- Applies to: order_status, payment_status, menu_item status -->
<span class="badge-status badge-available">Available</span>
<span class="badge-status badge-pending">Pending</span>
<span class="badge-status badge-preparing">Preparing</span>
<span class="badge-status badge-delivered">Delivered</span>
<span class="badge-status badge-cancelled">Cancelled</span>
<span class="badge-status badge-outstock">Out of Stock</span>
<span class="badge-status badge-paid">Paid</span>
<span class="badge-status badge-failed">Failed</span>
CSS rule adds a coloured dot before the text via ::before { content: ''; }.

Panel Component
<div class="panel">
    <div class="panel-header">
        <div class="panel-title">
            <i class="bi bi-grid me-2" style="color:var(--accent)"></i>
            Panel Title
        </div>
        <a href="#">Action Link</a>
    </div>
    <!-- Content: table, or padded div -->
    <div style="padding:24px">
        ...
    </div>
</div>
Stat Card Component
<div class="stat-card" style="--glow-color:rgba(249,115,22,0.12)">
    <div class="stat-icon-wrap"
         style="background:rgba(249,115,22,0.12);color:var(--accent)">
        <i class="bi bi-bag-fill"></i>
    </div>
    <div class="stat-label">Total Orders</div>
    <div class="stat-value" style="color:var(--accent)">42</div>
    <div class="stat-trend trend-up">
        <i class="bi bi-arrow-up-short"></i> 3 pending
    </div>
</div>
Data Table
<table class="data-table">
    <thead>
        <tr><th>Name</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>Item Name</td>
            <td><span class="badge-status badge-available">Active</span></td>
            <td>
                <div style="display:flex;gap:6px">
                    <a href="?edit=1" class="btn-icon"><i class="bi bi-pencil"></i></a>
                    <a href="?delete=1" class="btn-icon danger"><i class="bi bi-trash"></i></a>
                </div>
            </td>
        </tr>
    </tbody>
</table>
Alert Messages
<!-- Success -->
<div class="alert-ff alert-ff-success">
    <i class="bi bi-check-circle"></i> Operation completed successfully.
</div>

<!-- Error -->
<div class="alert-ff alert-ff-error">
    <i class="bi bi-exclamation-circle"></i> Something went wrong.
</div>
Empty State
<div class="empty-state">
    <div class="empty-icon">🍽️</div>
    <div class="empty-title">No items found</div>
    <div class="empty-sub">Try adjusting your filters or add a new item</div>
</div>
Progress Bar
<div class="progress-ff">
    <div class="progress-fill" style="width:75%"></div>
</div>
12. Extending FoodFlow
Adding a New Admin Page
Create admin/yourpage.php
Start with this boilerplate:
<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$page_title = 'Your Page Title';

// Your queries here...

include 'includes/header.php';
?>

<div class="page-header">
    <h1>Your Page</h1>
    <p>Page description</p>
</div>

<!-- Your content here using panel, data-table, stat-card components -->

<?php include 'includes/footer.php'; ?>
Add a link in admin/includes/header.php inside <nav class="sidebar-nav">:
<div class="nav-item">
    <a href="<?= SITE_URL ?>/admin/yourpage.php"
       class="nav-link <?= $current_page==='yourpage'?'active':'' ?>">
        <i class="bi bi-your-icon nav-icon"></i> Your Page
    </a>
</div>
Adding a New Database Table
Add CREATE TABLE to database/foodflow.sql
Insert sample data with INSERT INTO
Add FK constraint if linking to existing tables
Update db.php if a new constant is needed
Adding a New Menu Item Field
ALTER TABLE menu_items ADD COLUMN new_field VARCHAR(100);
Update admin/menu.php form to include the new input
Update both the INSERT and UPDATE prepared statements
Display in customer/menu.php as needed
Adding Image Upload
Replace the image filename input in admin/menu.php with:

// In form: <input type="file" name="item_image" accept="image/*">
// Also add enctype="multipart/form-data" to the form tag

if ($_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $ext      = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
    $filename = 'item_' . time() . '.' . $ext;
    $dest     = '../assets/images/food/' . $filename;
    move_uploaded_file($_FILES['item_image']['tmp_name'], $dest);
    $image = $filename;
}
Adding Email Notifications
Install PHPMailer via Composer or include manually, then call in cart.php after order creation:

// After INSERT INTO orders...
sendOrderConfirmation($user_email, $order_id, $total);
Making Payments Actually Process
Replace the payment method select with a real payment gateway (Stripe, PayPal):

Use Stripe PHP SDK: composer require stripe/stripe-php
Create a payment intent on checkout
Update payments.payment_status to 'Paid' after successful charge webhook
Appendix A — Default URL Map
http://localhost/foodflow/                          Landing page
http://localhost/foodflow/login.php                 Login
http://localhost/foodflow/register.php              Register
http://localhost/foodflow/logout.php                Logout
http://localhost/foodflow/admin/dashboard.php       Admin home
http://localhost/foodflow/admin/menu.php            Menu management
http://localhost/foodflow/admin/categories.php      Categories
http://localhost/foodflow/admin/orders.php          Orders list
http://localhost/foodflow/admin/order_detail.php?id=1   Order detail
http://localhost/foodflow/admin/payments.php        Payments
http://localhost/foodflow/admin/customers.php       Customers
http://localhost/foodflow/admin/feedback.php        Feedback
http://localhost/foodflow/admin/notifications.php   Notifications
http://localhost/foodflow/admin/reports.php         Analytics
http://localhost/foodflow/customer/dashboard.php    Customer home
http://localhost/foodflow/customer/menu.php         Browse menu
http://localhost/foodflow/customer/cart.php         Shopping cart
http://localhost/foodflow/customer/orders.php       Order history
http://localhost/foodflow/customer/feedback.php     Submit review
http://localhost/foodflow/customer/profile.php      My profile
Appendix B — GET Parameter Reference
Page	Parameter	Effect
login.php	?demo=admin	Auto-login as admin
login.php	?demo=customer	Auto-login as customer
admin/menu.php	?edit=ID	Pre-fill edit form
admin/menu.php	?delete=ID	Delete item (no confirmation)
admin/menu.php	?search=X&cat=N&status=S	Filter results
admin/orders.php	?status=Pending	Filter by status
admin/orders.php	?search=name	Search by customer
admin/notifications.php	?mark_all=1	Mark all notifications read
admin/notifications.php	?read=ID	Mark one read
admin/notifications.php	?delete=ID	Delete notification
customer/menu.php	?add=ID	Add item to cart
customer/menu.php	?cat=N&search=X	Filter menu
customer/cart.php	?remove=ID	Remove item from cart
customer/orders.php	?view=ID	Show order detail inline
customer/orders.php	?cancel=ID	Cancel pending order
customer/orders.php	?success=ID	Show success message
FoodFlow Technical Documentation — v1.0.0
