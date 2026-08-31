-- AI Planning Assistant usage/cost log.
-- Records one row per Gemini API call (prompt + thinking + output token counts from the
-- API's own usageMetadata, plus a locally estimated USD cost) so token/cost usage can be
-- monitored without relying solely on Google's own billing dashboard.
-- No FK on user_id: local `users` rows are only synced in on login, matching activity_logs.sql.

CREATE TABLE IF NOT EXISTS `ai_usage_logs` (
  `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `model` VARCHAR(100) NOT NULL,
  `prompt_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
  `thoughts_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
  `candidates_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_tokens` INT UNSIGNED NOT NULL DEFAULT 0,
  `estimated_cost_usd` DECIMAL(10,6) NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_ai_usage_model` (`model`),
  KEY `idx_ai_usage_user` (`user_id`),
  KEY `idx_ai_usage_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
