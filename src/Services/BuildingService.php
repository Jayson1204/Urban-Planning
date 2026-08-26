<?php

namespace App\Services;

class BuildingService
{
    private $buildingRepo;

    // Caloocan's real extent (from assets/geojson/caloocan-barangays.geojson), padded slightly.
    // A viewport bbox is clamped to this so a stray/huge bbox can't force a citywide scan.
    private const CITY_MIN_LAT = 14.60;
    private const CITY_MAX_LAT = 14.79;
    private const CITY_MIN_LNG = 120.94;
    private const CITY_MAX_LNG = 121.11;

    private const MAX_RESULTS = 2000;
    private const MAX_VIEWPORT_DEGREES = 0.15; // roughly a few km side, generous for a zoom>=15 viewport

    public function __construct($buildingRepo)
    {
        $this->buildingRepo = $buildingRepo;
    }

    /**
     * Parses "minLng,minLat,maxLng,maxLat", clamps to Caloocan's extent and a
     * max viewport size, and returns the buildings + a truncated flag.
     */
    public function buildingsForBbox($bboxParam)
    {
        $parts = array_map('trim', explode(',', (string)$bboxParam));
        if (count($parts) !== 4 || !array_reduce($parts, fn($ok, $p) => $ok && is_numeric($p), true)) {
            return ['error' => 'bbox must be "minLng,minLat,maxLng,maxLat".'];
        }

        [$minLng, $minLat, $maxLng, $maxLat] = array_map('floatval', $parts);
        if ($minLng >= $maxLng || $minLat >= $maxLat) {
            return ['error' => 'bbox min values must be less than max values.'];
        }

        $minLat = max($minLat, self::CITY_MIN_LAT);
        $maxLat = min($maxLat, self::CITY_MAX_LAT);
        $minLng = max($minLng, self::CITY_MIN_LNG);
        $maxLng = min($maxLng, self::CITY_MAX_LNG);

        if (($maxLat - $minLat) > self::MAX_VIEWPORT_DEGREES) {
            $midLat = ($minLat + $maxLat) / 2;
            $minLat = $midLat - self::MAX_VIEWPORT_DEGREES / 2;
            $maxLat = $midLat + self::MAX_VIEWPORT_DEGREES / 2;
        }
        if (($maxLng - $minLng) > self::MAX_VIEWPORT_DEGREES) {
            $midLng = ($minLng + $maxLng) / 2;
            $minLng = $midLng - self::MAX_VIEWPORT_DEGREES / 2;
            $maxLng = $midLng + self::MAX_VIEWPORT_DEGREES / 2;
        }

        $total = $this->buildingRepo->countInBbox($minLng, $minLat, $maxLng, $maxLat);
        $rows = $this->buildingRepo->findInBbox($minLng, $minLat, $maxLng, $maxLat, self::MAX_RESULTS);

        return [
            'rows' => $rows,
            'truncated' => $total > count($rows),
            'total_in_view' => $total,
        ];
    }
}
