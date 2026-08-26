<?php

namespace App\Repositories;

class SubdivisionRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($subdivisionId)
    {
        $rows = $this->db->query(
            "SELECT s.*, b.name AS barangay_name FROM subdivisions s
             LEFT JOIN barangays b ON s.barangay_id = b.barangay_id
             WHERE s.subdivision_id = :id",
            ['id' => $subdivisionId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', s.name, s.barangay, s.subdivision_type) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay_id'])) {
            $where[] = "s.barangay_id = :barangay_id";
            $params['barangay_id'] = $filters['barangay_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = "s.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM subdivisions s {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT s.*, b.name AS barangay_name
                FROM subdivisions s
                LEFT JOIN barangays b ON s.barangay_id = b.barangay_id
                {$whereSql}
                ORDER BY s.name ASC
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

    /**
     * All active subdivisions, for the map layer (small dataset, no bbox limiting needed).
     */
    public function allActive()
    {
        return $this->db->query(
            "SELECT s.*, b.name AS barangay_name FROM subdivisions s
             LEFT JOIN barangays b ON s.barangay_id = b.barangay_id
             WHERE s.status = 'Active'
             ORDER BY s.name ASC"
        );
    }

    public function countByBarangay($barangayId)
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) AS total FROM subdivisions WHERE status = 'Active' AND barangay_id = :id",
            ['id' => $barangayId]
        );
        return (int)($rows[0]['total'] ?? 0);
    }

    public function create($data)
    {
        return $this->db->insert('subdivisions', $data);
    }

    public function update($subdivisionId, $data)
    {
        return $this->db->update('subdivisions', $data, ['subdivision_id' => $subdivisionId]);
    }

    public function setStatus($subdivisionId, $status)
    {
        return $this->db->update('subdivisions', ['status' => $status], ['subdivision_id' => $subdivisionId]);
    }
}
