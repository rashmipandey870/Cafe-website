# Mellow & Meadow Café — Owner's Management Portal Guide

Welcome to your website’s admin panel! This guide is designed for you and your staff. You do **not** need any coding knowledge or technical background to run your online business. Everything can be operated directly from your web dashboard.

---

## 1. How to Access Your Dashboard

1. Open your web browser and go to your website address followed by `/admin/` (for example: `https://www.yourcafe.com/admin/`).
2. Log in using your registered business email and secure password:
   * **Default Username**: `admin@mellowandmeadow.com`
   * **Default Password**: `AdminPassword123!`
   
> [!IMPORTANT]
> For security reasons, please change this temporary password immediately after logging in.

---

## 2. Managing Your Menu & Pricing

### A. How to Add a New Item to the Menu
1. Log in to the Admin Dashboard.
2. Click **Menu Items** on the left menu.
3. Click the **Add Menu Item** button at the top right.
4. Fill in the form:
   * **Name**: The name of the dish (e.g., "Lavender Ice Latte").
   * **Category**: Select which section it belongs to (e.g., "Espresso Bar").
   * **Price**: Set the menu price in Rupees (do not add currency symbols like ₹).
   * **Image**: Upload a clear photo of the food.
   * **Vegetarian Toggle**: Check the box if the dish is vegetarian.
   * **Featured Toggle**: Check this box if you want this item featured on the main homepage.
5. Click **Create Menu Item** at the bottom. The item is now immediately live on the public website!

### B. How to Update Prices or Edit Items
1. Go to **Menu Items** on the left menu.
2. Find the item you wish to modify and click the **Edit** button (the pencil icon).
3. Change the price number, name, or photo, and click **Save Changes**. The changes take effect immediately on the site.

### C. How to Mark an Item "Sold Out" or "Available"
If you run out of an ingredient, you can temporarily disable the item so customers cannot order it:
1. Go to **Menu Items** on the left menu.
2. Find the item in the list.
3. Under the **Ordering** column, click the **Power** icon button to toggle its availability.
4. When marked **Unavailable**, the website automatically displays a "Sold Out" label on the menu card and prevents customers from adding it to their carts or checking out.

---

## 3. Creating Coupons & Offers (The Promotions Engine)

You can run sales campaigns, happy hours, or coupon promotions from the dashboard.

### A. How to Create a Coupon Code (e.g., "DIWALI20")
1. Click **Promotions** on the left menu.
2. Click the **Create Promotion** button at the top right.
3. Fill in the campaign form:
   * **Promotion Name**: The name of the campaign (e.g., "Diwali Special").
   * **Priority**: If multiple promotions are active, set which one applies first (1 = Highest Priority).
   * **Discount Type**: Choose **Percentage Discount** (e.g., 20%) or **Fixed Amount Deduction** (e.g., ₹100).
   * **Discount Value**: Enter the numeric discount value (e.g., `20` for 20% or `100` for ₹100).
   * **Coupon Code**: Type the coupon customers should enter (e.g., `DIWALI20`). If you leave this empty, the discount applies automatically to all qualifying carts!
   * **Minimum Order**: The cart value required to use the coupon (e.g., ₹500).
   * **Maximum Discount**: The maximum discount cap allowed (e.g., limit a 20% discount to a maximum of ₹300).
   * **Start / End Time**: Select the dates and times when the campaign starts and ends.
   * **Apply To**: Choose if it applies to the **Entire Menu**, specific **Categories** (e.g. only desserts), or specific **Products**.
4. Click **Create Promotion**.

### B. How to Duplicate a Campaign
To run a past campaign again under a new name:
1. Go to the **Promotions** list.
2. Find the campaign and click the **Duplicate** button (the copy icon).
3. The form will load all configuration data automatically. Just update the dates or name, and click save!

---

## 4. Processing Incoming Orders

When customers place orders, they appear in the administration system.

### A. Live New Order Banners
* The moment a customer places a checkout order, a bright notification banner (`🔔 NEW ORDER`) will appear at the top of your dashboard.
* An orange notification count badge will also display next to **Orders** in the left sidebar.

### B. Moving an Order Through the Prep Process
1. Click **Orders** in the left sidebar.
2. Find the new order (status: **Pending**) and click the view button.
3. Review the items, customer phone, notes, and fulfillment type (Pickup or Delivery).
4. Update the order status using the dropdown selector as preparation proceeds:
   * **Confirmed**: You have accepted the order and are printing the ticket.
   * **Preparing**: Baristas and chefs are preparing the items.
   * **Ready**: The order is packed at the counter for pickup, or ready for the delivery driver.
   * **Out for Delivery** (Delivery only): The courier has left the building.
   * **Completed**: The customer has paid and received their order.
   * **Cancelled**: If you need to reject the order (e.g., sold out), mark it cancelled. The customer's tracking screen will immediately display "Order Cancelled."

---

## 5. Booking Reservations & Testimonials

### A. Customer Table Reservations
1. Click **Reservations** on the left menu.
2. View incoming requests, contact phone numbers, requested times, and special requests (e.g. "birthday brunch").
3. Click the green checkmark to **Confirm** the booking, or click the red "X" to cancel it.

### B. Website Reviews
1. Click **Reviews** in the sidebar.
2. Read the reviews customers submit.
3. Click the **Approve** button to display a positive review on your homepage carousel, or click **Unapprove** to hide it.

---

## 6. Adjusting Website Settings & Café Information

1. Click **Settings** on the left menu.
2. Modify your business hours, contact numbers, address, and social links.
3. Manage tax and delivery calculations:
   * **Tax/GST Toggle & Rate**: Turn sales tax on/off and set the percentage (e.g., `5.00` for 5% GST).
   * **Delivery Charge**: The standard delivery surcharge.
   * **Minimum Delivery Order**: The lowest order subtotal allowed for home delivery checkouts.
   * **Free Delivery Threshold**: The order subtotal value above which the delivery fee is waived.
4. Click **Save Website Configurations** to apply changes.

---

## 7. Simple Database Backups & Recovery

Because you own your website and database data, we recommend taking backups monthly to protect your business logs.

### A. Backing Up Your Database
1. Log in to your hosting control panel (cPanel).
2. Find the **Databases** section and click **phpMyAdmin**.
3. Select your database from the left-side menu (e.g. `yourcafe_db`).
4. Click the **Export** tab at the top.
5. Keep the export method set to **Quick** and format set to **SQL**.
6. Click **Export** / **Go**. A file ending in `.sql` will download. Store this file securely.

### B. Restoring Your Database (Recovery)
In case of a server crash or database loss:
1. Log in to cPanel -> **phpMyAdmin** and select your database.
2. Click the **Import** tab at the top.
3. Click **Choose File** and select your saved `.sql` backup file.
4. Click **Import** / **Go** at the bottom of the page. All settings, menu items, and order history will be restored!
