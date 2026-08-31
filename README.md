# Mellow & Meadow Café — Full-Stack Management & Ordering Website Handover Documentation

Welcome to the production-ready source code repository for **Mellow & Meadow Specialty Café & Brunch**. This website is built using **vanilla PHP 8+**, **MySQL**, **Bootstrap 5**, and **Vanilla JavaScript**. It is fully self-contained, database-portable, secure, responsive, mobile-first, and optimized for Indian customer payment workflows (UPI / Razorpay / Net Banking / Cards).

---

## 1. Project Directory Structure

```text
cafe-website/
│
├── index.php                   # Homepage (Hero, search, categories, popular items, testimonials, contact CTA)
├── about.php                   # About Us page (philosophies, ingredient quality)
├── menu.php                    # Dynamic Mobile-First Menu (search filter, sticky category pills, table indicator)
├── offers.php                  # Special offers & coupons listing (queries Promotions Engine)
├── gallery.php                 # Image gallery with masonry grid and captions
├── reservation.php             # Table Booking Form (validates dates & slots)
├── cart.php                    # Shopping Cart (quantity toggling, pickup/dine-in/delivery option)
├── checkout.php                # Order Placement (Razorpay modal, NPCI UPI QR, server-calculated totals, DB transactions)
├── order-success.php           # Order Confirmation (receipt, dynamic UPI payment QR, mobile payment intent)
├── track-order.php             # Live Order Status Timeline (visual milestones tracker)
├── contact.php                 # Dynamic contact form & responsive Google Maps embed
├── login.php                   # Customer Login portal
├── register.php                # Optional Customer Registration page
├── logout.php                  # Customer Session Destroyer
├── install.php                 # Secure Web Installer (setup wizard, connection tests, auto-seeding)
│
├── admin/                      # Secure Administration Portal
│   ├── index.php               # Gateway Router (checks active session)
│   ├── login.php               # Admin Login (CSRF validated, bcrypt check)
│   ├── logout.php              # Secure Session Destroyer
│   ├── dashboard.php           # KPI Stats Overview & Live 🔔 NEW ORDER notification banner
│   ├── categories/             # Categories CRUD panel
│   ├── menu/                   # Menu Items CRUD panel (with safe image upload & soft-deactivation)
│   ├── promotions/             # Promotions CRUD panel (percentage/fixed limits & Duplicate button)
│   ├── orders/                 # Orders Search & Status Management (table badges, gateway payment audit IDs)
│   ├── reservations/           # Table Reservation Confirmations & Date Filters panel
│   ├── customers/              # Customers Directory panel
│   ├── reviews/                # Testimonials moderation panel
│   ├── gallery/                # Gallery image uploader & disk unlinker panel
│   ├── messages/               # Contact submissions inbox panel
│   ├── reports/                # Sales Performance Reports (presets & rankings)
│   ├── qrcode/                 # Table QR Code Generator & Printable Acrylic Stand Designer
│   ├── settings/               # Tabbed Settings Center (Razorpay, UPI ID, Google Maps, QR, Delivery, Tax)
│   └── includes/               # Admin template layout files
│
├── config/
│   ├── database.php            # PDO Database Connection & Error Silencing
│   ├── config.php              # Session configurations, Timezones & Table persistence loader
│   ├── db_config.example.php   # Database credentials reference template (exclude real files from git)
│   └── db_config.php           # Active database & payment secrets (excluded from Git)
│
├── includes/                   # Core application header/footer, functions and helper files
│   ├── header.php              # Public HTML head, Dynamic SEO tags & structured metadata
│   ├── navbar.php              # Main Navigation header & Dynamic account login toggles
│   ├── bottom_nav.php          # Fixed App-Like Bottom Navigation for mobile devices (< 768px)
│   ├── footer.php              # Reusable footer layout, map coordinates & JS loader
│   ├── functions.php           # Utilities: XSS escape, image upload filter, order numbers
│   ├── csrf.php                # CSRF cryptosecure token generation & verification (POST/GET verification)
│   ├── auth.php                # Login validation routines
│   ├── razorpay.php            # Server-Side Razorpay order creator & HMAC-SHA256 signature verifier
│   ├── qrcode.php              # High-resolution QR code generator helper
│   └── order_calculator.php    # Central calculations engine (taxes, coupon codes, delivery rules)
│
├── api/
│   ├── calculate-total.php     # Checkout calculation endpoint (called dynamically via AJAX)
│   ├── razorpay-create.php     # Server-side Razorpay order initiation endpoint
│   └── razorpay-webhook.php    # Idempotent Razorpay webhook notification receiver
│
├── tools/
│   └── database-check.php      # Development Environment & Database Schema Diagnostics
│
├── assets/                     # Stylesheets and frontend scripts
│   ├── css/style.css           # Custom theme colors (Sage, Terracotta, Ivory), mobile bottom nav & food cards
│   └── js/cart.js              # Client-side cart manager & floating cart pill synchronization
│
├── uploads/                    # Target directory for uploaded photos
│   ├── menu/
│   ├── gallery/
│   └── offers/
│
├── database/
│   └── cafe_database.sql       # Master Database Schema & Sample Seeding
│
└── .htaccess                   # Root Apache performance, caching & security rules
```

