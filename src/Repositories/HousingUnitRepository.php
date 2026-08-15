<?php

namespace App\Repositories;

class HousingUnitRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($unitId)
    {
        $rows = $this->db->query(
            "SELECT * FROM housing_units WHERE unit_id = :id",
            ['id' => $unitId]
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
            $where[] = "CONCAT_WS(' ', hu.unit_code, hu.project_name, hu.street_address) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['barangay'])) {
            $where[] = "hu.barangay = :barangay";
            $params['barangay'] = $filters['barangay'];
        }
        if (!empty($filters['occupancy_status'])) {
            $where[] = "hu.occupancy_status = :occupancy_status";
            $params['occupancy_status'] = $filters['occupancy_status'];
        }
        if (!empty($filters['status'])) {
            $where[] = "hu.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "hu.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "hu.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM housing_units hu {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT hu.*
                FROM housing_units hu
                {$whereSql}
                ORDER BY hu.unit_code ASC
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
                SUM(status = 'Active' AND occupancy_status = 'Vacant') AS vacant,
                SUM(status = 'Active' AND occupancy_status = 'Occupied') AS occupied,
                SUM(status = 'Archived') AS archived
             FROM housing_units"
        );
        return $rows[0] ?? ['total' => 0, 'vacant' => 0, 'occupied' => 0, 'archived' => 0];
    }

    public function codeExists($unitCode, $excludeId = null)
    {
        $sql = "SELECT unit_id FROM housing_units WHERE unit_code = :code";
        $params = ['code' => $unitCode];
        if ($excludeId) {
            $sql .= " AND unit_id <> :id";
            $params['id'] = $excludeId;
        }
        $rows = $this->db->query($sql, $params);
        return !empty($rows);
    }

    public function create($data)
    {
        return $this->db->insert('housing_units', $data);
    }

    public function update($unitId, $data)
    {
        return $this->db->update('housing_units', $data, ['unit_id' => $unitId]);
    }

    public function setStatus($unitId, $status)
    {
        return $this->db->update('housing_units', ['status' => $status], ['unit_id' => $unitId]);
    }

    /**
     * Per-unit timeline: merges occupancy moves, relocations, and beneficiary awards
     * into one date-sorted history instead of a separate append-only log table.
     */
    public function getHistory($unitId)
    {
        $events = [];

        $occupancyRows = $this->db->query(
            "SELECT o.*, CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS resident_name
             FROM housing_occupancy o
             LEFT JOIN residents res ON o.resident_id = res.resident_id
             WHERE o.unit_id = :unit_id",
            ['unit_id' => $unitId]
        );
        foreach ($occupancyRows as $row) {
            $events[] = [
                'date' => $row['move_in_date'],
                'type' => 'Occupancy Started',
                'description' => trim($row['resident_name']) . ' moved in.',
            ];
            if (!empty($row['move_out_date'])) {
                $events[] = [
                    'date' => $row['move_out_date'],
                    'type' => 'Occupancy Ended',
                    'description' => trim($row['resident_name']) . ' moved out.',
                ];
            }
        }

        $relocationRows = $this->db->query(
            "SELECT rel.*, CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS resident_name,
                    fu.unit_code AS from_unit_code, tu.unit_code AS to_unit_code
             FROM housing_relocations rel
             LEFT JOIN residents res ON rel.resident_id = res.resident_id
             LEFT JOIN housing_units fu ON rel.from_unit_id = fu.unit_id
             LEFT JOIN housing_units tu ON rel.to_unit_id = tu.unit_id
             WHERE (rel.from_unit_id = :unit_id OR rel.to_unit_id = :unit_id2) AND rel.status = 'Active'",
            ['unit_id' => $unitId, 'unit_id2' => $unitId]
        );
        foreach ($relocationRows as $row) {
            $direction = (int)$row['to_unit_id'] === (int)$unitId
                ? 'relocated in from ' . ($row['from_unit_code'] ?? 'an unrecorded unit')
                : 'relocated out to ' . ($row['to_unit_code'] ?? 'an unrecorded unit');
            $events[] = [
                'date' => $row['relocation_date'],
                'type' => 'Relocation',
                'description' => trim($row['resident_name']) . ' ' . $direction . ' (' . $row['reason'] . ').',
            ];
        }

        $awardRows = $this->db->query(
            "SELECT b.award_date, CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS resident_name
             FROM housing_beneficiaries b
             LEFT JOIN residents res ON b.resident_id = res.resident_id
             WHERE b.unit_id = :unit_id AND b.beneficiary_status = 'Awarded' AND b.award_date IS NOT NULL",
            ['unit_id' => $unitId]
        );
        foreach ($awardRows as $row) {
            $events[] = [
                'date' => $row['award_date'],
                'type' => 'Beneficiary Awarded',
                'description' => trim($row['resident_name']) . ' was awarded this unit.',
            ];
        }

        usort($events, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $events;
    }
}
