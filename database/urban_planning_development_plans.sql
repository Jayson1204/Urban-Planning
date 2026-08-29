-- Urban Planning
-- Module 5.1 - Development Plans (core planning record, no FK deps).
-- Later Phase 5 modules (Urban Projects, Infrastructure Records, Planning Documents)
-- will FK to `development_plans`.

CREATE TABLE IF NOT EXISTS `development_plans` (
  `plan_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_code` VARCHAR(50) NOT NULL,
  `plan_title` VARCHAR(150) NOT NULL,
  `plan_type` ENUM('Comprehensive Land Use Plan','Zoning Plan','Infrastructure Plan','Development Framework','Other') NOT NULL DEFAULT 'Other',
  `barangay` VARCHAR(100) NULL,
  `coverage_area` VARCHAR(255) NULL,
  `lead_department` VARCHAR(150) NULL,
  `budget_allocation` DECIMAL(14,2) NULL,
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `plan_status` ENUM('Draft','Active','Completed','Archived') NOT NULL DEFAULT 'Draft',
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`plan_id`),
  UNIQUE KEY `uniq_plan_code` (`plan_code`),
  KEY `idx_plan_barangay` (`barangay`),
  KEY `idx_plan_type` (`plan_type`),
  KEY `idx_plan_status` (`plan_status`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
