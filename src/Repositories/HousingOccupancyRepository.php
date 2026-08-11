<?php

namespace App\Repositories;

class HousingOccupancyRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT o.*,
                    CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS resident_name,
                    res.barangay AS resident_barangay,
                    hu.unit_code, hu.project_name
                FROM housing_occupancy o
                LEFT JOIN residents res ON o.resident_id = res.resident_id
                LEFT JOIN housing_units hu ON o.unit_id = hu.unit_id";
    }

    public function find($occupancyId)
    {
        $rows = $this->db->query(
            $this->baseSelect() . " WHERE o.occupancy_id = :id",
            ['id' => $occupancyId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name, hu.unit_code) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = "o.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['unit_id'])) {
            $where[] = "o.unit_id = :unit_id";
            $params['unit_id'] = $filters['unit_id'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM housing_occupancy o
                     LEFT JOIN residents res ON o.resident_id = res.resident_id
                     LEFT JOIN housing_units hu ON o.unit_id = hu.unit_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . " {$whereSql}
                ORDER BY o.status ASC, o.move_in_date DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $rows = $this->db->query($sql, $params);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    public function stats()
    {
        $rows = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'Active') AS active,
                SUM(status = 'Ended') AS ended,
                COUNT(DISTINCT CASE WHEN status = 'Active' THEN unit_id END) AS units_occupied
             FROM housing_occupancy"
        );
        return $rows[0] ?? ['total' => 0, 'active' => 0, 'ended' => 0, 'units_occupied' => 0];
    }

    public function activeForUnit($unitId)
    {
        $rows = $this->db->query(
            "SELECT * FROM housing_occupancy WHERE unit_id = :unit_id AND status = 'Active'",
            ['unit_id' => $unitId]
        );
        return $rows[0] ?? null;
    }

    public function activeForResident($residentId)
    {
        $rows = $this->db->query(
            "SELECT * FROM housing_occupancy WHERE resident_id = :resident_id AND status = 'Active'",
            ['resident_id' => $residentId]
        );
        return $rows[0] ?? null;
    }

    public function forUnit($unitId)
    {
        return $this->db->query(
            $this->baseSelect() . " WHERE o.unit_id = :unit_id ORDER BY o.move_in_date DESC",
            ['unit_id' => $unitId]
        );
    }

    public function create($data)
    {
        return $this->db->insert('housing_occupancy', $data);
    }

    public function endOccupancy($occupancyId, $moveOutDate)
    {
        return $this->db->update('housing_occupancy', [
            'status' => 'Ended',
            'move_out_date' => $moveOutDate,
        ], ['occupancy_id' => $occupancyId]);
    }
}
