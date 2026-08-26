<?php

// Imports real, already-named residential areas for Caloocan City from
// OpenStreetMap into the local `subdivisions` table, as a starting point
// for the Subdivisions map layer (staff can add/correct more via the
// Subdivisions page — see pages/urban-planning/subdivisions.php).
//
// Run: php scripts/import/import-osm-subdivisions.php
//
// Only OSM landuse=residential / place=quarter / place=neighbourhood
// features that already carry a `name` tag are imported — nothing is drawn
// or guessed. A polygon is stored when the source feature has one; the
// pointonly ones still get a location from their OSM centroid.
//
// Idempotent: deletes prior source='OpenStreetMap' rows before reimporting.

ini_set('memory_limit', '512M');
set_time_limit(0);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/geo-common.php';

$geojsonPath = __DIR__ . '/../../assets/geojson/caloocan-barangays.geojson';

echo "Loading Caloocan barangay polygons...\n";
$geo = loadBarangayPolygons($db->getPdo(), $geojsonPath);
$barangays = $geo['barangays'];
[$minLng, $minLat, $maxLng, $maxLat] = $geo['city_bbox'];

$buffer = 0.01;
$bboxStr = sprintf('%.6f,%.6f,%.6f,%.6f', $minLat - $buffer, $minLng - $buffer, $maxLat + $buffer, $maxLng + $buffer);

echo "Querying Overpass for named residential areas in bbox ({$bboxStr})...\n";
$query = "[out:json][timeout:180];(
  way[\"landuse\"=\"residential\"][\"name\"]({$bboxStr});
  way[\"place\"=\"quarter\"][\"name\"]({$bboxStr});
  way[\"place\"=\"neighbourhood\"][\"name\"]({$bboxStr});
  node[\"place\"=\"quarter\"][\"name\"]({$bboxStr});
  node[\"place\"=\"neighbourhood\"][\"name\"]({$bboxStr});
);out geom;";
$result = overpassQuery($query);
$elements = $result['elements'];
echo "Fetched " . count($elements) . " candidate features.\n";

$pdo = $db->getPdo();
$pdo->exec("DELETE FROM subdivisions WHERE source = 'OpenStreetMap'");

$insertStmt = $pdo->prepare(
    "INSERT INTO subdivisions
        (name, barangay_id, barangay, latitude, longitude, boundary_geojson, subdivision_type, source, status)
     VALUES (:name, :barangay_id, :barangay, :latitude, :longitude, :boundary_geojson, :subdivision_type, 'OpenStreetMap', 'Active')"
);

$namesById = [];
foreach ($pdo->query("SELECT barangay_id, name FROM barangays") as $row) {
    $namesById[$row['barangay_id']] = $row['name'];
}

$typeLabels = [
    'residential' => 'Residential Area',
    'quarter' => 'Neighbourhood',
    'neighbourhood' => 'Neighbourhood',
];

$inserted = 0;
$skippedOutsideCity = 0;
$skippedDuplicateName = 0;
$seenNames = [];

foreach ($elements as $el) {
    $name = trim($el['tags']['name'] ?? '');
    if ($name === '') {
        continue;
    }

    if ($el['type'] === 'node') {
        $lat = (float)$el['lat'];
        $lng = (float)$el['lon'];
        $boundary = null;
    } else {
        $geometry = $el['geometry'] ?? null;
        if (!$geometry || count($geometry) < 3) {
            continue;
        }
        $ring = [];
        $sumLat = 0.0; $sumLng = 0.0;
        foreach ($geometry as $node) {
            $ring[] = [(float)$node['lon'], (float)$node['lat']];
            $sumLat += (float)$node['lat'];
            $sumLng += (float)$node['lon'];
        }
        if ($ring[0] !== $ring[count($ring) - 1]) {
            $ring[] = $ring[0];
        }
        $lat = $sumLat / count($geometry);
        $lng = $sumLng / count($geometry);
        $boundary = json_encode(['type' => 'Polygon', 'coordinates' => [$ring]]);
    }

    $barangayId = findBarangayForPoint($lng, $lat, $barangays);
    if (!$barangayId) {
        $skippedOutsideCity++;
        continue;
    }

    // A subdivision spanning several adjoining OSM ways can appear more than
    // once with the same name; keep the first occurrence only.
    $dedupeKey = strtolower($name) . '|' . $barangayId;
    if (isset($seenNames[$dedupeKey])) {
        $skippedDuplicateName++;
        continue;
    }
    $seenNames[$dedupeKey] = true;

    $tagKey = $el['tags']['place'] ?? ($el['tags']['landuse'] ?? null);
    $subdivisionType = $typeLabels[$tagKey] ?? 'Residential Area';

    $insertStmt->execute([
        'name' => $name,
        'barangay_id' => $barangayId,
        'barangay' => $namesById[$barangayId] ?? null,
        'latitude' => $lat,
        'longitude' => $lng,
        'boundary_geojson' => $boundary,
        'subdivision_type' => $subdivisionType,
    ]);
    $inserted++;
}

echo "\nDone.\n";
echo "  Imported:           {$inserted}\n";
echo "  Outside Caloocan:   {$skippedOutsideCity}\n";
echo "  Duplicate names:    {$skippedDuplicateName}\n";
