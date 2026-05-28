-- ============================================================
-- Database Migration: Department Linking System
-- تحديث نظام ربط المراجعين بالدواوين
-- ============================================================

-- 1) إضافة جدول الدواوين (اختياري - للتعريف الأفضل)
CREATE TABLE IF NOT EXISTS `diwan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `name_ar` varchar(100) NOT NULL,
  `name_en` varchar(100),
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) إدراج الدواوين الأساسية
INSERT IGNORE INTO `diwan` (`code`, `name_ar`, `name_en`) VALUES
('public_attorney', 'ديوان المحامي العام', 'Public Attorney Office'),
('public_prosecution', 'ديوان الكاتب بالعدل', 'Public Prosecutor Office'),
('sharia', 'ديوان الشرعية', 'Sharia Court'),
('civil_beginning', 'ديوان البداية المدنية', 'Civil Court'),
('investigation', 'ديوان التحقيق', 'Investigation Office'),
('penal_reconciliation', 'ديوان صلح الجزاء', 'Penal Reconciliation Court');

-- 3) تحديث جدول customers لربطه بالديوان (اختياري)
ALTER TABLE `customers` ADD COLUMN `diwan_code` varchar(50) NULL AFTER `service_type`;
ALTER TABLE `customers` ADD COLUMN `diwan_name_ar` varchar(100) NULL AFTER `diwan_code`;

-- 4) تحديث جدول counters لتوضيح الديوان الرئيسي (اختياري)
ALTER TABLE `counters` ADD COLUMN `primary_diwan_code` varchar(50) NULL AFTER `service_types`;
ALTER TABLE `counters` ADD COLUMN `diwan_name_ar` varchar(100) NULL AFTER `primary_diwan_code`;

-- 5) إضافة index للأداء
CREATE INDEX `idx_customers_service_type` ON `customers` (`service_type`);
CREATE INDEX `idx_customers_diwan_code` ON `customers` (`diwan_code`);
CREATE INDEX `idx_customers_status` ON `customers` (`status`);
CREATE INDEX `idx_customers_created_at` ON `customers` (`created_at`);
CREATE INDEX `idx_counters_primary_diwan` ON `counters` (`primary_diwan_code`);

-- 6) تحديث البيانات الموجودة لربطها بالدواوين
UPDATE `counters` SET `primary_diwan_code` = 'public_attorney', `diwan_name_ar` = 'ديوان المحامي العام' WHERE `id` = 1;
UPDATE `counters` SET `primary_diwan_code` = 'public_prosecution', `diwan_name_ar` = 'ديوان الكاتب بالعدل' WHERE `id` = 2;
UPDATE `counters` SET `primary_diwan_code` = 'sharia', `diwan_name_ar` = 'ديوان الشرعية' WHERE `id` = 3;