<?php

namespace App\Repositories;

class HousingRelocationRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT r.*,
                    CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS resident_name,
                    fu.unit_code AS from_unit_code, tu.unit_code AS to_unit_code
                FROM housing_relocations r
                LEFT JOIN residents res ON r.resident_id = res.resident_id
                LEFT JOIN housing_units fu ON r.from_unit_id = fu.unit_id
                LEFT JOIN housing_units tu ON r.to_unit_id = tu.unit_id";
    }

    public function find($relocationId)
    {
        $rows = $this->db->query(
            $this->baseSelect() . " WHERE r.relocation_id = :id",
            ['id' => $relocationId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name, fu.unit_code, tu.unit_code) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['reason'])) {
            $where[] = "r.reason = :reason";
            $params['reason'] = $filters['reason'];
        }
        if (!empty($filters['status'])) {
            $where[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['unit_id'])) {
            $where[] = "(r.from_unit_id = :unit_id OR r.to_unit_id = :unit_id2)";
            $params['unit_id'] = $filters['unit_id'];
            $params['unit_id2'] = $filters['unit_id'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM housing_relocations r
                     LEFT JOIN residents res ON r.resident_id = res.resident_id
                     LEFT JOIN housing_units fu ON r.from_unit_id = fu.unit_id
                     LEFT JOIN housing_units tu ON r.to_unit_id = tu.unit_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . " {$whereSql}
                ORDER BY r.relocation_date DESC, r.created_at DESC
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
                SUM(status = 'Active' AND relocation_date = CURDATE()) AS today,
                SUM(status = 'Archived') AS archived
             FROM housing_relocations"
        );
        return $rows[0] ?? ['total' => 0, 'active' => 0, 'today' => 0, 'archived' => 0];
    }

    public function forUnit($unitId)
    {
        return $this->db->query(
            $this->baseSelect() . " WHERE r.from_unit_id = :unit_id OR r.to_unit_id = :unit_id2 ORDER BY r.relocation_date DESC",
            ['unit_id' => $unitId, 'unit_id2' => $unitId]
        );
    }

    public function create($data)
    {
        return $this->db->insert('housing_relocations', $data);
    }

    public function setStatus($relocationId, $status)
    {
        return $this->db->update('housing_relocations', ['status' => $status], ['relocation_id' => $relocationId]);
    }
}
