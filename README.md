# ☕ Grand Cafe — Full-Stack Web Application

A complete, modern, responsive full-stack web application designed for **"Grand Cafe"**, featuring warm cafe-themed styling (rich espresso brown, velvety latte cream, and golden amber caramel), secure session-based authentication, interactive shopping cart, dynamic order calculations, multi-mode payment options (Cash, UPI QR, Card), customer order history with cancellation, and a comprehensive Administrator CRUD dashboard.

---

## 🌟 Key Features

1. **Warm Cafe-Themed Responsive Design**
   - Built with semantic HTML5, modern CSS3 variables, and vanilla JavaScript.
   - Warm color palette: Cream (`#FAF6F0`), Espresso Brown (`#2C1810`), and Warm Amber/Caramel (`#C68B59`).
   - Fully responsive on mobile, tablet, and desktop screens with mobile drawer navigation.

2. **Interactive Coffee Menu & Dynamic Cart**
   - Live category filtering (*All Brews*, *Hot Coffee*, *Cold Coffee*, *Specialty Coffee*).
   - Real-time search filter by coffee name.
   - Stepper quantity selectors `[- 1 +]` with dynamic subtotal calculations.
   - Slide-in Cart Drawer backed by browser storage with toast notifications.

3. **Secure Authentication & Role-Based Access Control**
   - User registration and login powered by PHP PDO and `password_hash()` / `password_verify()`.
   - Role-based routing: Regular customers are routed to `menu.php`, while Administrators access `admin.php`.
   - One-click demo credentials on the login page for rapid testing.

4. **Checkout, Payments & Digital Invoice**
   - Server-side price re-verification directly from MySQL to prevent client-side cart tampering.
   - Atomic database transactions using PDO (`orders` & `order_items`).
   - Multi-channel payment simulation: **Cash on Counter**, **Instant UPI** with interactive QR code, and **Credit/Debit Card**.
   - Clean Digital Receipt / Invoice view with print styling (`@media print`).

5. **Customer Order Management**
   - View past orders, timestamps, payment modes, and item breakdowns.
   - **Order Cancellation**: Customers can cancel pending orders directly.

6. **Admin Dashboard (Full CRUD)**
   - **Manage Menu**: Add new coffee items, edit names, prices, categories, toggle availability (`available` vs `out_of_stock`), and remove items.
   - **Manage Orders**: View all customer orders, update order status (`Pending`, `Confirmed`, `Cancelled`), view item details, or remove orders.
   - **Live Metrics**: Real-time sales total, order count, pending queue, and menu item count.

---

## 🛠️ Technology Stack

- **Frontend**: HTML5, Modern CSS3 (Flexbox, CSS Grid, Custom Properties, Transitions), Vanilla JavaScript (ES6+).
- **Backend**: PHP 7.4+ / PHP 8.x (Clean procedural architecture using PDO prepared statements).
- **Database**: MySQL 5.7+ / MariaDB.
- **Icons & Fonts**: FontAwesome 6, Google Fonts (*Playfair Display* & *Plus Jakarta Sans*).

---

## 🗄️ Database Architecture (`grand_cafe_db`)

