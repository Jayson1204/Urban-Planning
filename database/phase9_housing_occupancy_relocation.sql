-- Phase 4 follow-up: Occupancy and Relocation Records.
-- Occupancy is decoupled from the beneficiary applicant/award lifecycle so a unit's
-- occupant history can span multiple stays and survive relocations. Relocating a
-- resident ends their occupancy on the source unit and starts a new one on the
-- destination unit; both actions plus the relocation itself feed the per-unit
-- Housing History timeline (computed on read, not a separate log table).

CREATE TABLE IF NOT EXISTS `housing_occupancy` (
  `occupancy_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `unit_id` INT UNSIGNED NOT NULL,
  `resident_id` INT UNSIGNED NOT NULL,
  `beneficiary_id` INT UNSIGNED NULL,
  `move_in_date` DATE NOT NULL,
  `move_out_date` DATE NULL,
  `status` ENUM('Active','Ended') NOT NULL DEFAULT 'Active',
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`occupancy_id`),
  KEY `idx_occupancy_unit` (`unit_id`),
  KEY `idx_occupancy_resident` (`resident_id`),
  KEY `idx_occupancy_status` (`status`),
  CONSTRAINT `fk_occupancy_unit` FOREIGN KEY (`unit_id`) REFERENCES `housing_units` (`unit_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_occupancy_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_occupancy_beneficiary` FOREIGN KEY (`beneficiary_id`) REFERENCES `housing_beneficiaries` (`beneficiary_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `housing_relocations` (
  `relocation_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resident_id` INT UNSIGNED NOT NULL,
  `from_unit_id` INT UNSIGNED NULL,
  `to_unit_id` INT UNSIGNED NOT NULL,
  `relocation_date` DATE NOT NULL,
  `reason` ENUM('Overcrowding','Safety Issue','Unit Upgrade','Personal Request','Government Directive','Other') NOT NULL DEFAULT 'Other',
  `remarks` TEXT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`relocation_id`),
  KEY `idx_relocation_resident` (`resident_id`),
  KEY `idx_relocation_from` (`from_unit_id`),
  KEY `idx_relocation_to` (`to_unit_id`),
  KEY `idx_relocation_status` (`status`),
  CONSTRAINT `fk_relocation_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_relocation_from_unit` FOREIGN KEY (`from_unit_id`) REFERENCES `housing_units` (`unit_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_relocation_to_unit` FOREIGN KEY (`to_unit_id`) REFERENCES `housing_units` (`unit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
