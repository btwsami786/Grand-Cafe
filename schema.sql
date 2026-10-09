-- ==========================================================
-- Grand Cafe - Database Schema & Initial Seed Data
-- Database Name: grand_cafe_db
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `grand_cafe_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `grand_cafe_db`;

-- ----------------------------------------------------------
-- 1. Table structure for table `users`
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `coffee_menu`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Table structure for table `coffee_menu`
-- ----------------------------------------------------------
CREATE TABLE `coffee_menu` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'Hot Coffee',
  `status` ENUM('available', 'out_of_stock') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Table structure for table `orders`
-- ----------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_mode` ENUM('Cash', 'UPI', 'Card') NOT NULL,
  `order_status` ENUM('Pending', 'Confirmed', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. Table structure for table `order_items`
-- ----------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `coffee_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_coffee` FOREIGN KEY (`coffee_id`) REFERENCES `coffee_menu` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Seed Data for `users`
-- Passwords:
-- admin@grandcafe.com    -> admin123
-- customer@grandcafe.com -> customer123
-- ----------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin', 'admin@grandcafe.com', '$2y$10$vsLYsCM9xrUK7feOTE2x3.cBCtoSH41dprJJlULBCTjHbDMsnmUJW', 'admin'),
(2, 'customer', 'customer@grandcafe.com', '$2y$10$PFLVuJaIpQfJwJPtVp55guuvuWLHYfyRtKzCqyDR5vynpX.wh/BOS', 'customer');

-- ----------------------------------------------------------
-- Seed Data for `coffee_menu`
-- ----------------------------------------------------------
INSERT INTO `coffee_menu` (`id`, `item_name`, `price`, `category`, `status`) VALUES
(1, 'Espresso', 80.00, 'Hot Coffee', 'available'),
(2, 'Cappuccino', 120.00, 'Hot Coffee', 'available'),
(3, 'Cafe Latte', 110.00, 'Hot Coffee', 'available'),
(4, 'Americano', 100.00, 'Hot Coffee', 'available'),
(5, 'Mocha', 130.00, 'Hot Coffee', 'available'),
(6, 'Cold Coffee', 140.00, 'Cold Coffee', 'available'),
(7, 'Filter Coffee', 70.00, 'Hot Coffee', 'available'),
(8, 'Hazelnut Coffee', 150.00, 'Specialty Coffee', 'available'),
(9, 'Caramel Coffee', 150.00, 'Specialty Coffee', 'available');

-- ----------------------------------------------------------
-- Sample Initial Orders (Demonstration / Verification)
-- ----------------------------------------------------------
INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `payment_mode`, `order_status`, `created_at`) VALUES
(1, 2, 230.00, 'UPI', 'Confirmed', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 2, 150.00, 'Cash', 'Pending', DATE_SUB(NOW(), INTERVAL 25 MINUTE));

INSERT INTO `order_items` (`order_id`, `coffee_id`, `quantity`, `unit_price`) VALUES
(1, 2, 1, 120.00), -- 1x Cappuccino
(1, 3, 1, 110.00), -- 1x Cafe Latte
(2, 8, 1, 150.00); -- 1x Hazelnut Coffee
