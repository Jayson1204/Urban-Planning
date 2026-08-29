-- Field Survey module, sub-module 2 — Survey Assignments.
-- A form assigned to a surveyor for a subject: a resident, a household, or a free-text site.
CREATE TABLE IF NOT EXISTS `field_survey_assignments` (
  `assignment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_id` INT UNSIGNED NOT NULL,
  `subject_type` ENUM('Resident','Household','Site') NOT NULL DEFAULT 'Site',
  `subject_id` INT UNSIGNED NULL,
  `site_label` VARCHAR(150) NULL,
  `site_address` VARCHAR(255) NULL,
  `assigned_to` VARCHAR(150) NULL,
  `due_date` DATE NULL,
  `assignment_status` ENUM('Pending','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`assignment_id`),
  KEY `idx_assignments_form` (`form_id`),
  KEY `idx_assignments_subject` (`subject_type`, `subject_id`),
  KEY `idx_assignments_status` (`assignment_status`),
  KEY `idx_assignments_record_status` (`status`),
  CONSTRAINT `fk_assignments_form` FOREIGN KEY (`form_id`) REFERENCES `field_survey_forms` (`form_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
