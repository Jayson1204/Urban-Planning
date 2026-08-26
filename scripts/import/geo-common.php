<?php

// Shared helpers for the OSM import scripts. Not autoloaded elsewhere —
// require_once directly from a CLI script.

/**
 * Fetches an Overpass QL query, trying a couple of public mirrors since a
 * single instance can be slow/overloaded. Returns the decoded JSON body.
 */
function overpassQuery(string $query): array
{
    $mirrors = [
        'https://overpass-api.de/api/interpreter',
        'https://lz4.overpass-api.de/api/interpreter',
        'https://overpass.kumi.systems/api/interpreter',
    ];

    $lastError = null;
    foreach ($mirrors as $endpoint) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['data' => $query]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Accept: */*'],
            CURLOPT_USERAGENT => 'Civentral-UrbanPlanning-Import/1.0 (+https://github.com)',
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body !== false && $httpCode === 200) {
            $decoded = json_decode($body, true);
            if (is_array($decoded) && isset($decoded['elements'])) {
                return $decoded;
            }
            $lastError = "Unexpected response from {$endpoint} (HTTP {$httpCode})";
        } else {
            $lastError = "Request to {$endpoint} failed (HTTP {$httpCode}): {$error}";
        }
        fwrite(STDERR, "  ! {$lastError} — trying next mirror if any.\n");
    }

    throw new RuntimeException("All Overpass mirrors failed. Last error: {$lastError}");
}

/**
 * Loads the existing Caloocan barangay boundaries and returns
 * [barangayId => ['bbox' => [minLng,minLat,maxLng,maxLat], 'polygons' => [...] ]]
 * plus the overall city bbox, keyed by psgc_code matched against the `barangays` table.
 */
function loadBarangayPolygons(PDO $pdo, string $geojsonPath): array
{
    $data = json_decode(file_get_contents($geojsonPath), true);
    if (!$data || empty($data['features'])) {
        throw new RuntimeException("Could not read barangay GeoJSON at {$geojsonPath}");
    }

    $idByPsgc = [];
    $stmt = $pdo->query("SELECT barangay_id, psgc_code FROM barangays");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $idByPsgc[$row['psgc_code']] = (int)$row['barangay_id'];
    }

    $barangays = [];
    $cityMinLat = 90.0; $cityMaxLat = -90.0; $cityMinLng = 180.0; $cityMaxLng = -180.0;

    foreach ($data['features'] as $feature) {
        $psgc = $feature['properties']['psgc_code'] ?? null;
        $barangayId = $idByPsgc[$psgc] ?? null;
        if (!$barangayId) {
            continue;
        }

        // Normalize to a list of polygons, each a list of rings, each a list of [lng,lat].
        $geomType = $feature['geometry']['type'];
        $polygons = $geomType === 'MultiPolygon' ? $feature['geometry']['coordinates'] : [$feature['geometry']['coordinates']];

        $minLat = 90.0; $maxLat = -90.0; $minLng = 180.0; $maxLng = -180.0;
        foreach ($polygons as $poly) {
            foreach ($poly[0] as [$lng, $lat]) {
                $minLat = min($minLat, $lat); $maxLat = max($maxLat, $lat);
                $minLng = min($minLng, $lng); $maxLng = max($maxLng, $lng);
            }
        }

        $barangays[$barangayId] = [
            'bbox' => [$minLng, $minLat, $maxLng, $maxLat],
            'polygons' => $polygons,
        ];

        $cityMinLat = min($cityMinLat, $minLat); $cityMaxLat = max($cityMaxLat, $maxLat);
        $cityMinLng = min($cityMinLng, $minLng); $cityMaxLng = max($cityMaxLng, $maxLng);
    }

    return [
        'barangays' => $barangays,
        'city_bbox' => [$cityMinLng, $cityMinLat, $cityMaxLng, $cityMaxLat],
    ];
}

/** Even-odd ray casting against a single ring (array of [lng,lat]). */
function pointInRing(float $lng, float $lat, array $ring): bool
{
    $inside = false;
    $n = count($ring);
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        [$xi, $yi] = $ring[$i];
        [$xj, $yj] = $ring[$j];
        $intersects = (($yi > $lat) !== ($yj > $lat))
            && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);
        if ($intersects) {
            $inside = !$inside;
        }
    }
    return $inside;
}

/** Point-in-polygon across all rings of one polygon (outer ring + holes via even-odd rule). */
function pointInPolygon(float $lng, float $lat, array $polygon): bool
{
    $inside = false;
    foreach ($polygon as $ring) {
        if (pointInRing($lng, $lat, $ring)) {
            $inside = !$inside;
        }
    }
    return $inside;
}

/**
 * Finds which barangay (from loadBarangayPolygons()) contains the given point,
 * using each barangay's bbox as a cheap pre-filter before exact point-in-polygon.
 */
function findBarangayForPoint(float $lng, float $lat, array $barangays): ?int
{
    foreach ($barangays as $barangayId => $entry) {
        [$minLng, $minLat, $maxLng, $maxLat] = $entry['bbox'];
        if ($lng < $minLng || $lng > $maxLng || $lat < $minLat || $lat > $maxLat) {
            continue;
        }
        foreach ($entry['polygons'] as $polygon) {
            if (pointInPolygon($lng, $lat, $polygon)) {
                return $barangayId;
            }
        }
    }
    return null;
}
