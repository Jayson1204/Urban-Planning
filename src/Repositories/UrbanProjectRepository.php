<?php

namespace App\Repositories;

class UrbanProjectRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT up.*, dp.plan_code, dp.plan_title
                FROM urban_projects up
                LEFT JOIN development_plans dp ON up.plan_id = dp.plan_id";
    }

    public function find($projectId)
    {
        $rows = $this->db->query(
            $this->baseSelect() . " WHERE up.project_id = :id",
            ['id' => $projectId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', up.project_code, up.project_title, up.coverage_area) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay'])) {
            $where[] = "up.barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }
        if (!empty($filters['project_type'])) {
            $where[] = "up.project_type = :project_type";
            $params['project_type'] = $filters['project_type'];
        }
        if (!empty($filters['project_status'])) {
            $where[] = "up.project_status = :project_status";
            $params['project_status'] = $filters['project_status'];
        }
        if (!empty($filters['status'])) {
            $where[] = "up.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['plan_id'])) {
            $where[] = "up.plan_id = :plan_id";
            $params['plan_id'] = $filters['plan_id'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM urban_projects up {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . " {$whereSql}
                ORDER BY up.project_code ASC
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
                SUM(status = 'Active' AND project_status = 'Planned') AS planned,
                SUM(status = 'Active' AND project_status = 'Ongoing') AS ongoing,
                SUM(status = 'Active' AND project_status = 'Completed') AS completed,
                SUM(status = 'Archived') AS archived
             FROM urban_projects"
        );
        return $rows[0] ?? ['total' => 0, 'planned' => 0, 'ongoing' => 0, 'completed' => 0, 'archived' => 0];
    }

    public function codeExists($projectCode, $excludeId = null)
    {
        $sql = "SELECT project_id FROM urban_projects WHERE project_code = :code";
        $params = ['code' => $projectCode];
        if ($excludeId) {
            $sql .= " AND project_id <> :id";
            $params['id'] = $excludeId;
        }
        $rows = $this->db->query($sql, $params);
        return !empty($rows);
    }

    public function create($data)
    {
        return $this->db->insert('urban_projects', $data);
    }

    public function update($projectId, $data)
    {
        return $this->db->update('urban_projects', $data, ['project_id' => $projectId]);
    }

    public function setStatus($projectId, $status)
    {
        return $this->db->update('urban_projects', ['status' => $status], ['project_id' => $projectId]);
    }
}
