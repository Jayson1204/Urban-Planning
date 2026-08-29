-- Housing Management
-- Module 4.2 - Beneficiaries. Links a Phase 3 resident (and optionally their
-- household) to a housing unit and tracks the applicant -> awarded lifecycle.

CREATE TABLE IF NOT EXISTS `housing_beneficiaries` (
  `beneficiary_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resident_id` INT UNSIGNED NOT NULL,
  `household_id` INT UNSIGNED NULL,
  `unit_id` INT UNSIGNED NULL,
  `application_date` DATE NULL,
  `award_date` DATE NULL,
  `beneficiary_status` ENUM('Applicant','Qualified','Awarded','Disqualified','Cancelled') NOT NULL DEFAULT 'Applicant',
  `category` ENUM('Informal Settler','Calamity Victim','Senior Citizen','PWD','Government Employee','Other') NOT NULL DEFAULT 'Other',
  `monthly_income` DECIMAL(12,2) NULL,
  `family_size` TINYINT UNSIGNED NULL,
  `remarks` TEXT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`beneficiary_id`),
  KEY `idx_ben_resident` (`resident_id`),
  KEY `idx_ben_household` (`household_id`),
  KEY `idx_ben_unit` (`unit_id`),
  KEY `idx_ben_status` (`beneficiary_status`),
  KEY `idx_ben_record_status` (`status`),
  CONSTRAINT `fk_ben_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ben_household` FOREIGN KEY (`household_id`) REFERENCES `households` (`household_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ben_unit` FOREIGN KEY (`unit_id`) REFERENCES `housing_units` (`unit_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
