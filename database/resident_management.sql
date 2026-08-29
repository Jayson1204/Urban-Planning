-- Phase 3: Resident Management
-- New local tables. Independent of the production-proxied `users`/`citizens` data
-- (see project memory: residents are decoupled from citizen portal accounts for now).

CREATE TABLE IF NOT EXISTS `households` (
  `household_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `household_number` VARCHAR(50) NULL,
  `barangay` VARCHAR(100) NOT NULL,
  `street_address` VARCHAR(255) NOT NULL,
  `household_type` ENUM('Owned','Renting','Informal Settler','Other') NOT NULL DEFAULT 'Other',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`household_id`),
  UNIQUE KEY `uniq_household_number` (`household_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `residents` (
  `resident_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `household_id` INT UNSIGNED NULL,
  `relationship_to_head` VARCHAR(30) NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(10) NULL,
  `birth_date` DATE NULL,
  `gender` ENUM('Male','Female','Other') NULL,
  `civil_status` ENUM('Single','Married','Widowed','Separated','Divorced') NULL,
  `contact_number` VARCHAR(20) NULL,
  `email` VARCHAR(150) NULL,
  `barangay` VARCHAR(100) NULL,
  `street_address` VARCHAR(255) NULL,
  `occupation` VARCHAR(100) NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resident_id`),
  KEY `idx_residents_household` (`household_id`),
  KEY `idx_residents_name` (`last_name`, `first_name`),
  KEY `idx_residents_barangay` (`barangay`),
  KEY `idx_residents_status` (`status`),
  CONSTRAINT `fk_residents_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `resident_documents` (
  `document_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resident_id` INT UNSIGNED NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  KEY `idx_documents_resident` (`resident_id`),
  CONSTRAINT `fk_documents_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
