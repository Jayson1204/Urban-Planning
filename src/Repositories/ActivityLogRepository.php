<?php

namespace App\Repositories;

class ActivityLogRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($data)
    {
        return $this->db->insert('activity_logs', $data);
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', al.actor_name, al.target_label, al.description) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['module'])) {
            $where[] = "al.module = :module";
            $params['module'] = $filters['module'];
        }
        if (!empty($filters['action'])) {
            $where[] = "al.action = :action";
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "al.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "al.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM activity_logs al {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT al.*
                FROM activity_logs al
                {$whereSql}
                ORDER BY al.created_at DESC
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
                SUM(DATE(created_at) = CURDATE()) AS today,
                SUM(action = 'Create') AS creates,
                SUM(action = 'Update') AS updates,
                SUM(action IN ('Archive','Delete')) AS archives
             FROM activity_logs"
        );
        return $rows[0] ?? ['total' => 0, 'today' => 0, 'creates' => 0, 'updates' => 0, 'archives' => 0];
    }

    public function distinctModules()
    {
        $rows = $this->db->query("SELECT DISTINCT module FROM activity_logs ORDER BY module ASC");
        return array_column($rows, 'module');
    }
}
