<?php

namespace App\Repositories;

class HousingUnitRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($unitId)
    {
        $rows = $this->db->query(
            "SELECT * FROM housing_units WHERE unit_id = :id",
            ['id' => $unitId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            // Single placeholder (CONCAT_WS skips NULLs) so it binds once — the project's
            // PDO runs with EMULATE_PREPARES=false, which rejects a reused named placeholder.
            $where[] = "CONCAT_WS(' ', hu.unit_code, hu.project_name, hu.street_address) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay'])) {
            $where[] = "hu.barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }
        if (!empty($filters['occupancy_status'])) {
            $where[] = "hu.occupancy_status = :occupancy_status";
            $params['occupancy_status'] = $filters['occupancy_status'];
        }
        if (!empty($filters['status'])) {
            $where[] = "hu.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM housing_units hu {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT hu.*
                FROM housing_units hu
                {$whereSql}
                ORDER BY hu.unit_code ASC
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
                SUM(status = 'Active' AND occupancy_status = 'Vacant') AS vacant,
                SUM(status = 'Active' AND occupancy_status = 'Occupied') AS occupied,
                SUM(status = 'Archived') AS archived
             FROM housing_units"
        );
        return $rows[0] ?? ['total' => 0, 'vacant' => 0, 'occupied' => 0, 'archived' => 0];
    }

    public function codeExists($unitCode, $excludeId = null)
    {
        $sql = "SELECT unit_id FROM housing_units WHERE unit_code = :code";
        $params = ['code' => $unitCode];
        if ($excludeId) {
            $sql .= " AND unit_id <> :id";
            $params['id'] = $excludeId;
        }
        $rows = $this->db->query($sql, $params);
        return !empty($rows);
    }

    public function create($data)
    {
        return $this->db->insert('housing_units', $data);
    }

    public function update($unitId, $data)
    {
        return $this->db->update('housing_units', $data, ['unit_id' => $unitId]);
    }

    public function setStatus($unitId, $status)
    {
        return $this->db->update('housing_units', ['status' => $status], ['unit_id' => $unitId]);
    }
}
