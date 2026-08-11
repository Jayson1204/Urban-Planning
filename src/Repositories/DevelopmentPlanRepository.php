<?php

namespace App\Repositories;

class DevelopmentPlanRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($planId)
    {
        $rows = $this->db->query(
            "SELECT * FROM development_plans WHERE plan_id = :id",
            ['id' => $planId]
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
            $where[] = "CONCAT_WS(' ', dp.plan_code, dp.plan_title, dp.coverage_area) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay'])) {
            $where[] = "dp.barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }
        if (!empty($filters['plan_type'])) {
            $where[] = "dp.plan_type = :plan_type";
            $params['plan_type'] = $filters['plan_type'];
        }
        if (!empty($filters['plan_status'])) {
            $where[] = "dp.plan_status = :plan_status";
            $params['plan_status'] = $filters['plan_status'];
        }
        if (!empty($filters['status'])) {
            $where[] = "dp.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM development_plans dp {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT dp.*
                FROM development_plans dp
                {$whereSql}
                ORDER BY dp.plan_code ASC
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
                SUM(status = 'Active' AND plan_status = 'Draft') AS draft,
                SUM(status = 'Active' AND plan_status = 'Active') AS active,
                SUM(status = 'Active' AND plan_status = 'Completed') AS completed,
                SUM(status = 'Archived') AS archived
             FROM development_plans"
        );
        return $rows[0] ?? ['total' => 0, 'draft' => 0, 'active' => 0, 'completed' => 0, 'archived' => 0];
    }

    public function codeExists($planCode, $excludeId = null)
    {
        $sql = "SELECT plan_id FROM development_plans WHERE plan_code = :code";
        $params = ['code' => $planCode];
        if ($excludeId) {
            $sql .= " AND plan_id <> :id";
            $params['id'] = $excludeId;
        }
        $rows = $this->db->query($sql, $params);
        return !empty($rows);
    }

    public function create($data)
    {
        return $this->db->insert('development_plans', $data);
    }

    public function update($planId, $data)
    {
        return $this->db->update('development_plans', $data, ['plan_id' => $planId]);
    }

    public function setStatus($planId, $status)
    {
        return $this->db->update('development_plans', ['status' => $status], ['plan_id' => $planId]);
    }
}
