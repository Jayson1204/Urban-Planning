<?php

namespace App\Repositories;

class ZoningClearanceRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT zc.*,
                    CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS applicant_name,
                    res.contact_number AS applicant_contact,
                    res.barangay AS applicant_barangay
                FROM zoning_clearances zc
                LEFT JOIN residents res ON zc.resident_id = res.resident_id";
    }

    public function find($clearanceId)
    {
        $rows = $this->db->query(
            $this->baseSelect() . " WHERE zc.clearance_id = :id",
            ['id' => $clearanceId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            // Single placeholder (EMULATE_PREPARES=false rejects a reused named placeholder).
            $where[] = "CONCAT_WS(' ', zc.reference_number, res.first_name, res.middle_name, res.last_name) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['clearance_status'])) {
            $where[] = "zc.clearance_status = :clearance_status";
            $params['clearance_status'] = $filters['clearance_status'];
        }
        if (!empty($filters['zone_classification'])) {
            $where[] = "zc.zone_classification = :zone_classification";
            $params['zone_classification'] = $filters['zone_classification'];
        }
        if (!empty($filters['conformity_result'])) {
            $where[] = "zc.conformity_result = :conformity_result";
            $params['conformity_result'] = $filters['conformity_result'];
        }
        if (!empty($filters['status'])) {
            $where[] = "zc.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM zoning_clearances zc
                     LEFT JOIN residents res ON zc.resident_id = res.resident_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . " {$whereSql}
                ORDER BY zc.created_at DESC
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
                SUM(status = 'Active' AND clearance_status IN ('Submitted','Under Review','Returned for Revision')) AS pending,
                SUM(status = 'Active' AND clearance_status = 'Approved') AS approved,
                SUM(status = 'Active' AND clearance_status = 'Denied') AS denied
             FROM zoning_clearances"
        );
        return $rows[0] ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'denied' => 0];
    }

    public function referenceExists($referenceNumber)
    {
        $rows = $this->db->query(
            "SELECT 1 FROM zoning_clearances WHERE reference_number = :ref",
            ['ref' => $referenceNumber]
        );
        return !empty($rows);
    }

    public function countForYear($year)
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) AS total FROM zoning_clearances WHERE reference_number LIKE :prefix",
            ['prefix' => "ZC-{$year}-%"]
        );
        return (int)($rows[0]['total'] ?? 0);
    }

    public function create($data)
    {
        return $this->db->insert('zoning_clearances', $data);
    }

    public function update($clearanceId, $data)
    {
        return $this->db->update('zoning_clearances', $data, ['clearance_id' => $clearanceId]);
    }

    public function setStatus($clearanceId, $status)
    {
        return $this->db->update('zoning_clearances', ['status' => $status], ['clearance_id' => $clearanceId]);
    }

    public function addReview($clearanceId, $reviewerName, $reviewerRole, $action, $remarks)
    {
        return $this->db->insert('zoning_clearance_reviews', [
            'clearance_id' => $clearanceId,
            'reviewer_name' => $reviewerName,
            'reviewer_role' => $reviewerRole,
            'action' => $action,
            'remarks' => $remarks,
        ]);
    }

    public function getReviews($clearanceId)
    {
        return $this->db->query(
            "SELECT * FROM zoning_clearance_reviews WHERE clearance_id = :id ORDER BY created_at ASC",
            ['id' => $clearanceId]
        );
    }
}
