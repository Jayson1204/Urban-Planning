<?php

namespace App\Repositories;

class HousingBeneficiaryRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT b.*,
                    CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS resident_name,
                    res.barangay AS resident_barangay,
                    h.household_number, h.barangay AS household_barangay,
                    hu.unit_code, hu.project_name
                FROM housing_beneficiaries b
                LEFT JOIN residents res ON b.resident_id = res.resident_id
                LEFT JOIN households h ON b.household_id = h.household_id
                LEFT JOIN housing_units hu ON b.unit_id = hu.unit_id";
    }

    public function find($beneficiaryId)
    {
        $rows = $this->db->query(
            $this->baseSelect() . " WHERE b.beneficiary_id = :id",
            ['id' => $beneficiaryId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            // Single placeholder (see reference: EMULATE_PREPARES=false rejects reused names).
            $where[] = "CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name, hu.unit_code) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['beneficiary_status'])) {
            $where[] = "b.beneficiary_status = :beneficiary_status";
            $params['beneficiary_status'] = $filters['beneficiary_status'];
        }
        if (!empty($filters['category'])) {
            $where[] = "b.category = :category";
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['status'])) {
            $where[] = "b.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['unit_id'])) {
            $where[] = "b.unit_id = :unit_id";
            $params['unit_id'] = $filters['unit_id'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM housing_beneficiaries b
                     LEFT JOIN residents res ON b.resident_id = res.resident_id
                     LEFT JOIN housing_units hu ON b.unit_id = hu.unit_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . " {$whereSql}
                ORDER BY b.created_at DESC
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
                SUM(status = 'Active' AND beneficiary_status = 'Applicant') AS applicants,
                SUM(status = 'Active' AND beneficiary_status = 'Awarded') AS awarded,
                SUM(status = 'Active' AND beneficiary_status = 'Disqualified') AS disqualified
             FROM housing_beneficiaries"
        );
        return $rows[0] ?? ['total' => 0, 'applicants' => 0, 'awarded' => 0, 'disqualified' => 0];
    }

    public function hasActiveAward($unitId, $excludeId = null)
    {
        if (empty($unitId)) {
            return false;
        }
        $sql = "SELECT 1 FROM housing_beneficiaries
                WHERE unit_id = :unit_id AND beneficiary_status = 'Awarded' AND status = 'Active'";
        $params = ['unit_id' => $unitId];
        if ($excludeId) {
            $sql .= " AND beneficiary_id <> :id";
            $params['id'] = $excludeId;
        }
        $rows = $this->db->query($sql, $params);
        return !empty($rows);
    }

    public function forUnit($unitId)
    {
        return $this->db->query(
            $this->baseSelect() . " WHERE b.unit_id = :unit_id ORDER BY b.created_at DESC",
            ['unit_id' => $unitId]
        );
    }

    public function create($data)
    {
        return $this->db->insert('housing_beneficiaries', $data);
    }

    public function update($beneficiaryId, $data)
    {
        return $this->db->update('housing_beneficiaries', $data, ['beneficiary_id' => $beneficiaryId]);
    }

    public function setStatus($beneficiaryId, $status)
    {
        return $this->db->update('housing_beneficiaries', ['status' => $status], ['beneficiary_id' => $beneficiaryId]);
    }
}
