<?php

namespace App\Repositories;

class InfrastructureRecordRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT ir.*, up.project_code, up.project_title
                FROM infrastructure_records ir
                LEFT JOIN urban_projects up ON ir.project_id = up.project_id";
    }

    public function find($recordId)
    {
        $rows = $this->db->query(
            $this->baseSelect() . " WHERE ir.record_id = :id",
            ['id' => $recordId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', ir.infrastructure_name, ir.location_details, up.project_code) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay'])) {
            $where[] = "ir.barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }
        if (!empty($filters['infrastructure_type'])) {
            $where[] = "ir.infrastructure_type = :infrastructure_type";
            $params['infrastructure_type'] = $filters['infrastructure_type'];
        }
        if (!empty($filters['condition_status'])) {
            $where[] = "ir.condition_status = :condition_status";
            $params['condition_status'] = $filters['condition_status'];
        }
        if (!empty($filters['status'])) {
            $where[] = "ir.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM infrastructure_records ir
                     LEFT JOIN urban_projects up ON ir.project_id = up.project_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . " {$whereSql}
                ORDER BY ir.infrastructure_name ASC
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
                SUM(status = 'Active' AND condition_status = 'Good') AS good,
                SUM(status = 'Active' AND condition_status = 'Needs Repair') AS needs_repair,
                SUM(status = 'Active' AND condition_status = 'Under Construction') AS under_construction,
                SUM(status = 'Archived') AS archived
             FROM infrastructure_records"
        );
        return $rows[0] ?? ['total' => 0, 'good' => 0, 'needs_repair' => 0, 'under_construction' => 0, 'archived' => 0];
    }

    public function create($data)
    {
        return $this->db->insert('infrastructure_records', $data);
    }

    public function update($recordId, $data)
    {
        return $this->db->update('infrastructure_records', $data, ['record_id' => $recordId]);
    }

    public function setStatus($recordId, $status)
    {
        return $this->db->update('infrastructure_records', ['status' => $status], ['record_id' => $recordId]);
    }
}
