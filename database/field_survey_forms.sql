-- Field Survey module, sub-module 1 — Survey Forms (survey type/template definitions).
-- Master table for the Field Survey module; assignments and results FK into this.
CREATE TABLE IF NOT EXISTS `field_survey_forms` (
  `form_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_code` VARCHAR(50) NOT NULL,
  `form_title` VARCHAR(150) NOT NULL,
  `category` ENUM('Household Assessment','Infrastructure Condition','Land Use Assessment','Socioeconomic Survey','Other') NOT NULL DEFAULT 'Other',
  `subject_type` ENUM('Resident','Household','Site') NOT NULL DEFAULT 'Site',
  `description` TEXT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`form_id`),
  UNIQUE KEY `uniq_form_code` (`form_code`),
  KEY `idx_survey_forms_category` (`category`),
  KEY `idx_survey_forms_subject_type` (`subject_type`),
  KEY `idx_survey_forms_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
