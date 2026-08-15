<?php

namespace App\Repositories;

class BarangayRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function all($search = '')
    {
        if ($search !== '') {
            return $this->db->query(
                "SELECT * FROM barangays WHERE name LIKE :search ORDER BY name ASC",
                ['search' => '%' . $search . '%']
            );
        }
        return $this->db->query("SELECT * FROM barangays ORDER BY name ASC");
    }

    public function find($barangayId)
    {
        $rows = $this->db->query(
            "SELECT * FROM barangays WHERE barangay_id = :id",
            ['id' => $barangayId]
        );
        return $rows[0] ?? null;
    }

    /**
     * Per-barangay stats joined by name against the free-text `barangay` column
     * already used on housing_units (no barangay_id FK exists on that table).
     * Beneficiary application counts roll up through housing_units.unit_id, so
     * applications not yet assigned a unit are not attributable to a barangay.
     */
    public function stats($barangayName)
    {
        $unitRows = $this->db->query(
            "SELECT
                COUNT(*) AS housing_units,
                SUM(occupancy_status = 'Occupied') AS occupied_units,
                SUM(occupancy_status = 'Vacant') AS vacant_units
             FROM housing_units
             WHERE status = 'Active' AND barangay = :barangay",
            ['barangay' => $barangayName]
        );

        $beneficiaryRows = $this->db->query(
            "SELECT
                COUNT(*) AS total_applications,
                SUM(b.beneficiary_status = 'Awarded') AS approved_applications,
                SUM(b.beneficiary_status IN ('Applicant', 'Qualified')) AS pending_applications
             FROM housing_beneficiaries b
             INNER JOIN housing_units hu ON hu.unit_id = b.unit_id
             WHERE b.status = 'Active' AND hu.barangay = :barangay",
            ['barangay' => $barangayName]
        );

        return array_merge(
            $unitRows[0] ?? ['housing_units' => 0, 'occupied_units' => 0, 'vacant_units' => 0],
            $beneficiaryRows[0] ?? ['total_applications' => 0, 'approved_applications' => 0, 'pending_applications' => 0]
        );
    }

    /**
     * Housing units carrying coordinates, for the map marker layer. One query,
     * not one per barangay.
     */
    public function housingUnitMarkers()
    {
        return $this->db->query(
            "SELECT unit_id, unit_code, project_name, barangay, unit_type, occupancy_status, status, latitude, longitude
             FROM housing_units
             WHERE status = 'Active' AND latitude IS NOT NULL AND longitude IS NOT NULL"
        );
    }
}
