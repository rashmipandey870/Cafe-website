-- MySQL Upgraded Database Schema for Mellow & Meadow Café
-- Target: MySQL 8.0+

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `promotion_usage`;
DROP TABLE IF EXISTS `promotion_products`;
DROP TABLE IF EXISTS `promotion_categories`;
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `gallery`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Settings Table
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table (Admin / Staff accounts)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Customers Table (Supports both registered members and placeholder tracks for guests)
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NULL, -- NULL indicates guest checkout history
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `zip` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_customer_auth` (`email`, `phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Menu Items Table
CREATE TABLE `menu_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `image` VARCHAR(255) NULL,
  `is_vegetarian` TINYINT(1) DEFAULT 0,
  `is_available` TINYINT(1) DEFAULT 1,
  `is_featured` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Promotions Table
CREATE TABLE `promotions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `promotion_type` ENUM('percentage', 'fixed') NOT NULL,
  `discount_value` DECIMAL(10, 2) NOT NULL,
  `minimum_order_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `maximum_discount_amount` DECIMAL(10, 2) DEFAULT 99999.00,
  `coupon_code` VARCHAR(50) NULL UNIQUE,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NOT NULL,
  `priority` INT DEFAULT 1, -- 1 = Highest, 2 = Next, 3 = Next, etc.
  `is_active` TINYINT(1) DEFAULT 1,
  `allow_stacking` TINYINT(1) DEFAULT 0,
  `usage_limit` INT NULL DEFAULT NULL, -- NULL means unlimited
  `usage_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Promotion Products bridge (targets specific items)
CREATE TABLE `promotion_products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `promotion_id` INT NOT NULL,
  `menu_item_id` INT NOT NULL,
  CONSTRAINT `fk_promo_prod_promo` FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_prod_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Promotion Categories bridge (targets specific categories)
CREATE TABLE `promotion_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `promotion_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  CONSTRAINT `fk_promo_cat_promo` FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_cat_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Orders Table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `order_type` ENUM('pickup', 'delivery') NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `discount_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `delivery_charge` DECIMAL(10, 2) DEFAULT 0.00,
  `tax_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `promotion_id` INT NULL, -- References promotion that was actually applied
  `coupon_code` VARCHAR(50) NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
  `order_status` ENUM('pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled') DEFAULT 'pending',
  `delivery_address` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_order_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Order Items Table (Denormalized unit_price and item_name for historical records)
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `menu_item_id` INT NOT NULL,
  `item_name` VARCHAR(100) NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `quantity` INT NOT NULL,
  `discount_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Promotion Usage History Log Table
