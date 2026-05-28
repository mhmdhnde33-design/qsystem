-- employees table for employee login & counter-based permissions
-- Run after creating database/tables

CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `counter_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_employees_username` (`username`),
  KEY `idx_employees_counter_id` (`counter_id`),
  CONSTRAINT `fk_employees_counter` FOREIGN KEY (`counter_id`) REFERENCES `counters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

