-- Create matter_categories table and add matter_category_ids to hearings
-- Run this in MySQL if migrations haven't been run (e.g. php artisan migrate)

-- 1. Create matter_categories table
CREATE TABLE IF NOT EXISTS `matter_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sort_order` smallint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add matter_category_ids column to hearings (if not exists)
SET @col_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hearings' AND COLUMN_NAME = 'matter_category_ids'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `hearings` ADD COLUMN `matter_category_ids` JSON NULL DEFAULT NULL AFTER `matter_category`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Insert default matter categories (skip if already exist)
INSERT IGNORE INTO `matter_categories` (`name`, `sort_order`, `created_at`, `updated_at`) VALUES
('Эрүүгийн хэрэг', 1, NOW(), NOW()),
('Эрүүгийн хариуцлага', 2, NOW(), NOW()),
('Урьдчилсан хэлэлцүүлэг', 3, NOW(), NOW()),
('Иргэний хэрэг', 4, NOW(), NOW()),
('Захиргааны хэрэг', 5, NOW(), NOW()),
('Хөдөлмөрийн хэрэг', 6, NOW(), NOW()),
('Татан буулгах хэрэг', 7, NOW(), NOW()),
('Бусад', 8, NOW(), NOW());
