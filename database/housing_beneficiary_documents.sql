-- Beneficiary Application Documents (Housing Management extension).
-- Supporting documents for a housing beneficiary application (valid ID, proof of
-- income, barangay certificate, etc.). Citizens submit these themselves via the
-- future mobile app (submitted_by = 'Citizen'); until that exists, staff can enter
-- them on an applicant's behalf (submitted_by = 'Staff') so the review/approve
-- workflow below is fully usable today. Staff always perform the review step.

CREATE TABLE IF NOT EXISTS `housing_beneficiary_documents` (
  `document_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `beneficiary_id` INT UNSIGNED NOT NULL,
  `document_type` ENUM('Valid ID','Proof of Income','Barangay Certificate','Certificate of Indigency','Proof of Residency','Other') NOT NULL DEFAULT 'Other',
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NULL,
  `submitted_by` ENUM('Citizen','Staff') NOT NULL DEFAULT 'Staff',
  `review_status` ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `review_notes` TEXT NULL,
  `reviewed_by_name` VARCHAR(150) NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  KEY `idx_bendocs_beneficiary` (`beneficiary_id`),
  KEY `idx_bendocs_review_status` (`review_status`),
  CONSTRAINT `fk_bendocs_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `housing_beneficiaries` (`beneficiary_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
