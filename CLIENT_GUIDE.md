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

## 3. Printing Table QR Code Stands (Contactless Ordering)

You can generate and print acrylic table stands for customers to order directly from their tables using their smartphones:

1. Click **Table QR Codes** in the left sidebar menu.
2. Select your **Table Number** (e.g., Table 01, Table 02, etc.).
3. A branded 4" x 6" acrylic table tent card will appear in the live preview.
4. Click **Print Stand** to print it directly onto your printer or card stock.
5. Place the printed stands on your tables. When customers scan the QR with their phone camera, their table number is automatically remembered and attached to their order!

---

## 4. Setting Up Razorpay & UPI Payments

You can accept online payments via Google Pay, PhonePe, Paytm, Cards, and Net Banking:

1. Go to **Settings** on the left menu and click the **Payments & Razorpay** tab.
2. Toggle **Enable Online Payments** to ON.
3. Enter your **Merchant UPI ID** (e.g. `yourcafe@upi` or `9876543210@paytm`) and **Merchant Display Name**.
4. Paste your **Razorpay Key ID** (`rzp_live_...` from your Razorpay Dashboard).
5. Set the mode to **Live Mode** when you are ready to accept real payments.
6. Click **Save All Settings**.

---

## 5. Updating Your Google Maps Location

1. Go to **Settings** and click the **Google Maps** tab.
2. Open Google Maps in a browser, find your café, click **Share → Embed a map**, and copy the `src="..."` URL.
3. Paste the URL into the **Google Maps Embed URL** field.
4. Click **Save All Settings**. The map will immediately update on your website's Contact page.

---

## 6. Creating Coupons & Offers (The Promotions Engine)

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
   * **Apply To**: Choose if it applies to the **Entire Menu**, specific **Categories**, or specific **Products**.
4. Click **Create Promotion**.

### B. How to Duplicate a Campaign
To run a past campaign again under a new name:
1. Go to the **Promotions** list.
2. Find the campaign and click the **Duplicate** button (the copy icon).
3. Update the dates or name, and click save!

---

## 7. Processing Incoming Orders

### A. Live New Order Banners
* The moment a customer places a checkout order, a bright notification banner (`🔔 NEW ORDER`) will appear at the top of your dashboard.
* An orange notification count badge will also display next to **Orders** in the left sidebar.

### B. Moving an Order Through the Prep Process
1. Click **Orders** in the left sidebar.
2. Click on the order to view its customer details, order type (Takeaway, Delivery, or Table Dine-In), and items ordered.
3. Check the **Payment Status**:
   * **Paid**: Customer paid online via Razorpay.
   * **Pending**: Customer chose Cash or direct UPI QR payment.
4. Update the **Order Status** as preparation proceeds:
   * **Confirmed**: You have accepted the order.
   * **Preparing**: Kitchen staff is preparing the food.
   * **Ready**: Order is packed for pickup or ready to be served to the table.
   * **Out for Delivery** (Delivery only): Courier is on the way.
   * **Completed**: Food delivered or served and payment collected.
   * **Cancelled**: If the order could not be fulfilled.

---

## 8. Simple Database Backups & Recovery

We recommend taking monthly database backups to protect your business records:

### A. Backing Up Your Database
1. Log in to your hosting cPanel -> **phpMyAdmin**.
2. Select your database from the left menu.
3. Click the **Export** tab at the top.
4. Keep the export method set to **Quick** and format set to **SQL**.
5. Click **Export** / **Go** to download your `.sql` file.

### B. Restoring Your Database
1. In phpMyAdmin, select your database.
2. Click the **Import** tab.
3. Click **Choose File**, select your backup `.sql` file, and click **Import**.