CREATE TABLE `promotion_usage` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `promotion_id` INT NOT NULL,
  `customer_id` INT NULL, -- Nullable for guests
  `order_id` INT NOT NULL,
  `coupon_code` VARCHAR(50) NULL,
  `discount_amount` DECIMAL(10, 2) NOT NULL,
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_usage_promo` FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Table Reservations Table
CREATE TABLE `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `reservation_date` DATE NOT NULL,
  `reservation_time` TIME NOT NULL,
  `guests` INT NOT NULL,
  `special_request` TEXT NULL,
  `status` ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Reviews Table
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_name` VARCHAR(100) NOT NULL,
  `rating` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `is_approved` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Contact Messages Table
CREATE TABLE `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Gallery Table
CREATE TABLE `gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NULL,
  `image` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INDEXES FOR PERFORMANCE
CREATE INDEX `idx_menu_category` ON `menu_items` (`category_id`);
CREATE INDEX `idx_menu_available` ON `menu_items` (`is_available`);
CREATE INDEX `idx_menu_featured` ON `menu_items` (`is_featured`);
CREATE INDEX `idx_orders_number` ON `orders` (`order_number`);
CREATE INDEX `idx_orders_customer` ON `orders` (`customer_id`);
CREATE INDEX `idx_orders_status` ON `orders` (`order_status`);
CREATE INDEX `idx_reservations_date` ON `reservations` (`reservation_date`);
CREATE INDEX `idx_promotions_active` ON `promotions` (`is_active`, `start_datetime`, `end_datetime`);
CREATE INDEX `idx_reviews_approved` ON `reviews` (`is_approved`);

-- --------------------------------------------------------
-- SAMPLE SEED DATA
-- --------------------------------------------------------

-- Seeding Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('cafe_name', 'Mellow & Meadow'),
('cafe_logo', 'assets/images/logo.png'),
('cafe_phone', '+91 98765 43210'),
('cafe_email', 'hello@mellowandmeadow.com'),
('cafe_address', '12, Sage Boulevard, Green Park, Delhi - 110016'),
('cafe_opening_hours', 'Mon - Sun: 8:00 AM - 10:00 PM'),
('cafe_whatsapp', '+919876543210'),
('cafe_social_facebook', 'https://facebook.com/mellowandmeadow'),
('cafe_social_instagram', 'https://instagram.com/mellowandmeadow'),
('cafe_social_twitter', 'https://twitter.com/mellowmeadow'),
('cafe_google_maps', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3504.6067986420556!2d77.1994537!3d28.5355161!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d1df639c0f997%3A0xe54d8ad442f4c9c2!2sHauz%20Khas%20Village%2C%20New%20Delhi!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin'),
('cafe_about_text', 'Mellow & Meadow is a sun-filled, plant-rich sanctuary designed for slow mornings, productive afternoons, and shared evening moments. We are dedicated to artisanal coffee, organic ingredients, and local sourcing. Every cup is brewed with care, and every plate is crafted to feed the soul. Step in for the coffee, stay for the warmth.'),
('cafe_timezone', 'Asia/Kolkata'),
('delivery_enabled', '1'),
('delivery_charge', '45.00'),
('free_delivery_above', '500.00'),
('minimum_delivery_order', '200.00'),
('tax_enabled', '1'),
('tax_rate', '5.00');

-- Seeding Users (Password: AdminPassword123!)
-- Bcrypt Hash of 'AdminPassword123!': $2y$10$B5W4p4dMszP9gXbUuqVpeuxpZ3/D/C2B2kKx.gqjQh9rP9Z9e0y1y
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES
('Administrator', 'admin@mellowandmeadow.com', '$2y$10$B5W4p4dMszP9gXbUuqVpeuxpZ3/D/C2B2kKx.gqjQh9rP9Z9e0y1y', 'admin');

-- Seeding Categories
INSERT INTO `categories` (`id`, `name`, `description`, `image`, `is_active`) VALUES
(1, 'Espresso Bar', 'Artisanal espresso drinks crafted with premium, locally-roasted single-origin beans.', 'assets/images/cat_coffee.jpg', 1),
(2, 'Teas & Infusions', 'Hand-plucked floral infusions, organic matcha, and soothing herbal teas.', 'assets/images/cat_tea.jpg', 1),
(3, 'All-Day Brunch', 'Fresh, bright breakfast favorites, seasonal toasts, and organic grain bowls.', 'assets/images/cat_brunch.jpg', 1),
(4, 'Bakes & Dessert', 'Freshly baked artisanal croissants, citrus tarts, and vegan treats.', 'assets/images/cat_pastry.jpg', 1);

-- Seeding Menu Items
INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image`, `is_vegetarian`, `is_available`, `is_featured`) VALUES
(1, 1, 'Vanilla Bean Latte', 'Double shot of house espresso, steamed oat milk, and house-made vanilla bean syrup.', 180.00, 'assets/images/menu_vanilla_latte.jpg', 1, 1, 1),
(2, 1, 'Sage Honey Cortado', 'A balanced 1:1 ratio of espresso and warm textured milk infused with organic wild honey and fresh sage.', 160.00, 'assets/images/menu_sage_cortado.jpg', 1, 1, 1),
(3, 1, 'Lavender Cold Brew', 'Smooth 16-hour slow-steeped cold brew topped with a light, sweet lavender-infused cold foam.', 190.00, 'assets/images/menu_cold_brew.jpg', 1, 1, 0),
(4, 2, 'Ceremonial Iced Matcha Latte', 'Premium grade stone-ground Uji matcha whisked with filtered water and poured over organic coconut milk.', 210.00, 'assets/images/menu_matcha.jpg', 1, 1, 1),
(5, 2, 'Peach Hibiscus Iced Tea', 'Bright floral hibiscus flowers cold brewed and shaken with fresh peach puree and lemon juice.', 150.00, 'assets/images/menu_hibiscus.jpg', 1, 1, 0),
(6, 3, 'Heirloom Avocado Toast', 'Smashed organic avocado, heirloom cherry tomatoes, pickled red onions, micro-greens, and sage-oil drizzle on toasted rustic sourdough.', 280.00, 'assets/images/menu_avo_toast.jpg', 1, 1, 1),
(7, 3, 'Wild Mushroom Sourdough', 'Sautéed forest wild mushrooms in a light cashew-cream sauce on toasted sourdough, garnished with fresh chives.', 290.00, 'assets/images/menu_mushroom.jpg', 1, 1, 0),
(8, 3, 'Terracotta Brunch Bowl', 'Warm red quinoa, roasted sweet potatoes, organic spinach, fresh avocado, poached free-range egg, and a creamy tahini-lemon dressing.', 320.00, 'assets/images/menu_brunch_bowl.jpg', 1, 1, 1),
(9, 4, 'Almond Sourdough Croissant', 'Flaky, butter croissant filled with frangipane almond cream and baked to golden perfection with sliced almonds.', 140.00, 'assets/images/menu_croissant.jpg', 1, 1, 1),
(10, 4, 'Meyer Lemon Tart', 'Crisp sweet pastry shell filled with velvety Meyer lemon curd, garnished with edible pansies.', 160.00, 'assets/images/menu_lemon_tart.jpg', 1, 1, 1),
(11, 4, 'Chocolate Babka Slice', 'Rich, braided sweet bread swirled with dark Belgian chocolate and roasted chopped pistachios.', 150.00, 'assets/images/menu_babka.jpg', 1, 1, 0);

