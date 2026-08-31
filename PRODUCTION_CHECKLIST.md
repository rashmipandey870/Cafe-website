# Mellow & Meadow Café — Production Deployment Checklist

Use this checklist to verify, configure, and secure the website package before handoff to the café owner.

---

## 1. Hosting Environment & Server Setup
- [ ] **PHP Version**: Host server is running **PHP 8.0 or newer**.
- [ ] **Database Engine**: MySQL 5.7+ or MariaDB 10.3+ is active on the server.
- [ ] **File Permissions**:
  - Web directories: `0755`
  - Upload folders (`uploads/menu/`, `uploads/gallery/`, `uploads/offers/`): `0755` (write permission enabled for uploads)
  - PHP Source files: `0644`
  - Database Config file (`config/db_config.php`): `0600` or `0644`.

---

## 2. Secure Database User Provisioning (No root in production!)
- [ ] **Create Database**: Create a new empty database via cPanel MySQL Database Wizard (e.g., `mellow_meadow_cafe`).
- [ ] **Create Dedicated User**: Create a dedicated database user with a strong password (e.g., `mellow_meadow_app`). Do **NOT** use `root` on the live server.
- [ ] **Grant Privileges**: Assign standard privileges:
  - Enabled: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `DROP`, `INDEX`, `ALTER`
  - Disabled: Administrative options (`GRANT`, `SUPER`, `SHUTDOWN`, etc.)

---

## 3. Web Installation and Schema Seeding
- [ ] **Run Web Installer**: Open your browser and navigate to `https://yourdomain.com/install.php`.
- [ ] **Enter Parameters**: Input the Host, Port, Name, Username, and Password.
- [ ] **Verify Installer Success**:
  - [ ] Database connects successfully.
  - [ ] Tables are created and default settings are seeded.
  - [ ] `config/db_config.php` file is written.
  - [ ] `config/install.lock` is created.

---

## 4. Indian Payment Gateway & Google Maps Setup
- [ ] **Client Razorpay Account**: Ensure the café owner has their own verified Razorpay account.
- [ ] **Configure Razorpay Secret Key**: Add `define('RAZORPAY_KEY_SECRET', 'rzp_live_secret_here');` inside `config/db_config.php` on the server.
- [ ] **Configure Admin Payment Settings**:
  - [ ] Set **Payment Environment** to **Live Mode** in **Admin Settings → Payments**.
  - [ ] Enter the client's **Razorpay Key ID** (`rzp_live_...`).
  - [ ] Enter the client's **Merchant UPI ID** (e.g. `cafe@upi`) and **Merchant Name**.
- [ ] **Configure Google Maps Embed**: Paste the café's Google Maps embed `src="..."` URL into **Admin Settings → Google Maps**.
- [ ] **Configure Table Ordering**: Ensure **Table Ordering** is enabled and print the acrylic table stands from **Admin → Table QR Codes**.

---

## 5. Security Audit & Final Hardening
- [ ] **Installer Lock Check**: Visit `https://yourdomain.com/install.php` and verify it returns **`403 Forbidden`**.
- [ ] **Diagnostics Lock Check**: Visit `https://yourdomain.com/tools/database-check.php` and verify it returns **`403 Forbidden`**.
- [ ] **Delete Diagnostics Directory**: Delete the entire `tools/` folder from the production server.
- [ ] **SSL / HTTPS Activation**:
  - Install a valid SSL certificate (AutoSSL / Let's Encrypt) in cPanel.
  - Verify HTTPS redirects smoothly.
- [ ] **Exposed File Blocking**: Verify that direct browser access to `config/db_config.php` or `database/cafe_database.sql` returns **`403 Forbidden`**.

---

## 6. Administrative Setup & Testing
- [ ] **Update Default Admin Password**: Log in to `/admin/` and change the default password (`AdminPassword123!`) immediately.
- [ ] **Mobile UX Responsiveness Check**: Test the site on a mobile device (360px–430px) to confirm:
  - [ ] Fixed bottom navigation bar works.
  - [ ] Floating cart summary pill (`2 Items | ₹380 • View Cart ➔`) updates dynamically.
  - [ ] Category horizontal scroller on `menu.php` scrolls smoothly.
  - [ ] Realtime live search filters dishes instantly.
- [ ] **End-to-End Order Flow Test**:
  - [ ] Place a test takeaway order with UPI QR.
  - [ ] Place a test dine-in order by scanning Table #01 QR code.
  - [ ] Verify orders show up in the Admin Orders panel with table badges and payment statuses.
