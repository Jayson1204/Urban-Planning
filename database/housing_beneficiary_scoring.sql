-- Phase 14: Housing Beneficiary Registry follow-up.
-- Adds a server-computed eligibility score (used for waitlist/priority ordering)
-- and an amortization status field for tracking awarded beneficiaries only.

ALTER TABLE `housing_beneficiaries`
  ADD COLUMN `eligibility_score` DECIMAL(5,2) NULL AFTER `family_size`,
  ADD COLUMN `amortization_status` ENUM('Not Started','Current','Delinquent','Completed') NOT NULL DEFAULT 'Not Started' AFTER `award_date`;

ALTER TABLE `housing_beneficiaries`
  ADD KEY `idx_ben_score` (`eligibility_score`);
