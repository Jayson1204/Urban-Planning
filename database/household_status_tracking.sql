-- Households follow-up: promotes households to a fully managed record (list, edit, archive)
-- instead of only being creatable inline through the resident form's household picker.

ALTER TABLE `households`
  ADD COLUMN `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active' AFTER `household_type`;

ALTER TABLE `households`
  ADD KEY `idx_households_status` (`status`);
