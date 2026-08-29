-- Seed data: real, named NHA housing/resettlement projects in Caloocan
-- City, found via web research (2026-08-19) since no bulk dataset exists for
-- this layer (OSM has no reliable tagging for "housing project"). Each row
-- cites its source; fields not confirmed by that source are left NULL so the
-- UI shows "Data unavailable" rather than a guessed value. Location is the
-- confirmed barangay's centroid (already seeded in `barangays` in phase16) —
-- a barangay-level approximation, not a precise project-gate coordinate,
-- since no exact parcel coordinate was found for these.

INSERT INTO `housing_projects`
  (`name`, `barangay_id`, `barangay`, `latitude`, `longitude`, `units`, `project_status`, `developer`, `source`, `description`, `status`)
SELECT 'Deparo Residences', b.barangay_id, b.name, b.centroid_lat, b.centroid_lng,
       648, 'Ongoing', 'National Housing Authority (NHA)',
       'National Housing Authority (nha.gov.ph) - Expanded Pambansang Pabahay Para sa Pilipino (4PH) Program',
       'NHA flagship 4PH mid-rise housing project: 12 buildings, 648 residential units and 72 commercial units per NHA; a Manila Standard report separately cites 25 units already awarded to beneficiaries. Location approximated to Barangay 168 (Deparo) centroid, not a precise site coordinate.',
       'Active'
FROM barangays b WHERE b.psgc_code = '1380100168'
UNION ALL
SELECT 'Bagumbong Residences', b.barangay_id, b.name, b.centroid_lat, b.centroid_lng,
       480, 'Ongoing', 'National Housing Authority (NHA)',
       'National Housing Authority (nha.gov.ph)',
       'NHA housing project expected to house approximately 480 families. Location approximated to Barangay 171 (Bagumbong) centroid, not a precise site coordinate.',
       'Active'
FROM barangays b WHERE b.psgc_code = '1380100171'
UNION ALL
SELECT 'Bagong Silang Housing Project', b.barangay_id, b.name, b.centroid_lat, b.centroid_lng,
       300, 'Ongoing', 'National Housing Authority (NHA)',
       'National Housing Authority (nha.gov.ph)',
       'NHA housing project expected to deliver approximately 300 housing units. Location approximated to Barangay 176 (Bagong Silang) centroid, not a precise site coordinate.',
       'Active'
FROM barangays b WHERE b.psgc_code = '1380100176'
UNION ALL
SELECT 'Tala Resettlement Project', b.barangay_id, b.name, b.centroid_lat, b.centroid_lng,
       NULL, 'Existing', 'National Housing Authority (NHA)',
       'National Housing Authority (nha.gov.ph) - Tala Development Project records',
       'Long-established NHA resettlement site in the Tala area; beneficiary families have been receiving Transfer Certificates of Title. Unit count not confirmed by an available source. Location approximated to Barangay 186 (Tala) centroid, not a precise site coordinate.',
       'Active'
FROM barangays b WHERE b.psgc_code = '1380100186'
UNION ALL
SELECT 'Camarin Residences 3', b.barangay_id, b.name, b.centroid_lat, b.centroid_lng,
       1200, NULL, 'National Housing Authority (NHA)',
       'National Housing Authority (nha.gov.ph)',
       'NHA housing project reported as 10 buildings with 120 units each (1,200 units). Current project status not confirmed by an available source. Location approximated to Barangay 177 (Camarin) centroid, not a precise site coordinate.',
       'Active'
FROM barangays b WHERE b.psgc_code = '1380100177';
