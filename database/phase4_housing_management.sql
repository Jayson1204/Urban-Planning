-- Phase 4: Housing Management
-- Module 4.1 - Housing Units (master inventory record).
-- New local tables. Later Phase 4 modules (Beneficiaries, Occupancy, Relocation,
-- History) will add their own tables referencing `housing_units`.

CREATE TABLE IF NOT EXISTS `housing_units` (
  `unit_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_code` VARCHAR(50) NOT NULL,
  `project_name` VARCHAR(150) NULL,
  `unit_type` ENUM('Single Detached','Duplex','Row House','Medium Rise','Studio','Other') NOT NULL DEFAULT 'Other',
  `barangay` VARCHAR(100) NULL,
  `street_address` VARCHAR(255) NULL,
  `floor_area` DECIMAL(8,2) NULL,
  `bedrooms` TINYINT UNSIGNED NULL,
  `monthly_amortization` DECIMAL(12,2) NULL,
  `occupancy_status` ENUM('Vacant','Occupied','Reserved','Under Maintenance') NOT NULL DEFAULT 'Vacant',
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`unit_id`),
  UNIQUE KEY `uniq_unit_code` (`unit_code`),
  KEY `idx_housing_barangay` (`barangay`),
  KEY `idx_housing_occupancy` (`occupancy_status`),
  KEY `idx_housing_status` (`status`),
  KEY `idx_housing_project` (`project_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
