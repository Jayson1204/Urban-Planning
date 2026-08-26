<?php

namespace App\Repositories;

class BuildingRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($buildingId)
    {
        $rows = $this->db->query(
            "SELECT bd.*, b.name AS barangay_name FROM buildings bd
             LEFT JOIN barangays b ON bd.barangay_id = b.barangay_id
             WHERE bd.building_id = :id",
            ['id' => $buildingId]
        );
        return $rows[0] ?? null;
    }

    /**
     * Coarse bbox pre-filter via indexed DECIMAL columns (no MySQL spatial
     * types in this codebase). Exact polygon rendering happens client-side.
     */
    public function findInBbox($minLng, $minLat, $maxLng, $maxLat, $limit = 2000)
    {
        return $this->db->query(
            "SELECT building_id, barangay_id, building_type, source, footprint_geojson, centroid_lat, centroid_lng
             FROM buildings
             WHERE bbox_min_lng <= :max_lng AND bbox_max_lng >= :min_lng
               AND bbox_min_lat <= :max_lat AND bbox_max_lat >= :min_lat
             LIMIT " . (int)$limit,
            ['max_lng' => $maxLng, 'min_lng' => $minLng, 'max_lat' => $maxLat, 'min_lat' => $minLat]
        );
    }

    public function countInBbox($minLng, $minLat, $maxLng, $maxLat)
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) AS total FROM buildings
             WHERE bbox_min_lng <= :max_lng AND bbox_max_lng >= :min_lng
               AND bbox_min_lat <= :max_lat AND bbox_max_lat >= :min_lat",
            ['max_lng' => $maxLng, 'min_lng' => $minLng, 'max_lat' => $maxLat, 'min_lat' => $minLat]
        );
        return (int)($rows[0]['total'] ?? 0);
    }

    public function countByBarangay($barangayId)
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) AS total FROM buildings WHERE barangay_id = :id",
            ['id' => $barangayId]
        );
        return (int)($rows[0]['total'] ?? 0);
    }

    public function countAll()
    {
        $rows = $this->db->query("SELECT COUNT(*) AS total FROM buildings");
        return (int)($rows[0]['total'] ?? 0);
    }
}
