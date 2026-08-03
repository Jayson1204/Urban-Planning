-- Local, role-based access control for the new capstone modules (Resident Management,
-- Urban Planning, and future Housing/Field Survey/Reports/AI phases).
-- Kept separate from production's shared roles/permissions system since that system
-- has no knowledge of these new modules. `role_id` refers to production's role_id
-- (no local FK possible, since `roles` itself lives on production).

CREATE TABLE IF NOT EXISTS `capstone_module_permissions` (
  `permission_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `module_slug` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `uniq_role_module` (`role_id`, `module_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
