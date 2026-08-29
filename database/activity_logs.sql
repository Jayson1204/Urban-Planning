-- Phase 9: Local Activity Log.
-- Records create/update/archive/restore events from this capstone's own local-DB
-- modules (Residents, Housing, Urban Planning, Field Survey, Beneficiary Documents).
-- The existing Audit Trail (pages/audit/*) only proxies to the remote civentral.tech
-- backend and has no visibility into these local writes, so this table fills that gap.
-- No FK on user_id: local `users` rows are only synced in on login, and a log entry
-- must still be recorded even for an actor not yet present locally.

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `actor_name` VARCHAR(150) NOT NULL,
  `module` VARCHAR(100) NOT NULL,
  `action` ENUM('Create','Update','Archive','Restore','Delete','Approve','Reject') NOT NULL,
  `target_table` VARCHAR(100) NOT NULL,
  `target_id` VARCHAR(50) NULL,
  `target_label` VARCHAR(255) NULL,
  `description` VARCHAR(500) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_activity_module` (`module`),
  KEY `idx_activity_action` (`action`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
