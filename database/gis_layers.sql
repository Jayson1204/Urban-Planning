-- GIS layers on top of the barangay map (subdivisions,
-- housing projects, building footprints). Does not touch `barangays` or the
-- boundary GeoJSON. All three tables carry a real `barangay_id` FK (unlike
-- the legacy name-joined `housing_units.barangay`), computed at import time
-- via point-in-polygon against assets/geojson/caloocan-barangays.geojson for
-- bulk-imported rows, or picked from a dropdown for staff-entered rows.
-- No resident/household columns exist on any of these tables by design —
-- these layers show geography only, never personal data.

CREATE TABLE IF NOT EXISTS `subdivisions` (
  `subdivision_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `barangay_id` INT UNSIGNED NULL,
  `barangay` VARCHAR(100) NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `boundary_geojson` JSON NULL,
  `subdivision_type` VARCHAR(100) NULL,
  `subdivision_status` VARCHAR(50) NULL,
  `source` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`subdivision_id`),
  KEY `idx_subdivision_barangay` (`barangay_id`),
  KEY `idx_subdivision_status` (`status`),
  CONSTRAINT `fk_subdivision_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `housing_projects` (
  `housing_project_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `barangay_id` INT UNSIGNED NULL,
  `barangay` VARCHAR(100) NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `boundary_geojson` JSON NULL,
  `units` INT UNSIGNED NULL,
  `project_status` ENUM('Existing','Ongoing','Proposed','Completed') NULL,
  `developer` VARCHAR(150) NULL,
  `source` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`housing_project_id`),
  KEY `idx_housing_project_barangay` (`barangay_id`),
  KEY `idx_housing_project_status` (`status`),
  CONSTRAINT `fk_housing_project_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No MySQL spatial types: bbox pre-filtering uses plain indexed DECIMAL
-- columns (bbox_min_lat/lng, bbox_max_lat/lng), matching the rest of this
-- codebase (no other table uses GEOMETRY/SPATIAL INDEX). Leaflet renders the
-- exact polygon client-side from footprint_geojson; MySQL only needs to do
-- the coarse viewport pre-filter.
CREATE TABLE IF NOT EXISTS `buildings` (
  `building_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_id` VARCHAR(100) NULL,
  `barangay_id` INT UNSIGNED NULL,
  `building_type` VARCHAR(50) NULL,
  `source` VARCHAR(100) NOT NULL DEFAULT 'OpenStreetMap',
  `footprint_geojson` JSON NOT NULL,
  `centroid_lat` DECIMAL(10,8) NOT NULL,
  `centroid_lng` DECIMAL(11,8) NOT NULL,
  `bbox_min_lat` DECIMAL(10,8) NOT NULL,
  `bbox_min_lng` DECIMAL(11,8) NOT NULL,
  `bbox_max_lat` DECIMAL(10,8) NOT NULL,
  `bbox_max_lng` DECIMAL(11,8) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`building_id`),
  UNIQUE KEY `uniq_building_external` (`external_id`),
  KEY `idx_building_barangay` (`barangay_id`),
  KEY `idx_building_bbox` (`bbox_min_lng`, `bbox_max_lng`, `bbox_min_lat`, `bbox_max_lat`),
  CONSTRAINT `fk_building_barangay` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
