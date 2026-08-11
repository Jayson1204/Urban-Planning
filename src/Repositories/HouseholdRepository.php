<?php

namespace App\Repositories;

class HouseholdRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($householdId)
    {
        $rows = $this->db->query(
            "SELECT * FROM households WHERE household_id = :id",
            ['id' => $householdId]
        );
        return $rows[0] ?? null;
    }

    public function search($term, $limit = 10)
    {
        $sql = "SELECT * FROM households
                WHERE status = 'Active' AND CONCAT_WS(' ', household_number, barangay, street_address) LIKE :term
                ORDER BY barangay, street_address
                LIMIT " . (int)$limit;
        return $this->db->query($sql, ['term' => '%' . $term . '%']);
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', household_number, barangay, street_address) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay'])) {
            $where[] = "barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }
        if (!empty($filters['household_type'])) {
            $where[] = "household_type = :household_type";
            $params['household_type'] = $filters['household_type'];
        }
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM households {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT h.*, (SELECT COUNT(*) FROM residents r WHERE r.household_id = h.household_id) AS member_count
                FROM households h
                {$whereSql}
                ORDER BY h.barangay ASC, h.street_address ASC
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
                (SELECT COUNT(*) FROM residents WHERE household_id IS NOT NULL) AS residents_covered
             FROM households"
        );
        return $rows[0] ?? ['total' => 0, 'active' => 0, 'archived' => 0, 'residents_covered' => 0];
    }

    public function create($data)
    {
        return $this->db->insert('households', $data);
    }

    public function update($householdId, $data)
    {
        return $this->db->update('households', $data, ['household_id' => $householdId]);
    }

    public function setStatus($householdId, $status)
    {
        return $this->db->update('households', ['status' => $status], ['household_id' => $householdId]);
    }

    public function getMembers($householdId)
    {
        return $this->db->query(
            "SELECT resident_id, first_name, middle_name, last_name, relationship_to_head, status
             FROM residents WHERE household_id = :id ORDER BY
             FIELD(relationship_to_head, 'Head') DESC, first_name ASC",
            ['id' => $householdId]
        );
    }
}
