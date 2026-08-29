-- Adds brute-force lockout tracking to citizen_accounts. Separate from the existing
-- `status` column, which tracks Active/Locked for the staff-invite pending-password state --
-- a different concept from a temporary security lockout after repeated failed login attempts.

ALTER TABLE `citizen_accounts`
  ADD COLUMN `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `password_hash`,
  ADD COLUMN `locked_until` DATETIME NULL DEFAULT NULL AFTER `failed_login_attempts`;
