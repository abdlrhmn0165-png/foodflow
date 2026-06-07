# 🍽️ FoodFlow – Restaurant Management System

<div align="center">

![FoodFlow Banner](https://img.shields.io/badge/FoodFlow-Restaurant%20Management-f97316?style=for-the-badge&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHBhdGggZmlsbD0id2hpdGUiIGQ9Ik0xMSAydi4xN0M2LjkyIDIuNiA0IDYuMjYgNCA5Ljc1IDQgMTMuMjUgNy4xIDE2IDExIDE2di0yYy0yLjc2IDAtNS0yLjM4LTUtNS4yNSAwLTIuNjQgMi02Ljc1IDYtNnY4aDJ2LThjNCAuMjUgNiAzLjM2IDYgNiAwIDIuODctMi4yNCA1LjI1LTUgNS4yNXYyYzMuOSAwIDctMi43NSA3LTYuMjUgMC0zLjQ5LTIuOTItNy4xNS03LTcuNThWMmgtMnptLTEgMThIMXYyaDIydi0ySDE3di00aC0ydjRoLTR6Ii8+PC9zdmc+)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**A full-stack, modern restaurant management system built with PHP, MySQL, and Bootstrap 5.**  
Features a dark glassmorphism admin dashboard, a customer ordering portal, real-time analytics, and complete order lifecycle management.

[Live Demo](#demo-credentials) · [Documentation](DOCUMENTATION.md) · [Report Bug](#) · [Request Feature](#)

</div>

---

## 📸 Screenshots

| Landing Page | Admin Dashboard | Customer Menu |
|---|---|---|
| Modern hero with live stats | Analytics with Chart.js | Browse & order food |

| Orders Management | Reports & Analytics | Customer Profile |
|---|---|---|
| Status update modal | Revenue charts | Edit account details |

---

## ✨ Features at a Glance

### 🛡️ Admin Side
- **Modern Dashboard** — live stat cards, revenue graphs, order timelines
- **Menu Management** — full CRUD with category filter, search, status toggle
- **Order Management** — view all orders, update status via modal
- **Payment Tracking** — filter by method/status, totals breakdown
- **Customer Management** — view registered users, order counts, spend
- **Feedback Moderation** — star ratings, delete reviews, rating distribution
- **Notifications** — create/read/mark-all, unread badge in sidebar
- **Reports & Analytics** — 6 Chart.js charts: revenue, orders, categories, payment methods, daily orders, order status
- **Category Management** — add/edit/delete categories with item counts
- **Activity Logs** — auto-logged for every significant action

### 🛍️ Customer Side
- **Beautiful Landing Page** — animated hero, live stats, featured menu, reviews
- **Browse Menu** — filter by category, search by name, sticky cart bar
- **Cart System** — session-based cart, quantity update, live total
- **Checkout** — choose payment method, order placed in one click
- **Order History** — view all orders, cancel pending, detailed receipt
- **Feedback System** — interactive star picker, submit reviews, view own history
- **Profile Management** — update name/email, change password
- **Dashboard** — personal stats, recent orders, quick actions, featured food

---

## 🗂️ Project Structure

```
foodflow/
│
├── 📄 index.php                   ← Public landing page
├── 📄 login.php                   ← Unified login (admin + customer)
├── 📄 register.php                ← Customer registration
├── 📄 logout.php                  ← Session destroy & redirect
│
├── 📁 admin/                      ← Admin-only pages
│   ├── 📄 dashboard.php           ← Main analytics dashboard
│   ├── 📄 menu.php                ← Menu CRUD + search/filter
│   ├── 📄 categories.php          ← Category management
│   ├── 📄 orders.php              ← Order list + status update
│   ├── 📄 order_detail.php        ← Single order with items
│   ├── 📄 payments.php            ← Payment records + filters
│   ├── 📄 customers.php           ← Registered customer list
│   ├── 📄 feedback.php            ← Reviews + rating charts
│   ├── 📄 notifications.php       ← Notification centre
│   ├── 📄 reports.php             ← Advanced analytics (6 charts)
│   └── 📁 includes/
│       ├── 📄 header.php          ← Sidebar + topbar layout
│       └── 📄 footer.php          ← Closing tags + Bootstrap JS
│
├── 📁 customer/                   ← Customer-only pages
│   ├── 📄 dashboard.php           ← Personal stats + quick actions
│   ├── 📄 menu.php                ← Browse menu + add to cart
│   ├── 📄 cart.php                ← Cart view + checkout
│   ├── 📄 orders.php              ← Order history + cancel + detail
│   ├── 📄 feedback.php            ← Submit + view own reviews
│   ├── 📄 profile.php             ← Edit profile + change password
│   └── 📁 includes/
│       ├── 📄 header.php          ← Sidebar + topbar layout
│       └── 📄 footer.php          ← Closing tags
│
├── 📁 includes/                   ← Shared PHP includes
│   ├── 📄 db.php                  ← PDO database connection + constants
│   └── 📄 auth.php                ← Session helpers + role guards
│
├── 📁 database/
│   └── 📄 foodflow.sql            ← Complete DB schema + sample data
│
└── 📁 assets/                     ← (optional) static assets
    ├── 📁 css/
    ├── 📁 js/
    └── 📁 images/
        └── 📁 food/               ← Food item images (optional)
```

---

## 🗄️ Database Schema

```
9 Tables with full foreign-key relationships:

users            → stores admin + customer accounts
categories       → food categories (Burgers, Pizza, etc.)
menu_items       → food items linked to categories
orders           → customer order records
order_items      → line items per order (links orders ↔ menu_items)
payments         → payment record per order
feedback         → customer star reviews
activity_logs    → audit trail of all user actions
notifications    → admin notification centre
```

---

## ⚙️ Requirements

| Requirement | Version |
|---|---|
| PHP | 8.0 or higher |
| MySQL | 5.7+ / 8.0+ |
| XAMPP / WAMP / LAMP | Any recent version |
| Web Browser | Chrome, Firefox, Edge, Safari |
| PDO Extension | Enabled (default in XAMPP) |
| mbstring Extension | Enabled (default in XAMPP) |

---

## 🚀 Installation — Step by Step

### Step 1 — Download & Place Files

```
Place the entire foodflow/ folder inside your XAMPP htdocs:

Windows:   C:\xampp\htdocs\foodflow\
macOS:     /Applications/XAMPP/htdocs/foodflow/
Linux:     /var/www/html/foodflow/
```

### Step 2 — Start XAMPP Services

Open **XAMPP Control Panel** and start:
- ✅ **Apache**
- ✅ **MySQL**

### Step 3 — Import the Database

1. Open your browser → go to `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Database name: `foodflow` → Encoding: `utf8mb4_unicode_ci` → click **Create**
4. Select the `foodflow` database from the left sidebar
5. Click the **Import** tab at the top
6. Click **Choose File** → select `foodflow/database/foodflow.sql`
7. Click **Go** at the bottom

> ✅ You should see "Import has been successfully finished."

### Step 4 — Configure Database Connection

Open `foodflow/includes/db.php` and update if needed:

```php
define('DB_HOST', 'localhost');   // usually 'localhost'
define('DB_USER', 'root');        // your MySQL username (default: root)
define('DB_PASS', '');            // your MySQL password (default: empty)
define('DB_NAME', 'foodflow');    // must match the DB you created
define('SITE_URL', 'http://localhost/foodflow');  // your base URL
```

### Step 5 — Open in Browser

```
http://localhost/foodflow
```

That's it. You're live. 🎉

---

## 🔑 Demo Credentials

| Role | Email | Password | Dashboard |
|---|---|---|---|
| **Admin** | admin@foodflow.com | password | `/admin/dashboard.php` |
| **Customer** | sarah@example.com | password | `/customer/dashboard.php` |
| **Customer** | mike@example.com | password | `/customer/dashboard.php` |

> 💡 Quick access: Click **Admin Demo** or **Customer Demo** buttons on the login page — no typing needed.

---

## 🔐 Security Features

- ✅ **Password Hashing** — `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- ✅ **Prepared Statements** — all SQL uses PDO prepared statements (prevents SQL injection)
- ✅ **Session Regeneration** — `session_regenerate_id(true)` on login
- ✅ **Role Guards** — `requireAdmin()` / `requireCustomer()` on every protected page
- ✅ **XSS Prevention** — all output uses `htmlspecialchars()`
- ✅ **Input Validation** — server-side validation on all forms
- ✅ **CSRF Protection** — form submissions validated server-side

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.0+ (procedural + PDO) |
| **Database** | MySQL 8.0 with InnoDB + foreign keys |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) |
| **CSS Framework** | Bootstrap 5.3 |
| **Icons** | Bootstrap Icons 1.11 |
| **Charts** | Chart.js 4.4 |
| **Fonts** | Google Fonts (Syne + DM Sans) |
| **Design** | Glassmorphism + dark theme + gradient UI |
| **Sessions** | PHP native sessions |
| **Architecture** | MVC-inspired with includes |

---

## 📦 Sample Data Included

The SQL file ships with ready-to-use dummy data:

- 🧑‍🤝‍🧑 **6 users** (1 admin + 5 customers)
- 🏷️ **8 categories** (Burgers, Pizza, Pasta, Salads, Desserts, Beverages, Starters, Grills)
- 🍔 **18 menu items** with descriptions and prices
- 📦 **12 orders** spanning Jan–Apr 2025
- 🧾 **23 order items** across all orders
- 💳 **12 payment records** (mixed methods and statuses)
- ⭐ **5 feedback entries** with star ratings
- 📋 **8 activity log entries**
- 🔔 **4 notifications** (2 unread, 2 read)

---

## 🎨 UI Design Highlights

- 🌑 **Pure dark theme** — `#060810` background, no white glare
- 🟠 **Orange accent** — `#f97316` primary, `#fbbf24` gold secondary
- ✨ **Glassmorphism cards** — `backdrop-filter: blur()` panels
- 📐 **Syne font** — bold headings for premium feel
- 📱 **Fully responsive** — mobile sidebar collapses to hamburger menu
- 💫 **Scroll animations** — IntersectionObserver reveal effects
- 📊 **Live counters** — animated number count-up on landing page
- 🎭 **Hover micro-interactions** — card lifts, glow shadows, border transitions

---

## 🗺️ Page Map

```
/ (index.php)
    ├── /login.php
    │       └── POST → admin/dashboard.php  OR  customer/dashboard.php
    ├── /register.php
    │       └── POST → /login.php
    ├── /logout.php → /login.php
    │
    ├── /admin/
    │   ├── dashboard.php      (stats, charts, recent orders, activity)
    │   ├── menu.php           (list, add, edit, delete, search, filter)
    │   ├── categories.php     (list, add, edit, delete)
    │   ├── orders.php         (list, filter, update status)
    │   ├── order_detail.php   (single order with items + payment)
    │   ├── payments.php       (all payments, filter, totals)
    │   ├── customers.php      (all users, order count, spend)
    │   ├── feedback.php       (reviews, ratings, distribution)
    │   ├── notifications.php  (list, mark read, create, delete)
    │   └── reports.php        (6 advanced charts + KPIs)
    │
    └── /customer/
        ├── dashboard.php      (stats, recent orders, quick actions, featured)
        ├── menu.php           (browse, search, filter, add to cart)
        ├── cart.php           (view cart, update qty, checkout)
        ├── orders.php         (history, cancel, view detail)
        ├── feedback.php       (submit review, view own reviews)
        └── profile.php        (edit name/email, change password)
```

---

## ❓ Troubleshooting

### "Database connection failed"
- Make sure MySQL is running in XAMPP Control Panel
- Check credentials in `includes/db.php`
- Confirm the database name is `foodflow` (case sensitive on Linux)

### "Page not found / 404"
- Confirm the folder is named exactly `foodflow` inside `htdocs`
- Check `SITE_URL` in `includes/db.php` matches your actual URL

### "Access denied" when visiting admin pages
- You must be logged in as an admin role
- Use the demo credentials above or create an admin via phpMyAdmin

### Blank white page
- Enable PHP error display: add `ini_set('display_errors', 1);` at the top of `includes/db.php`
- Check the Apache error log in XAMPP

### Session issues / redirect loop
- Make sure `session_start()` is not being called twice
- All session management is handled in `includes/auth.php`

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/AmazingFeature`
3. Commit your changes: `git commit -m 'Add some AmazingFeature'`
4. Push to the branch: `git push origin feature/AmazingFeature`
5. Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

## 👨‍💻 Author

**FoodFlow** — Built as a complete full-stack PHP project demonstrating:
- Role-based authentication
- Relational database design
- Modern dark UI/UX
- Real-time analytics
- Full CRUD operations

---

<div align="center">
Made with ❤️ and a lot of ☕ | <strong>FoodFlow © 2025</strong>
</div>
