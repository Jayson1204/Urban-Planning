-- Phase 17: Subdivision and Building Review.
-- Module 5.x - Permit application intake (subdivision plats and building permits,
-- modeled as one application type field rather than two separate tables, per the
-- gap-analysis doc's own guidance), plan document versioning, multi-discipline
-- review routing with a consolidated evaluation summary, resubmission tracking,
-- and permit issuance with conditions of approval.
-- No parcel/GIS layer exists yet, so barangay/street_address are self-declared
-- rather than derived from a real parcel map (same simplification as
-- zoning_clearances.zone_classification; see phase15).

CREATE TABLE IF NOT EXISTS `permit_applications` (
  `application_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resident_id` INT UNSIGNED NOT NULL,
  `application_type` ENUM('Subdivision Plan','Building Permit') NOT NULL,
  `reference_number` VARCHAR(30) NOT NULL,
  `project_name` VARCHAR(150) NULL,
  `project_description` TEXT NULL,
  `barangay` VARCHAR(100) NULL,
  `street_address` VARCHAR(255) NULL,
  `lot_area_sqm` DECIMAL(10,2) NULL,
  `floor_area_sqm` DECIMAL(10,2) NULL,
  `number_of_storeys` SMALLINT UNSIGNED NULL,
  `number_of_lots` SMALLINT UNSIGNED NULL,
  `estimated_project_cost` DECIMAL(14,2) NULL,
  `consolidated_result` ENUM('Pending','Under Review','Returned for Revision','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `application_status` ENUM('Submitted','Under Review','Returned for Revision','Approved','Permit Issued','Denied','Cancelled') NOT NULL DEFAULT 'Submitted',
  `resubmission_round` INT UNSIGNED NOT NULL DEFAULT 0,
  `fee_amount` DECIMAL(10,2) NULL,
  `payment_status` ENUM('Unpaid','Paid','Waived') NOT NULL DEFAULT 'Unpaid',
  `permit_number` VARCHAR(30) NULL,
  `conditions_of_approval` TEXT NULL,
  `issued_date` DATE NULL,
  `expiry_date` DATE NULL,
  `application_date` DATE NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `uniq_pa_reference_number` (`reference_number`),
  UNIQUE KEY `uniq_pa_permit_number` (`permit_number`),
  KEY `idx_pa_resident` (`resident_id`),
  KEY `idx_pa_type` (`application_type`),
  KEY `idx_pa_status` (`application_status`),
  KEY `idx_pa_record_status` (`status`),
  CONSTRAINT `fk_pa_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per discipline per application (multi-discipline review routing).
-- Rows are created automatically on submission for all five disciplines and
-- updated in place as each discipline's reviewer records a decision; the
-- resubmission_round column records which round the current status is from.
CREATE TABLE IF NOT EXISTS `permit_discipline_reviews` (
  `discipline_review_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `discipline` ENUM('Architectural','Structural','Sanitary','Electrical','Fire Safety') NOT NULL,
  `review_status` ENUM('Pending','Under Review','Returned for Revision','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `reviewer_name` VARCHAR(150) NULL,
  `remarks` TEXT NULL,
  `resubmission_round` INT UNSIGNED NOT NULL DEFAULT 0,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`discipline_review_id`),
  UNIQUE KEY `uniq_pdr_application_discipline` (`application_id`, `discipline`),
  CONSTRAINT `fk_pdr_application` FOREIGN KEY (`application_id`) REFERENCES `permit_applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only audit trail and comment thread, same precedent as
-- zoning_clearance_reviews (phase15): a permit record needs a real per-stage
-- audit trail. discipline is NULL for top-level application actions and set
-- to a discipline name for a threaded review comment on that discipline.
CREATE TABLE IF NOT EXISTS `permit_application_reviews` (
  `review_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `discipline` VARCHAR(50) NULL,
  `resubmission_round` INT UNSIGNED NOT NULL DEFAULT 0,
  `reviewer_name` VARCHAR(150) NULL,
  `reviewer_role` VARCHAR(100) NULL,
  `action` VARCHAR(50) NOT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `idx_par_application` (`application_id`),
  CONSTRAINT `fk_par_application` FOREIGN KEY (`application_id`) REFERENCES `permit_applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Versioned plan documents (site development / building / structural /
-- sanitary / electrical / fire safety plans, subdivision plats). A new
-- upload of the same document_type bumps version_number and marks the prior
-- row Superseded rather than deleting it, so reviewers can see history.
CREATE TABLE IF NOT EXISTS `permit_plan_documents` (
  `document_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED NOT NULL,
  `document_type` ENUM('Site Development Plan','Building Plan','Structural Plan','Sanitary/Plumbing Plan','Electrical Plan','Fire Safety Plan','Subdivision Plat','Other') NOT NULL DEFAULT 'Other',
  `version_number` INT UNSIGNED NOT NULL DEFAULT 1,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NULL,
  `submitted_by` ENUM('Applicant','Staff') NOT NULL DEFAULT 'Staff',
  `resubmission_round` INT UNSIGNED NOT NULL DEFAULT 0,
  `document_status` ENUM('Current','Superseded','Archived') NOT NULL DEFAULT 'Current',
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  KEY `idx_ppd_application_type` (`application_id`, `document_type`),
  CONSTRAINT `fk_ppd_application` FOREIGN KEY (`application_id`) REFERENCES `permit_applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
