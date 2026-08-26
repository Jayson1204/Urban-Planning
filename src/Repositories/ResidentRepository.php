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
            $where[] = "CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name, r.contact_number, r.email) LIKE :search";
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
        if (!empty($filters['date_from'])) {
            $where[] = "r.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "r.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
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

    public function findByEmail($email)
    {
        $rows = $this->db->query(
            "SELECT * FROM residents WHERE email = :email ORDER BY resident_id ASC LIMIT 1",
            ['email' => $email]
        );
        return $rows[0] ?? null;
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
