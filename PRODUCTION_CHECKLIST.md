# Mellow & Meadow Café — Production Deployment Checklist

Use this checklist to verify, configure, and secure the website package before handoff to the café owner.

---

## 1. Hosting Environment & Server Setup
- [ ] **PHP Version**: Ensure the host server is configured to run **PHP 8.0 or newer**.
- [ ] **Database Engine**: Ensure MySQL 5.7+ or MariaDB 10.3+ is active on the server.
- [ ] **File Permissions**:
  - Web directories: `0755`
  - Upload folders (`uploads/menu/`, `uploads/gallery/`, `uploads/offers/`): `0755` (write permission enabled for image uploads)
  - PHP Source files: `0644`
  - Database Config file (`config/db_config.php`): `0600` or `0644` depending on server environment.

---

## 2. Secure Database User Provisioning (No root in production!)
- [ ] **Create Database**: Create a new empty database via cPanel MySQL Database Wizard (e.g., `mellow_meadow_cafe`).
- [ ] **Create User**: Create a dedicated database user with a strong random password (e.g., `mellow_meadow_app`). Do **NOT** use the default MySQL `root` account on the live server.
- [ ] **Grant Privileges**: Assign the user to the database with only standard web application privileges:
  - Enabled: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `DROP`, `INDEX`, `ALTER`
  - Disabled: Administrative options (`GRANT`, `SUPER`, `SHUTDOWN`, etc.)

---

## 3. Web Installation and Schema Seeding
- [ ] **Run Web Installer**: Open your browser and navigate to the domain followed by `install.php` (e.g. `https://yourdomain.com/install.php`).
- [ ] **Enter Parameters**: Input the Host, Port, Name, Username, and Password created in Section 2.
- [ ] **Verify Installer Success**:
  - [ ] Database connects successfully.
  - [ ] Tables are created and default settings are seeded.
  - [ ] `config/db_config.php` file is written.
  - [ ] `config/install.lock` is created.

---

## 4. Security Audit & Final Hardening
- [ ] **Installer Lock Check**: Visit `https://yourdomain.com/install.php` and verify it returns a **`403 Forbidden`** error page.
- [ ] **Diagnostics Lock Check**: Visit `https://yourdomain.com/tools/database-check.php` and verify it returns a **`403 Forbidden`** page.
- [ ] **Delete Diagnostics Directory**: Delete the entire `tools/` folder from the production server for defense-in-depth safety.
- [ ] **SSL / HTTPS Activation**:
  - Install a valid SSL certificate (Let's Encrypt / AutoSSL) via cPanel.
  - Ensure the redirect from HTTP to HTTPS is active in the root `.htaccess`.
- [ ] **Exposed File Blocking**: Verify that trying to access `config/db_config.php` or `database/cafe_database.sql` directly from the browser throws a **`403 Forbidden`** error.

---

## 5. Administrative Setup & Testing
- [ ] **Default Admin Profile Update**:
  - Log in to `/admin/` using credentials: `admin@mellowandmeadow.com` / `AdminPassword123!`
  - Navigate to phpMyAdmin or the database user manager and update the password hash inside the `users` table to a secure client password.
- [ ] **Timezone Calibration**: Navigate to **Settings** in the admin panel and select the café's local timezone (e.g., `Asia/Kolkata`) to synchronize promotions, order timestamps, and reservations correctly.
- [ ] **Menu pricing Test**: Add a test item, modify its price, toggle its availability status to "Unavailable", and confirm that the change renders instantly on the customer menu page.
- [ ] **Promotion/Coupon Test**: Set up a test percentage coupon (e.g. `TEST10`, 10% off, min order ₹100), add items to the cart, apply it, and verify that the checkout summary card recalculates the totals (discount, GST, and final price) via AJAX.
- [ ] **Reservation Flow Test**: Book a table slot from the customer page, verify that the notification badge incremented on the admin sidebar, and approve it inside the Admin Reservations list.