---

## 2. Indian Payment Integration Architecture

The café website supports all major Indian digital payment methods via **Razorpay** and **Direct NPCI UPI QR Codes**:

### Supported Payment Channels:
* **UPI**: Google Pay, PhonePe, Paytm, BHIM, Cred UPI, and all bank UPI applications.
* **Cards**: Visa, Mastercard, RuPay, and Maestro (Credit & Debit).
* **Net Banking**: 50+ Indian banks (HDFC, ICICI, SBI, Axis, Kotak, Punjab National Bank, etc.).
* **Wallets**: Paytm Wallet, Mobikwik, etc.
* **Pay at Counter / Cash on Delivery**: For takeaway, dine-in, and local delivery.

### Payment Security Rules:
1. **Server-Calculated Amounts**: The final payable amount is **always** recalculated by the server from database menu prices. The browser is never trusted for the total amount.
2. **HMAC-SHA256 Signature Verification**: For online gateway payments, the server verifies `hash_hmac('sha256', $order_id . '|' . $payment_id, $secret)` before updating `payment_status = 'paid'`.
3. **Separation of Statuses**:
   * **Payment Status**: `pending`, `paid`, `failed`, `refunded`
   * **Order Status**: `pending`, `confirmed`, `preparing`, `ready`, `out_for_delivery`, `completed`, `cancelled`
4. **Zero Sensitive Data Stored**: No credit card numbers, CVVs, card PINs, or UPI PINs are ever captured or stored on the server.
5. **Secret Key Isolation**: The `RAZORPAY_KEY_SECRET` is stored in the server-side configuration file (`config/db_config.php`) and is **never** printed in HTML, JavaScript, or AJAX responses.

---

## 3. Table QR Codes & Contactless Ordering

The application includes a complete QR code management system:

### 1. Table Stand Generator (`/admin/qrcode/index.php`)
* Allows the café owner to generate individual QR codes for Table 1 to Table 30.
* Generates an acrylic table stand template featuring the café's branding:
  ```text
  ┌───────────────────────────┐
  │      MELLOW & MEADOW      │
  │   Specialty Café & Brunch │
  │                           │
  │        [ QR CODE ]        │
  │                           │
  │       SCAN TO ORDER       │
  │         TABLE 04          │
  │   Free Wi-Fi: MellowGuest │
  └───────────────────────────┘
  ```
* Includes a **Print Stand** button formatted for standard 4" x 6" acrylic table stands.

### 2. Session Table Persistence
* Scanning a table QR code encodes `https://domain.com/menu.php?table=4`.
* The system automatically captures `Table #4` in the customer's session (`$_SESSION['table_number']`).
* During checkout, "Dine-In (Table #4)" is automatically selected and the table number is permanently logged in the order records.

---

## 4. Mobile-First UX / UI (Swiggy / Zomato Inspired)

The website is engineered for app-like usability on mobile screens:

1. **Fixed Mobile Bottom Navigation**: Quick one-tap access to Home, Menu, Offers, Cart (with live item count badge), and Orders/Account.
2. **Floating Cart Summary Bar**: A floating pill (`2 Items | ₹380 • View Cart ➔`) that updates in real-time as items are added and allows one-tap checkout.
3. **Live Realtime Menu Search**: Instant client-side search bar filtering dishes dynamically as the customer types.
4. **Sticky Horizontal Category Pills**: Touch-scrollable category tabs with active pill highlights.
5. **Compact Modern Food Cards**: Displays Veg (🟢) / Non-Veg (🔴) indicators, price, crisp thumbnails, and touch-friendly "+ ADD" buttons.

---

## 5. Client Payment Ownership & Gateway Setup

To hand over the website to a café owner:

### Step 1: Client Creates Their Own Razorpay Account
1. The café owner signs up at **[https://razorpay.com](https://razorpay.com)** with their business details and bank account.
2. Once verified, they log in to their Razorpay Dashboard and navigate to **Settings → API Keys**.
3. Generate **Key ID** and **Key Secret**.

### Step 2: Configure Development vs Production Mode
* **Development / Testing**:
  * In Admin Settings, set **Payment Environment** to **Test Mode**.
  * Use the Razorpay Test Key ID (`rzp_test_...`).
* **Production / Live**:
  * In Admin Settings, set **Payment Environment** to **Live Mode**.
  * Enter the client's Live Key ID (`rzp_live_...`).
  * Add the client's Live Key Secret (`RAZORPAY_KEY_SECRET`) to `config/db_config.php`.

---

## 6. Google Maps Configuration

The café location is configured in **Admin Settings → Google Maps**:
1. Open Google Maps, search your café location, and click **Share → Embed a map**.
2. Copy the `src="..."` URL and paste it into the **Google Maps Embed URL** field in Settings.
3. The map automatically renders responsively on the **Contact Us** page and footer.
