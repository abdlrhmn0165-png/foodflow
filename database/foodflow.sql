-- ============================================================
-- FILE: database/foodflow.sql
-- HOW TO USE: Open phpMyAdmin > Create DB "foodflow" > Import this file
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create and select database
CREATE DATABASE IF NOT EXISTS `foodflow` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `foodflow`;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`category_name`, `description`) VALUES
('Burgers', 'Juicy handcrafted burgers'),
('Pizza', 'Stone-baked artisan pizzas'),
('Pasta', 'Authentic Italian pasta dishes'),
('Salads', 'Fresh garden and gourmet salads'),
('Desserts', 'Indulgent sweets and treats'),
('Beverages', 'Cold and hot drinks'),
('Starters', 'Appetizers and small plates'),
('Grills', 'BBQ and grilled specialties');

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin: password = admin123 | Customer: password = customer123
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) VALUES
('Admin User', 'admin@foodflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Sarah Johnson', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
('Mike Chen', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
('Emma Wilson', 'emma@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
('James Rodriguez', 'james@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer'),
('Priya Sharma', 'priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

-- ============================================================
-- TABLE: menu_items
-- ============================================================
CREATE TABLE `menu_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `item_name` VARCHAR(150) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Available','Out of Stock') NOT NULL DEFAULT 'Available',
  `image` VARCHAR(255) DEFAULT 'default-food.jpg',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `menu_items` (`item_name`, `category_id`, `description`, `price`, `status`, `image`) VALUES
('Classic Smash Burger', 1, 'Double smash patty, cheddar, special sauce, brioche bun', 12.99, 'Available', 'burger1.jpg'),
('BBQ Bacon Stack', 1, 'Triple beef, crispy bacon, BBQ sauce, onion rings', 15.99, 'Available', 'burger2.jpg'),
('Veggie Delight Burger', 1, 'Portobello mushroom, avocado, swiss cheese', 11.49, 'Available', 'burger3.jpg'),
('Margherita Pizza', 2, 'San Marzano tomatoes, fresh mozzarella, basil', 13.99, 'Available', 'pizza1.jpg'),
('Pepperoni Inferno', 2, 'Double pepperoni, spicy chili flakes, mozzarella', 15.49, 'Available', 'pizza2.jpg'),
('BBQ Chicken Pizza', 2, 'Smoked chicken, BBQ sauce, red onion, cilantro', 14.99, 'Available', 'pizza3.jpg'),
('Spaghetti Carbonara', 3, 'Guanciale, eggs, pecorino romano, black pepper', 14.49, 'Available', 'pasta1.jpg'),
('Penne Arrabbiata', 3, 'Spicy tomato sauce, garlic, fresh parsley', 12.99, 'Available', 'pasta2.jpg'),
('Caesar Salad', 4, 'Romaine, croutons, parmesan, classic Caesar dressing', 9.99, 'Available', 'salad1.jpg'),
('Greek Salad', 4, 'Cucumber, olives, feta, tomatoes, red onion', 10.49, 'Available', 'salad2.jpg'),
('Chocolate Lava Cake', 5, 'Warm chocolate cake with molten center, vanilla ice cream', 7.99, 'Available', 'dessert1.jpg'),
('Tiramisu', 5, 'Classic Italian espresso-soaked ladyfingers, mascarpone', 6.99, 'Available', 'dessert2.jpg'),
('Mango Smoothie', 6, 'Fresh mango, yogurt, honey, coconut milk', 5.49, 'Available', 'drink1.jpg'),
('Craft Lemonade', 6, 'Hand-squeezed lemons, mint, sparkling water', 4.49, 'Available', 'drink2.jpg'),
('Garlic Bread', 7, 'Toasted sourdough, roasted garlic butter, herbs', 4.99, 'Available', 'starter1.jpg'),
('Mozzarella Sticks', 7, 'Crispy breaded mozzarella, marinara dipping sauce', 6.99, 'Available', 'starter2.jpg'),
('Grilled Salmon', 8, 'Atlantic salmon, lemon herb butter, seasonal vegetables', 22.99, 'Available', 'grill1.jpg'),
('BBQ Ribs', 8, 'Slow-smoked baby back ribs, house BBQ sauce, coleslaw', 24.99, 'Out of Stock', 'grill2.jpg');

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `order_status` ENUM('Pending','Confirmed','Preparing','Ready','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `orders` (`user_id`, `total_amount`, `order_status`, `created_at`) VALUES
(2, 28.98, 'Delivered', '2025-01-05 12:30:00'),
(3, 45.47, 'Delivered', '2025-01-10 14:00:00'),
(4, 19.98, 'Delivered', '2025-01-15 19:15:00'),
(5, 37.98, 'Preparing', '2025-01-20 20:00:00'),
(2, 22.98, 'Confirmed', '2025-02-02 13:00:00'),
(6, 31.48, 'Delivered', '2025-02-10 18:30:00'),
(3, 15.99, 'Delivered', '2025-02-14 12:00:00'),
(4, 52.97, 'Delivered', '2025-03-01 19:00:00'),
(5, 20.48, 'Delivered', '2025-03-08 21:00:00'),
(2, 41.98, 'Pending',   '2025-03-15 11:30:00'),
(6, 27.98, 'Delivered', '2025-04-02 17:00:00'),
(3, 18.98, 'Delivered', '2025-04-10 20:00:00');

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `menu_item_id` INT(11) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_items` (`order_id`, `menu_item_id`, `quantity`, `price`) VALUES
(1, 1, 1, 12.99),(1, 4, 1, 13.99),(1, 15, 1, 4.99),
(2, 5, 2, 15.49),(2, 7, 1, 14.49),
(3, 9, 1, 9.99),(3, 11, 1, 7.99),
(4, 2, 2, 15.99),(4, 13, 1, 5.49),
(5, 3, 2, 11.49),(6, 6, 1, 14.99),(6, 16, 2, 6.99),
(7, 4, 1, 15.99),(8, 17, 2, 22.99),(8, 9, 1, 9.99),
(9, 10, 1, 10.49),(9, 14, 2, 4.49),(10, 1, 2, 12.99),(10, 12, 2, 6.99),
(11, 5, 1, 15.49),(11, 13, 2, 5.49),(12, 8, 1, 12.99),(12, 16, 1, 6.99);

-- ============================================================
-- TABLE: payments
-- ============================================================
CREATE TABLE `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('Cash','Card','Online','Wallet') NOT NULL DEFAULT 'Cash',
  `payment_status` ENUM('Paid','Pending','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `payment_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `payments` (`order_id`, `amount`, `payment_method`, `payment_status`, `payment_date`) VALUES
(1, 28.98, 'Card', 'Paid', '2025-01-05 12:35:00'),
(2, 45.47, 'Online', 'Paid', '2025-01-10 14:05:00'),
(3, 19.98, 'Cash', 'Paid', '2025-01-15 19:20:00'),
(4, 37.98, 'Card', 'Paid', '2025-01-20 20:05:00'),
(5, 22.98, 'Wallet', 'Pending', '2025-02-02 13:05:00'),
(6, 31.48, 'Card', 'Paid', '2025-02-10 18:35:00'),
(7, 15.99, 'Cash', 'Paid', '2025-02-14 12:05:00'),
(8, 52.97, 'Online', 'Paid', '2025-03-01 19:05:00'),
(9, 20.48, 'Card', 'Paid', '2025-03-08 21:05:00'),
(10, 41.98, 'Cash', 'Pending', '2025-03-15 11:35:00'),
(11, 27.98, 'Card', 'Paid', '2025-04-02 17:05:00'),
(12, 18.98, 'Online', 'Paid', '2025-04-10 20:05:00');

-- ============================================================
-- TABLE: feedback
-- ============================================================
CREATE TABLE `feedback` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `rating` TINYINT(1) NOT NULL DEFAULT 5,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `feedback` (`user_id`, `message`, `rating`) VALUES
(2, 'Amazing food! The smash burger was absolutely incredible. Will definitely order again.', 5),
(3, 'Great service and fast delivery. The pasta carbonara was perfect.', 5),
(4, 'Really good food, portions are generous and prices are reasonable.', 4),
(5, 'Love the BBQ bacon stack, best burger I have had in a long time!', 5),
(6, 'The tiramisu was divine. Highly recommend the desserts section.', 4);

-- ============================================================
-- TABLE: activity_logs
-- ============================================================
CREATE TABLE `activity_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `activity` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `activity_logs` (`user_id`, `activity`) VALUES
(1, 'Added new menu item: BBQ Ribs'),
(2, 'Placed order #10'),
(3, 'Registered new account'),
(1, 'Updated menu item: Pepperoni Inferno'),
(4, 'Cancelled order #3'),
(1, 'Added new category: Grills'),
(5, 'Placed order #4'),
(2, 'Left feedback with 5 stars');

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `notifications` (`title`, `message`, `status`) VALUES
('New Order Received', 'Order #10 has been placed by Sarah Johnson for $41.98', 'unread'),
('Low Stock Alert', 'BBQ Ribs is now Out of Stock. Please update inventory.', 'unread'),
('New Feedback', 'James Rodriguez left a 5-star review!', 'read'),
('New Customer', 'Priya Sharma just registered on FoodFlow.', 'read');

COMMIT;