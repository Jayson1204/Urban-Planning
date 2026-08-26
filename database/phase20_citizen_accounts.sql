-- Phase 20: Local citizen accounts for the citizen mobile app.
-- Distinct from the production-proxied citizen accounts used by Citizen Management
-- (pages/citizen/citizen-directory.php, api/citizen/*.php) -- those are untouched.
-- Each row links exactly one resident to exactly one login. password_hash is NULL
-- until the citizen sets it: either immediately (self-registration) or via the
-- emailed set-password link (when staff create the resident first).

CREATE TABLE IF NOT EXISTS `citizen_accounts` (
  `citizen_account_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resident_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NULL,
  `status` ENUM('Active','Locked') NOT NULL DEFAULT 'Active',
  `password_set_token` VARCHAR(64) NULL,
  `password_set_token_expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`citizen_account_id`),
  UNIQUE KEY `uniq_citizen_resident` (`resident_id`),
  UNIQUE KEY `uniq_citizen_email` (`email`),
  KEY `idx_citizen_token` (`password_set_token`),
  CONSTRAINT `fk_citizen_account_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