The database schema is defined in [`schema.sql`](file:///c:/Users/samis/OneDrive/Documents/Desktop/Grand%20Cafe/schema.sql) with 4 interconnected tables:

1. **`users`**:
   - `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
   - `username` (VARCHAR(50), UNIQUE)
   - `email` (VARCHAR(100), UNIQUE)
   - `password` (VARCHAR(255) - hashed with BCRYPT)
   - `role` (ENUM('customer', 'admin') DEFAULT 'customer')
   - `created_at` (TIMESTAMP)

2. **`coffee_menu`**:
   - `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
   - `item_name` (VARCHAR(100))
   - `price` (DECIMAL(10,2))
   - `category` (VARCHAR(50) DEFAULT 'Hot Coffee')
   - `status` (ENUM('available', 'out_of_stock') DEFAULT 'available')

3. **`orders`**:
   - `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
   - `user_id` (INT, FOREIGN KEY referencing `users(id)` ON DELETE CASCADE)
   - `total_amount` (DECIMAL(10,2))
   - `payment_mode` (ENUM('Cash', 'UPI', 'Card'))
   - `order_status` (ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending')
   - `created_at` (TIMESTAMP)

4. **`order_items`**:
   - `id` (INT, AUTO_INCREMENT, PRIMARY KEY)
   - `order_id` (INT, FOREIGN KEY referencing `orders(id)` ON DELETE CASCADE)
   - `coffee_id` (INT, FOREIGN KEY referencing `coffee_menu(id)`)
   - `quantity` (INT)
   - `unit_price` (DECIMAL(10,2))

---

## 🚀 Step-by-Step Setup Guide (XAMPP / WAMP)

Follow these simple steps to run Grand Cafe locally on your computer using XAMPP or WAMP:

### Step 1: Place Project Files in Web Server Root

1. Copy or move the entire `Grand Cafe` folder into your local web server's root directory:
   - **For XAMPP**: `C:\xampp\htdocs\Grand Cafe`
   - **For WAMP**: `C:\wamp64\www\Grand Cafe`

### Step 2: Start Apache and MySQL

1. Open the **XAMPP Control Panel** (or WAMP manager).
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.
4. Ensure both services show green status indicators.

### Step 3: Initialize the Database

You can initialize the database using either of the two methods below:

#### Option A — 1-Click Automated Web Installer (Recommended):
1. Open your browser and navigate to:
   ```
   http://localhost/Grand Cafe/setup.php
   ```
2. Click **"🚀 Initialize Grand Cafe Database Now"**.
3. The script will automatically create `grand_cafe_db`, construct all tables, and seed the default menu and demo accounts.

#### Option B — Manual Import via phpMyAdmin:
1. Open your browser and visit: `http://localhost/phpmyadmin`
2. Click on the **Import** tab at the top.
3. Click **Choose File** and select the [`schema.sql`](file:///c:/Users/samis/OneDrive/Documents/Desktop/Grand%20Cafe/schema.sql) file located in the root of the project directory.
4. Click **Go** at the bottom to execute the script.
5. The `grand_cafe_db` database is now ready with all seed data!

### Step 4: Verify Database Credentials

If you are using default XAMPP/WAMP settings, no changes are needed. If your MySQL server uses custom credentials, open [`db.php`](file:///c:/Users/samis/OneDrive/Documents/Desktop/Grand%20Cafe/db.php) and adjust the constants:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'grand_cafe_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Add your password if configured
```

### Step 5: Launch the Application

Visit the following URL in any modern web browser:
```
http://localhost/Grand Cafe/
```

---

## 🔐 Default Demo Accounts

The database comes pre-seeded with ready-to-use accounts:

| Role | Email | Password | Dashboard Access |
|---|---|---|---|
| **Administrator** | `admin@grandcafe.com` | `admin123` | Full Admin Dashboard (`admin.php`) |
| **Customer** | `customer@grandcafe.com` | `customer123` | Ordering & Order History (`menu.php`, `my_orders.php`) |

> **Tip**: On the [`login.php`](file:///c:/Users/samis/OneDrive/Documents/Desktop/Grand%20Cafe/login.php) page, convenient **"Demo Admin"** and **"Demo Customer"** buttons are available to auto-fill these credentials instantly with one click.

---

## ☕ Pre-Seeded Coffee Menu

The coffee menu is initialized with the following artisan brews:

| Coffee Item | Category | Price | Status |
|---|---|---|---|
| Espresso | Hot Coffee | ₹80.00 | Available |
| Cappuccino | Hot Coffee | ₹120.00 | Available |
| Cafe Latte | Hot Coffee | ₹110.00 | Available |
| Americano | Hot Coffee | ₹100.00 | Available |
| Mocha | Hot Coffee | ₹130.00 | Available |
| Cold Coffee | Cold Coffee | ₹140.00 | Available |
| Filter Coffee | Hot Coffee | ₹70.00 | Available |
| Hazelnut Coffee | Specialty Coffee | ₹150.00 | Available |
| Caramel Coffee | Specialty Coffee | ₹150.00 | Available |

---

## 📂 Project Structure

```
Grand Cafe/
│
├── schema.sql              # MySQL database schema & pre-seed data
├── db.php                  # PDO database connection, charset & error handler
├── setup.php               # 1-Click web-based database installer
│
├── index.php               # Landing page with hero banner & featured brews
├── menu.php                # Coffee menu with live search, filters & cart controls
├── order.php               # Order route bridge referencing menu.php
├── login.php               # User authentication with demo login helpers
├── register.php            # New customer registration with validation
├── logout.php              # Session termination & clean redirect
├── payment.php             # Checkout, payment simulation & digital invoice view
├── my_orders.php           # Customer order tracking & order cancellation
├── admin.php               # Full Admin CRUD dashboard (Menu + Orders)
│
├── includes/
│   ├── auth_helper.php     # Session authorization, roles & flash messages
│   ├── header.php          # Responsive navbar, branding & cart drawer markup
│   └── footer.php          # Cafe hours, contact details, copyright & script inclusion
│
├── assets/
│   ├── css/
│   │   └── style.css       # Custom warm cafe CSS3 styling & print media queries
│   └── js/
│       └── main.js         # Interactive cart, AJAX/DOM updates, toasts & modals
│
└── README.md               # Complete setup and developer documentation
```

---

## 🛡️ Security Best Practices Implemented

- **Prepared Statements (PDO)**: All database interactions utilize prepared statements with parameter binding to prevent SQL Injection attacks.
- **Password Hashing**: Passwords are encrypted using PHP's standard `password_hash()` (BCRYPT) and verified with `password_verify()`.
- **Cross-Site Scripting (XSS) Prevention**: User-supplied input is sanitized through the custom `e()` escaping helper (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- **Tamper-Proof Price Validation**: During checkout in `payment.php`, all item prices and availability statuses are validated directly against MySQL records before order insertion, ignoring any manipulated client-side prices.
- **Session Protection**: Proper session fixation protection, explicit session invalidation on logout, and role validation guards (`requireLogin`, `requireAdmin`).
- **Database Integrity**: Foreign key constraints with `ON DELETE CASCADE` ensure referential integrity across orders and order line items.
