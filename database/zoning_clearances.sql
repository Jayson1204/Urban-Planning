-- Zoning Clearance System.
-- Module 5.x - Online application intake, rule-based conformity pre-screening,
-- multi-level review routing, and fee/payment tracking for zoning clearances.
-- No parcel/GIS layer exists yet, so zone_classification is applicant/staff-declared
-- rather than derived from a real zoning map (matches development_plans' barangay/
-- coverage_area free-text simplification).

-- Reference data: permitted/conditional/prohibited use per zone, plus the numeric
-- limits used for automated conformity pre-screening. Seeded below; there is no
-- admin CRUD UI for this table in v1, edit via SQL/DB tools directly for now.
CREATE TABLE IF NOT EXISTS `zoning_use_regulations` (
  `regulation_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `zone_classification` VARCHAR(50) NOT NULL,
  `use_category` VARCHAR(50) NOT NULL,
  `conformity` ENUM('Permitted','Conditional','Prohibited') NOT NULL DEFAULT 'Conditional',
  `max_height_m` DECIMAL(6,2) NULL,
  `min_setback_m` DECIMAL(6,2) NULL,
  `max_far` DECIMAL(4,2) NULL,
  `max_lot_occupancy_pct` DECIMAL(5,2) NULL,
  `reference_note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`regulation_id`),
  UNIQUE KEY `uniq_zone_use` (`zone_classification`, `use_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `zoning_clearances` (
  `clearance_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resident_id` INT UNSIGNED NOT NULL,
  `reference_number` VARCHAR(30) NOT NULL,
  `zone_classification` VARCHAR(50) NOT NULL,
  `use_category` VARCHAR(50) NOT NULL,
  `project_description` TEXT NULL,
  `barangay` VARCHAR(100) NULL,
  `street_address` VARCHAR(255) NULL,
  `lot_area_sqm` DECIMAL(10,2) NULL,
  `proposed_height_m` DECIMAL(6,2) NULL,
  `proposed_setback_m` DECIMAL(6,2) NULL,
  `proposed_far` DECIMAL(4,2) NULL,
  `proposed_lot_occupancy_pct` DECIMAL(5,2) NULL,
  `conformity_result` ENUM('Conforming','Non-Conforming','Needs Manual Review') NULL,
  `conformity_notes` TEXT NULL,
  `clearance_status` ENUM('Submitted','Under Review','Returned for Revision','Approved','Denied','Cancelled') NOT NULL DEFAULT 'Submitted',
  `fee_amount` DECIMAL(10,2) NULL,
  `payment_status` ENUM('Unpaid','Paid','Waived') NOT NULL DEFAULT 'Unpaid',
  `verification_code` VARCHAR(20) NULL,
  `application_date` DATE NULL,
  `approved_date` DATE NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clearance_id`),
  UNIQUE KEY `uniq_reference_number` (`reference_number`),
  KEY `idx_zc_resident` (`resident_id`),
  KEY `idx_zc_status` (`clearance_status`),
  KEY `idx_zc_record_status` (`status`),
  CONSTRAINT `fk_zc_resident` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only routing/audit log. A deliberate exception to this codebase's usual