-- Seeding Sample Promotions
-- Promotion 1: Diwali Special Coupon (20% percentage discount, min order ₹500, max discount ₹300, priority 1)
INSERT INTO `promotions` (`id`, `name`, `description`, `promotion_type`, `discount_value`, `minimum_order_amount`, `maximum_discount_amount`, `coupon_code`, `start_datetime`, `end_datetime`, `priority`, `is_active`, `allow_stacking`, `usage_limit`, `usage_count`) VALUES
(1, 'Diwali Special 2026', 'Enjoy a 20% discount on all orders above ₹500. Festive treats are best enjoyed together.', 'percentage', 20.00, 500.00, 300.00, 'DIWALI20', '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1, 1, 0, 1000, 1);

-- Promotion 2: Category specific Desserts discount (10% off on all Desserts category, priority 2)
INSERT INTO `promotions` (`id`, `name`, `description`, `promotion_type`, `discount_value`, `minimum_order_amount`, `maximum_discount_amount`, `coupon_code`, `start_datetime`, `end_datetime`, `priority`, `is_active`, `allow_stacking`, `usage_limit`, `usage_count`) VALUES
(2, 'Sweet Treat Discount', '10% off on our hand-crafted pastries and cakes.', 'percentage', 10.00, 0.00, 100.00, NULL, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 2, 1, 0, NULL, 0);

-- Link promotion 2 to category 4 (Bakes & Dessert)
INSERT INTO `promotion_categories` (`promotion_id`, `category_id`) VALUES (2, 4);

-- Seeding Reviews
INSERT INTO `reviews` (`customer_name`, `rating`, `comment`, `is_approved`) VALUES
('Ananya Sen', 5, 'Absolutely fell in love with Mellow & Meadow! The Sage Honey Cortado is spectacular, and the space is so bright, green, and inspiring. Perfect for catching up on books.', 1),
('Kabir Malhotra', 5, 'The Heirloom Avocado Toast is hands down the best I have ever had. Everything is fresh, clean, and beautifully plated. The staff are so warm!', 1),
('Sarah Vance', 4, 'Beautiful sunlit environment with lots of natural wood. The Ceremonial Iced Matcha Latte was creamy and authentic. Highly recommended for slow Sunday brunch.', 1);

-- Seeding Gallery Images
INSERT INTO `gallery` (`title`, `image`, `is_active`) VALUES
('Sunlit Seating Area', 'assets/images/gal_interior.jpg', 1),
('Pouring the Matcha Latte', 'assets/images/gal_matcha_pour.jpg', 1),
('Our Freshly Baked Pastries', 'assets/images/gal_pastries.jpg', 1),
('Artisanal Espresso Pull', 'assets/images/gal_espresso.jpg', 1),
('Rustic Sourdough Baking', 'assets/images/gal_sourdough.jpg', 1),
('Slow Mornings at M&M', 'assets/images/gal_brunch.jpg', 1);

-- Seeding Customers (password: CustomerPassword123!)
-- Bcrypt Hash of 'CustomerPassword123!': $2y$10$vYJzP6vH75N82.2lX11e0uP3Sg.G69Q/P6S9t8w7y6x5v4u3t2s1q
INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `password_hash`, `address`, `city`, `zip`) VALUES
(1, 'Aarav Sharma', 'aarav.sharma@gmail.com', '9876543211', '$2y$10$vYJzP6vH75N82.2lX11e0uP3Sg.G69Q/P6S9t8w7y6x5v4u3t2s1q', 'Flat 402, Block C, Green Meadows Apartment', 'New Delhi', '110016'),
(2, 'Divya Patel', 'divya.patel@gmail.com', '9876543212', NULL, NULL, NULL, NULL);

