-- Phase 5 follow-up: Urban Projects, Infrastructure Records, and Planning Documents.
-- Urban Projects and Infrastructure Records optionally FK to a development plan.
-- Planning Documents FK directly to a plan and are managed inline from the plan's
-- view modal (mirrors resident_documents), not as a standalone module page.

CREATE TABLE IF NOT EXISTS `urban_projects` (
  `project_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NULL,
  `project_code` VARCHAR(50) NOT NULL,
  `project_title` VARCHAR(150) NOT NULL,
  `project_type` ENUM('Road','Drainage','Public Building','Utility','Park/Open Space','Other') NOT NULL DEFAULT 'Other',
  `barangay` VARCHAR(100) NULL,
  `coverage_area` VARCHAR(255) NULL,
  `contractor` VARCHAR(150) NULL,
  `budget` DECIMAL(14,2) NULL,
  `start_date` DATE NULL,
  `target_completion_date` DATE NULL,
  `actual_completion_date` DATE NULL,
  `project_status` ENUM('Planned','Ongoing','Completed','Delayed','Cancelled') NOT NULL DEFAULT 'Planned',
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `uniq_project_code` (`project_code`),
  KEY `idx_project_plan` (`plan_id`),
  KEY `idx_project_barangay` (`barangay`),
  KEY `idx_project_status` (`project_status`),
  KEY `idx_project_record_status` (`status`),
  CONSTRAINT `fk_project_plan` FOREIGN KEY (`plan_id`) REFERENCES `development_plans` (`plan_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `infrastructure_records` (
  `record_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NULL,
  `infrastructure_type` ENUM('Road','Bridge','Drainage System','Water Supply','Street Lighting','Public Facility','Other') NOT NULL DEFAULT 'Other',
  `infrastructure_name` VARCHAR(150) NOT NULL,
  `barangay` VARCHAR(100) NULL,
  `location_details` VARCHAR(255) NULL,
  `condition_status` ENUM('Good','Needs Repair','Under Construction','Non-Functional') NOT NULL DEFAULT 'Good',
  `completion_date` DATE NULL,
  `remarks` TEXT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`record_id`),
  KEY `idx_infra_project` (`project_id`),
  KEY `idx_infra_barangay` (`barangay`),
  KEY `idx_infra_condition` (`condition_status`),
  KEY `idx_infra_status` (`status`),
  CONSTRAINT `fk_infra_project` FOREIGN KEY (`project_id`) REFERENCES `urban_projects` (`project_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `planning_documents` (
  `document_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_id` INT UNSIGNED NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`document_id`),
  KEY `idx_plandocs_plan` (`plan_id`),
  CONSTRAINT `fk_plandocs_plan` FOREIGN KEY (`plan_id`) REFERENCES `development_plans` (`plan_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
