<?php

namespace App\Repositories;

class PermitApplicationRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT pa.*,
                    CONCAT_WS(' ', res.first_name, res.middle_name, res.last_name) AS applicant_name,
                    res.contact_number AS applicant_contact,
                    res.barangay AS applicant_barangay
                FROM permit_applications pa
                LEFT JOIN residents res ON pa.resident_id = res.resident_id";
    }

    public function find($applicationId)
    {
        $rows = $this->db->query(
            $this->baseSelect() . " WHERE pa.application_id = :id",
            ['id' => $applicationId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', pa.reference_number, pa.project_name, res.first_name, res.middle_name, res.last_name) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['application_type'])) {
            $where[] = "pa.application_type = :application_type";
            $params['application_type'] = $filters['application_type'];
        }
        if (!empty($filters['application_status'])) {
            $where[] = "pa.application_status = :application_status";
            $params['application_status'] = $filters['application_status'];
        }
        if (!empty($filters['consolidated_result'])) {
            $where[] = "pa.consolidated_result = :consolidated_result";
            $params['consolidated_result'] = $filters['consolidated_result'];
        }
        if (!empty($filters['status'])) {
            $where[] = "pa.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM permit_applications pa
                     LEFT JOIN residents res ON pa.resident_id = res.resident_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . " {$whereSql}
                ORDER BY pa.created_at DESC
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
                SUM(status = 'Active' AND application_status IN ('Submitted','Under Review','Returned for Revision')) AS pending,
                SUM(status = 'Active' AND application_status = 'Permit Issued') AS issued,
                SUM(status = 'Active' AND application_status = 'Denied') AS denied
             FROM permit_applications"
        );
        return $rows[0] ?? ['total' => 0, 'pending' => 0, 'issued' => 0, 'denied' => 0];
    }

    public function referenceExists($referenceNumber)
    {
        $rows = $this->db->query(
            "SELECT 1 FROM permit_applications WHERE reference_number = :ref",
            ['ref' => $referenceNumber]
        );
        return !empty($rows);
    }

    public function countForYear($prefix, $year)
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) AS total FROM permit_applications WHERE reference_number LIKE :prefix",
            ['prefix' => "{$prefix}-{$year}-%"]
        );
        return (int)($rows[0]['total'] ?? 0);
    }

    public function create($data)
    {
        return $this->db->insert('permit_applications', $data);
    }

    public function update($applicationId, $data)
    {
        return $this->db->update('permit_applications', $data, ['application_id' => $applicationId]);
    }

    public function setStatus($applicationId, $status)
    {
        return $this->db->update('permit_applications', ['status' => $status], ['application_id' => $applicationId]);
    }

    public function addReview($applicationId, $discipline, $resubmissionRound, $reviewerName, $reviewerRole, $action, $remarks)
    {
        return $this->db->insert('permit_application_reviews', [
            'application_id' => $applicationId,
            'discipline' => $discipline,
            'resubmission_round' => $resubmissionRound,
            'reviewer_name' => $reviewerName,
            'reviewer_role' => $reviewerRole,
            'action' => $action,
            'remarks' => $remarks,
        ]);
    }

    public function getReviews($applicationId)
    {
        return $this->db->query(
            "SELECT * FROM permit_application_reviews WHERE application_id = :id ORDER BY created_at ASC",
            ['id' => $applicationId]
        );
    }

    public function createDisciplineReview($applicationId, $discipline)
    {
        return $this->db->insert('permit_discipline_reviews', [
            'application_id' => $applicationId,
            'discipline' => $discipline,
            'review_status' => 'Pending',
        ]);
    }

    public function getDisciplineReviews($applicationId)
    {
        return $this->db->query(
            "SELECT * FROM permit_discipline_reviews WHERE application_id = :id ORDER BY discipline ASC",
            ['id' => $applicationId]
        );
    }

    public function findDisciplineReview($applicationId, $discipline)
    {
        $rows = $this->db->query(
            "SELECT * FROM permit_discipline_reviews WHERE application_id = :id AND discipline = :discipline",
            ['id' => $applicationId, 'discipline' => $discipline]
        );
        return $rows[0] ?? null;
    }

    public function updateDisciplineReview($disciplineReviewId, $data)
    {
        return $this->db->update('permit_discipline_reviews', $data, ['discipline_review_id' => $disciplineReviewId]);
    }
}
