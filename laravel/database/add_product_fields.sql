-- SQL Script to add missing fields to products table
-- Run this in your MySQL database to enable full add product functionality

ALTER TABLE `products`
ADD COLUMN `discount_price` DECIMAL(10,2) NULL AFTER `price`,
ADD COLUMN `category_id` INT UNSIGNED NULL AFTER `category`,
ADD COLUMN `stock_quantity` INT NOT NULL DEFAULT 0 AFTER `category_id`,
ADD COLUMN `sku` VARCHAR(100) NULL AFTER `stock_quantity`,
ADD COLUMN `weight` DECIMAL(8,2) NULL AFTER `sku`,
ADD COLUMN `dimensions` VARCHAR(255) NULL AFTER `weight`,
ADD COLUMN `ingredients` JSON NULL AFTER `dimensions`,
ADD COLUMN `allergens` JSON NULL AFTER `ingredients`,
ADD COLUMN `status` ENUM('active','draft','out_of_stock') NOT NULL DEFAULT 'active' AFTER `allergens`,
ADD COLUMN `is_featured` BOOLEAN NOT NULL DEFAULT FALSE AFTER `status`,
ADD COLUMN `meta_title` VARCHAR(255) NULL AFTER `is_featured`,
ADD COLUMN `meta_description` TEXT NULL AFTER `meta_title`,
ADD COLUMN `images` JSON NULL AFTER `image_path`;