-- "compute history on read" convention (see field_survey_assignments), justified
-- because a legal clearance needs a real per-stage audit trail. reviewer_role is
-- free text rather than a fixed-stage enum, so routing stays flexible.
CREATE TABLE IF NOT EXISTS `zoning_clearance_reviews` (
  `review_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clearance_id` INT UNSIGNED NOT NULL,
  `reviewer_name` VARCHAR(150) NULL,
  `reviewer_role` VARCHAR(100) NULL,
  `action` VARCHAR(50) NOT NULL,
  `remarks` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `idx_zcr_clearance` (`clearance_id`),
  CONSTRAINT `fk_zcr_clearance` FOREIGN KEY (`clearance_id`) REFERENCES `zoning_clearances` (`clearance_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed regulation matrix: 8 zone classifications x 7 use categories with
-- illustrative Philippine-LGU-style limits. Staff can adjust via SQL/DB tools.
INSERT INTO `zoning_use_regulations`
  (`zone_classification`, `use_category`, `conformity`, `max_height_m`, `min_setback_m`, `max_far`, `max_lot_occupancy_pct`, `reference_note`)
VALUES
  ('Residential-1', 'Residential Dwelling', 'Permitted', 10.00, 2.00, 1.50, 60.00, 'Sec. 4.1 Residential-1 Density Standards'),
  ('Residential-1', 'Home Occupation', 'Conditional', 10.00, 2.00, 1.50, 60.00, 'Sec. 4.1.3 Home Occupation Allowance'),
  ('Residential-1', 'Commercial Establishment', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.1.5 Prohibited Uses in Residential-1'),
  ('Residential-1', 'Light Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.1.5 Prohibited Uses in Residential-1'),
  ('Residential-1', 'Heavy Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.1.5 Prohibited Uses in Residential-1'),
  ('Residential-1', 'Institutional', 'Conditional', 12.00, 3.00, 1.50, 50.00, 'Sec. 4.1.4 Institutional Use Allowance'),
  ('Residential-1', 'Agricultural', 'Conditional', 8.00, 2.00, 0.50, 40.00, 'Sec. 4.1.4 Institutional Use Allowance'),

  ('Residential-2', 'Residential Dwelling', 'Permitted', 15.00, 2.00, 2.00, 65.00, 'Sec. 4.2 Residential-2 Density Standards'),
  ('Residential-2', 'Home Occupation', 'Permitted', 15.00, 2.00, 2.00, 65.00, 'Sec. 4.2.3 Home Occupation Allowance'),
  ('Residential-2', 'Commercial Establishment', 'Conditional', 15.00, 3.00, 2.00, 65.00, 'Sec. 4.2.5 Limited Commercial Allowance'),
  ('Residential-2', 'Light Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.2.6 Prohibited Uses in Residential-2'),
  ('Residential-2', 'Heavy Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.2.6 Prohibited Uses in Residential-2'),
  ('Residential-2', 'Institutional', 'Conditional', 15.00, 3.00, 2.00, 55.00, 'Sec. 4.2.4 Institutional Use Allowance'),
  ('Residential-2', 'Agricultural', 'Conditional', 8.00, 2.00, 0.50, 40.00, 'Sec. 4.2.4 Institutional Use Allowance'),

  ('Commercial-1', 'Residential Dwelling', 'Conditional', 20.00, 2.00, 2.50, 70.00, 'Sec. 4.3.3 Mixed-Use Residential Allowance'),
  ('Commercial-1', 'Home Occupation', 'Permitted', 20.00, 2.00, 2.50, 70.00, 'Sec. 4.3 Commercial-1 Standards'),
  ('Commercial-1', 'Commercial Establishment', 'Permitted', 20.00, 2.00, 2.50, 70.00, 'Sec. 4.3 Commercial-1 Standards'),
  ('Commercial-1', 'Light Industrial', 'Conditional', 20.00, 3.00, 2.50, 70.00, 'Sec. 4.3.5 Light Industrial Allowance'),
  ('Commercial-1', 'Heavy Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.3.6 Prohibited Uses in Commercial-1'),
  ('Commercial-1', 'Institutional', 'Permitted', 20.00, 2.00, 2.50, 70.00, 'Sec. 4.3 Commercial-1 Standards'),
  ('Commercial-1', 'Agricultural', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.3.6 Prohibited Uses in Commercial-1'),

  ('Commercial-2', 'Residential Dwelling', 'Conditional', 30.00, 3.00, 3.50, 75.00, 'Sec. 4.4.3 Mixed-Use Residential Allowance'),
  ('Commercial-2', 'Home Occupation', 'Permitted', 30.00, 3.00, 3.50, 75.00, 'Sec. 4.4 Commercial-2 Standards'),
  ('Commercial-2', 'Commercial Establishment', 'Permitted', 30.00, 3.00, 3.50, 75.00, 'Sec. 4.4 Commercial-2 Standards'),
  ('Commercial-2', 'Light Industrial', 'Permitted', 30.00, 3.00, 3.50, 75.00, 'Sec. 4.4 Commercial-2 Standards'),
  ('Commercial-2', 'Heavy Industrial', 'Conditional', 30.00, 5.00, 3.50, 75.00, 'Sec. 4.4.6 Heavy Industrial Allowance'),
  ('Commercial-2', 'Institutional', 'Permitted', 30.00, 3.00, 3.50, 75.00, 'Sec. 4.4 Commercial-2 Standards'),
  ('Commercial-2', 'Agricultural', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.4.7 Prohibited Uses in Commercial-2'),

  ('Institutional', 'Residential Dwelling', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.5.5 Prohibited Uses in Institutional'),
  ('Institutional', 'Home Occupation', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.5.5 Prohibited Uses in Institutional'),
  ('Institutional', 'Commercial Establishment', 'Conditional', 20.00, 3.00, 2.00, 60.00, 'Sec. 4.5.4 Ancillary Commercial Allowance'),
  ('Institutional', 'Light Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.5.5 Prohibited Uses in Institutional'),
  ('Institutional', 'Heavy Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.5.5 Prohibited Uses in Institutional'),
  ('Institutional', 'Institutional', 'Permitted', 20.00, 3.00, 2.00, 60.00, 'Sec. 4.5 Institutional Zone Standards'),
  ('Institutional', 'Agricultural', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.5.5 Prohibited Uses in Institutional'),

  ('Industrial', 'Residential Dwelling', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.6.5 Prohibited Uses in Industrial'),
  ('Industrial', 'Home Occupation', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.6.5 Prohibited Uses in Industrial'),
  ('Industrial', 'Commercial Establishment', 'Conditional', 20.00, 5.00, 2.00, 70.00, 'Sec. 4.6.4 Ancillary Commercial Allowance'),
  ('Industrial', 'Light Industrial', 'Permitted', 25.00, 5.00, 3.00, 75.00, 'Sec. 4.6 Industrial Zone Standards'),
  ('Industrial', 'Heavy Industrial', 'Permitted', 25.00, 8.00, 3.00, 75.00, 'Sec. 4.6 Industrial Zone Standards'),
  ('Industrial', 'Institutional', 'Conditional', 20.00, 5.00, 2.00, 60.00, 'Sec. 4.6.4 Ancillary Institutional Allowance'),
  ('Industrial', 'Agricultural', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.6.5 Prohibited Uses in Industrial'),

  ('Agricultural', 'Residential Dwelling', 'Conditional', 8.00, 3.00, 0.50, 30.00, 'Sec. 4.7.4 Farm Dwelling Allowance'),
  ('Agricultural', 'Home Occupation', 'Conditional', 8.00, 3.00, 0.50, 30.00, 'Sec. 4.7.4 Farm Dwelling Allowance'),
  ('Agricultural', 'Commercial Establishment', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.7.5 Prohibited Uses in Agricultural'),
  ('Agricultural', 'Light Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.7.5 Prohibited Uses in Agricultural'),
  ('Agricultural', 'Heavy Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.7.5 Prohibited Uses in Agricultural'),
  ('Agricultural', 'Institutional', 'Conditional', 10.00, 3.00, 0.50, 30.00, 'Sec. 4.7.4 Ancillary Institutional Allowance'),
  ('Agricultural', 'Agricultural', 'Permitted', 8.00, 2.00, 0.30, 20.00, 'Sec. 4.7 Agricultural Zone Standards'),

  ('Open Space', 'Residential Dwelling', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.8.5 Prohibited Uses in Open Space'),
  ('Open Space', 'Home Occupation', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.8.5 Prohibited Uses in Open Space'),
  ('Open Space', 'Commercial Establishment', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.8.5 Prohibited Uses in Open Space'),
  ('Open Space', 'Light Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.8.5 Prohibited Uses in Open Space'),
  ('Open Space', 'Heavy Industrial', 'Prohibited', NULL, NULL, NULL, NULL, 'Sec. 4.8.5 Prohibited Uses in Open Space'),
  ('Open Space', 'Institutional', 'Conditional', 6.00, 5.00, 0.20, 15.00, 'Sec. 4.8.4 Park Amenity Structure Allowance'),
  ('Open Space', 'Agricultural', 'Conditional', 5.00, 3.00, 0.20, 15.00, 'Sec. 4.8.4 Community Garden Allowance');
