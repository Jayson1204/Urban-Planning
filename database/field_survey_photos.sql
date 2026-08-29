-- Phase 11: Field Survey module, sub-module 4 — Photo Uploads.
-- Evidence photos attached to a survey result, same upload pattern as resident_documents.
CREATE TABLE IF NOT EXISTS `field_survey_photos` (
  `photo_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `result_id` INT UNSIGNED NOT NULL,
  `caption` VARCHAR(255) NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`photo_id`),
  KEY `idx_photos_result` (`result_id`),
  CONSTRAINT `fk_photos_result` FOREIGN KEY (`result_id`) REFERENCES `field_survey_results` (`result_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
