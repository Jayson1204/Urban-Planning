<?php

// Imports real building footprints for Caloocan City from OpenStreetMap
// (via the public Overpass API) into the local `buildings` table.
//
// Run: php scripts/import/import-osm-buildings.php
//
// A single citywide Overpass query for a city this dense returns a JSON
// payload too large to hold in memory alongside its decoded PHP structure,
// so this queries Overpass once PER BARANGAY (each barangay's bbox + a small
// buffer), which keeps each response small. A building's owning barangay is
// still resolved by point-in-polygon against ALL 188 barangay polygons (not
// just the one being queried) so a building caught by a neighboring
// barangay's padded bbox is only ever inserted once, in its real owner's
// pass — the loose bbox is just what's fetched, not what's trusted.
//
// Idempotent: deletes prior source='OpenStreetMap' rows before reimporting.
// Relations (multipolygon buildings, a small minority) are not imported —
// only simple ways — to keep the script straightforward.

ini_set('memory_limit', '1024M');
set_time_limit(0);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/geo-common.php';

$geojsonPath = __DIR__ . '/../../assets/geojson/caloocan-barangays.geojson';

echo "Loading Caloocan barangay polygons...\n";
$geo = loadBarangayPolygons($db->getPdo(), $geojsonPath);
$barangays = $geo['barangays'];

$pdo = $db->getPdo();
$pdo->exec("DELETE FROM buildings WHERE source = 'OpenStreetMap'");

$insertSql = "INSERT INTO buildings
    (external_id, barangay_id, building_type, source, footprint_geojson, centroid_lat, centroid_lng, bbox_min_lat, bbox_min_lng, bbox_max_lat, bbox_max_lng)
    VALUES (:external_id, :barangay_id, :building_type, 'OpenStreetMap', :footprint_geojson, :centroid_lat, :centroid_lng, :bbox_min_lat, :bbox_min_lng, :bbox_max_lat, :bbox_max_lng)
    ON DUPLICATE KEY UPDATE barangay_id = VALUES(barangay_id)";
$insertStmt = $pdo->prepare($insertSql);

$namesById = [];
foreach ($pdo->query("SELECT barangay_id, name FROM barangays") as $row) {
    $namesById[$row['barangay_id']] = $row['name'];
}

$buffer = 0.002; // ~200m, enough overlap to catch edge buildings without ballooning response size
$totalInserted = 0;
$totalBarangays = count($barangays);
$i = 0;

foreach ($barangays as $barangayId => $entry) {
    $i++;
    [$minLng, $minLat, $maxLng, $maxLat] = $entry['bbox'];
    $bboxStr = sprintf('%.6f,%.6f,%.6f,%.6f', $minLat - $buffer, $minLng - $buffer, $maxLat + $buffer, $maxLng + $buffer);
    $label = $namesById[$barangayId] ?? "#{$barangayId}";

    echo "[{$i}/{$totalBarangays}] {$label}...";

    try {
        $query = "[out:json][timeout:120];(way[\"building\"]({$bboxStr}););out geom;";
        $result = overpassQuery($query);
    } catch (\Throwable $e) {
        echo " FAILED: " . $e->getMessage() . "\n";
        continue;
    }

    $elements = $result['elements'];
    $insertedHere = 0;

    foreach ($elements as $way) {
        $geometry = $way['geometry'] ?? null;
        if (!$geometry || count($geometry) < 3) {
            continue;
        }

        $ring = [];
        $bMinLat = 90.0; $bMaxLat = -90.0; $bMinLng = 180.0; $bMaxLng = -180.0;
        $sumLat = 0.0; $sumLng = 0.0;
        foreach ($geometry as $node) {
            $lng = (float)$node['lon'];
            $lat = (float)$node['lat'];
            $ring[] = [$lng, $lat];
            $sumLat += $lat; $sumLng += $lng;
            $bMinLat = min($bMinLat, $lat); $bMaxLat = max($bMaxLat, $lat);
            $bMinLng = min($bMinLng, $lng); $bMaxLng = max($bMaxLng, $lng);
        }
        if ($ring[0] !== $ring[count($ring) - 1]) {
            $ring[] = $ring[0];
        }
        $count = count($geometry);
        $centroidLat = $sumLat / $count;
        $centroidLng = $sumLng / $count;

        // Resolve the real owning barangay across all of Caloocan, and only
        // insert here if it's this pass's barangay — avoids double-inserting
        // buildings caught by two overlapping padded bboxes.
        $owningBarangayId = findBarangayForPoint($centroidLng, $centroidLat, $barangays);
        if ($owningBarangayId !== $barangayId) {
            continue;
        }

        $buildingTag = $way['tags']['building'] ?? null;
        $buildingType = ($buildingTag && $buildingTag !== 'yes') ? ucwords(str_replace(['_', '-'], ' ', $buildingTag)) : null;

        $insertStmt->execute([
            'external_id' => 'osm/way/' . $way['id'],
            'barangay_id' => $owningBarangayId,
            'building_type' => $buildingType,
            'footprint_geojson' => json_encode(['type' => 'Polygon', 'coordinates' => [$ring]]),
            'centroid_lat' => $centroidLat,
            'centroid_lng' => $centroidLng,
            'bbox_min_lat' => $bMinLat,
            'bbox_min_lng' => $bMinLng,
            'bbox_max_lat' => $bMaxLat,
            'bbox_max_lng' => $bMaxLng,
        ]);
        $insertedHere++;
    }

    $totalInserted += $insertedHere;
    echo " {$insertedHere} buildings (running total: {$totalInserted})\n";

    unset($result, $elements);
    usleep(300000); // be polite to the free public Overpass endpoint
}

echo "\nDone. Imported {$totalInserted} buildings across {$totalBarangays} barangays.\n";
