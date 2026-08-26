<?php

namespace App\Repositories;

class HousingProjectRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($housingProjectId)
    {
        $rows = $this->db->query(
            "SELECT hp.*, b.name AS barangay_name FROM housing_projects hp
             LEFT JOIN barangays b ON hp.barangay_id = b.barangay_id
             WHERE hp.housing_project_id = :id",
            ['id' => $housingProjectId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', hp.name, hp.barangay, hp.developer) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay_id'])) {
            $where[] = "hp.barangay_id = :barangay_id";
            $params['barangay_id'] = $filters['barangay_id'];
        }
        if (!empty($filters['project_status'])) {
            $where[] = "hp.project_status = :project_status";
            $params['project_status'] = $filters['project_status'];
        }
        if (!empty($filters['status'])) {
            $where[] = "hp.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM housing_projects hp {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT hp.*, b.name AS barangay_name
                FROM housing_projects hp
                LEFT JOIN barangays b ON hp.barangay_id = b.barangay_id
                {$whereSql}
                ORDER BY hp.name ASC
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

    public function allActive()
    {
        return $this->db->query(
            "SELECT hp.*, b.name AS barangay_name FROM housing_projects hp
             LEFT JOIN barangays b ON hp.barangay_id = b.barangay_id
             WHERE hp.status = 'Active'
             ORDER BY hp.name ASC"
        );
    }

    public function countByBarangay($barangayId)
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) AS total FROM housing_projects WHERE status = 'Active' AND barangay_id = :id",
            ['id' => $barangayId]
        );
        return (int)($rows[0]['total'] ?? 0);
    }

    public function create($data)
    {
        return $this->db->insert('housing_projects', $data);
    }

    public function update($housingProjectId, $data)
    {
        return $this->db->update('housing_projects', $data, ['housing_project_id' => $housingProjectId]);
    }

    public function setStatus($housingProjectId, $status)
    {
        return $this->db->update('housing_projects', ['status' => $status], ['housing_project_id' => $housingProjectId]);
    }
}