-- Seeding Orders
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_type`, `subtotal`, `discount_amount`, `delivery_charge`, `tax_amount`, `total_amount`, `promotion_id`, `coupon_code`, `payment_method`, `payment_status`, `order_status`, `delivery_address`, `notes`, `created_at`) VALUES
(1, 'ORD-20260831-7291', 1, 'delivery', 460.00, 92.00, 0.00, 18.40, 386.40, 1, 'DIWALI20', 'Cash on Delivery', 'pending', 'pending', 'Flat 402, Block C, Green Meadows Apartment, New Delhi - 110016', 'Please call on arrival. Do not ring bell.', '2026-08-31 18:30:00'),
(2, 'ORD-20260831-9812', 2, 'pickup', 320.00, 0.00, 0.00, 16.00, 336.00, NULL, NULL, 'Pay at Café', 'paid', 'completed', NULL, 'Extra syrup in the latte please.', '2026-08-31 16:15:00');

-- Seeding Order Items
INSERT INTO `order_items` (`order_id`, `menu_item_id`, `item_name`, `unit_price`, `quantity`, `discount_amount`, `subtotal`) VALUES
(1, 1, 'Vanilla Bean Latte', 180.00, 1, 36.00, 144.00),
(1, 6, 'Heirloom Avocado Toast', 280.00, 1, 56.00, 224.00),
(2, 8, 'Terracotta Brunch Bowl', 320.00, 1, 0.00, 320.00);

-- Seed Promotion Usage Log
INSERT INTO `promotion_usage` (`promotion_id`, `customer_id`, `order_id`, `coupon_code`, `discount_amount`, `used_at`) VALUES
(1, 1, 1, 'DIWALI20', 92.00, '2026-08-31 18:30:00');

-- Seeding Reservations
INSERT INTO `reservations` (`name`, `phone`, `email`, `reservation_date`, `reservation_time`, `guests`, `special_request`, `status`, `created_at`) VALUES
('Rohit Verma', '9876543222', 'rohit.verma@gmail.com', '2026-09-02', '11:00:00', 4, 'Window seat if possible, it is a birthday brunch.', 'confirmed', '2026-08-31 14:00:00'),
('Elena Rostova', '9876543223', 'elena.ros@yahoo.com', '2026-09-05', '09:30:00', 2, 'Gluten-free menu questions.', 'pending', '2026-08-31 19:45:00');

-- Seeding Contact Messages
INSERT INTO `messages` (`name`, `email`, `phone`, `subject`, `message`, `is_read`) VALUES
('Meera Nair', 'meera.nair@outlook.com', '9876543233', 'Catering Enquiry', 'Hello, do you support corporate lunch boxes or high-tea setups for a group of 30 people on weekends? Love your aesthetics.', 0),
('Vikram Goel', 'vikram.goel@gmail.com', '9876543234', 'Supplier Query', 'Hello, we are local growers of organic microgreens and heritage cherry tomatoes. We would love to share a catalog with you.', 1);
