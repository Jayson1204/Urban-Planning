<?php

namespace App\Repositories;

class ResidentRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($residentId)
    {
        $rows = $this->db->query(
            "SELECT r.*, h.barangay AS household_barangay, h.street_address AS household_address, h.household_number
             FROM residents r
             LEFT JOIN households h ON r.household_id = h.household_id
             WHERE r.resident_id = :id",
            ['id' => $residentId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name) LIKE :search
                         OR r.contact_number LIKE :search OR r.email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay'])) {
            $where[] = "r.barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }
        if (!empty($filters['status'])) {
            $where[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM residents r {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT r.*, h.barangay AS household_barangay, h.household_number
                FROM residents r
                LEFT JOIN households h ON r.household_id = h.household_id
                {$whereSql}
                ORDER BY r.last_name ASC, r.first_name ASC
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
                SUM(status = 'Archived') AS archived,
                COUNT(DISTINCT household_id) AS households_covered
             FROM residents"
        );
        return $rows[0] ?? ['total' => 0, 'active' => 0, 'archived' => 0, 'households_covered' => 0];
    }

    public function create($data)
    {
        return $this->db->insert('residents', $data);
    }

    public function update($residentId, $data)
    {
        return $this->db->update('residents', $data, ['resident_id' => $residentId]);
    }

    public function setStatus($residentId, $status)
    {
        return $this->db->update('residents', ['status' => $status], ['resident_id' => $residentId]);
    }
}
