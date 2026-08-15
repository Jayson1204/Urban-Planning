-- Phase 11: Field Survey module, sub-module 3 — Survey Results.
-- One result per assignment: the surveyor's findings once fieldwork is done.
CREATE TABLE IF NOT EXISTS `field_survey_results` (
  `result_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` INT UNSIGNED NOT NULL,
  `survey_date` DATE NULL,
  `condition_rating` ENUM('Excellent','Good','Fair','Poor','Critical') NULL,
  `population_count` INT UNSIGNED NULL,
  `income_bracket` VARCHAR(50) NULL,
  `findings` TEXT NULL,
  `recommendations` TEXT NULL,
  `additional_notes` TEXT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`result_id`),
  UNIQUE KEY `uniq_results_assignment` (`assignment_id`),
  KEY `idx_results_condition` (`condition_rating`),
  KEY `idx_results_status` (`status`),
  CONSTRAINT `fk_results_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `field_survey_assignments` (`assignment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
