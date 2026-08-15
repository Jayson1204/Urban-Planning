<?php

namespace App\Repositories;

class FieldSurveyAssignmentRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT a.*,
                    f.form_code, f.form_title, f.category,
                    CASE
                        WHEN a.subject_type = 'Resident' THEN CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name)
                        WHEN a.subject_type = 'Household' THEN h.household_number
                        ELSE a.site_label
                    END AS subject_name,
                    r.barangay AS resident_barangay,
                    h.barangay AS household_barangay
                FROM field_survey_assignments a
                LEFT JOIN field_survey_forms f ON a.form_id = f.form_id
                LEFT JOIN residents r ON a.subject_type = 'Resident' AND a.subject_id = r.resident_id
                LEFT JOIN households h ON a.subject_type = 'Household' AND a.subject_id = h.household_id";
    }

    public function find($assignmentId)
    {
        $sql = $this->baseSelect() . " WHERE a.assignment_id = :id";
        $rows = $this->db->query($sql, ['id' => $assignmentId]);
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', f.form_code, f.form_title, a.site_label, a.assigned_to) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['subject_type'])) {
            $where[] = "a.subject_type = :subject_type";
            $params['subject_type'] = $filters['subject_type'];
        }
        if (!empty($filters['assignment_status'])) {
            $where[] = "a.assignment_status = :assignment_status";
            $params['assignment_status'] = $filters['assignment_status'];
        }
        if (!empty($filters['status'])) {
            $where[] = "a.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM field_survey_assignments a
                     LEFT JOIN field_survey_forms f ON a.form_id = f.form_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . "
                {$whereSql}
                ORDER BY a.due_date IS NULL, a.due_date ASC, a.assignment_id DESC
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
                SUM(status = 'Active' AND assignment_status = 'Pending') AS pending,
                SUM(status = 'Active' AND assignment_status = 'In Progress') AS in_progress,
                SUM(status = 'Active' AND assignment_status = 'Completed') AS completed
             FROM field_survey_assignments"
        );
        return $rows[0] ?? ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
    }

    public function create($data)
    {
        return $this->db->insert('field_survey_assignments', $data);
    }

    public function update($assignmentId, $data)
    {
        return $this->db->update('field_survey_assignments', $data, ['assignment_id' => $assignmentId]);
    }

    public function setStatus($assignmentId, $status)
    {
        return $this->db->update('field_survey_assignments', ['status' => $status], ['assignment_id' => $assignmentId]);
    }

    /**
     * Per-subject timeline: merges every assignment and its result (if recorded) for a
     * resident, household, or site into one date-sorted history instead of a separate
     * append-only log table.
     */
    public function getHistoryForSubject($subjectType, $subjectId = null, $siteLabel = null)
    {
        $where = "a.subject_type = :subject_type";
        $params = ['subject_type' => $subjectType];

        if ($subjectType === 'Site') {
            $where .= " AND a.site_label = :site_label";
            $params['site_label'] = $siteLabel;
        } else {
            $where .= " AND a.subject_id = :subject_id";
            $params['subject_id'] = $subjectId;
        }

        $rows = $this->db->query(
            "SELECT a.assignment_id, a.assigned_to, a.due_date, a.assignment_status, a.created_at AS assigned_at,
                    f.form_code, f.form_title,
                    res.result_id, res.survey_date, res.condition_rating, res.findings, res.recommendations
             FROM field_survey_assignments a
             LEFT JOIN field_survey_forms f ON a.form_id = f.form_id
             LEFT JOIN field_survey_results res ON res.assignment_id = a.assignment_id
             WHERE {$where}
             ORDER BY a.created_at DESC",
            $params
        );

        $events = [];
        foreach ($rows as $row) {
            $events[] = [
                'date' => substr($row['assigned_at'], 0, 10),
                'type' => 'Assignment Created',
                'description' => trim(($row['form_code'] ?? 'Survey') . ' assigned'
                    . (!empty($row['assigned_to']) ? ' to ' . $row['assigned_to'] : '')
                    . (!empty($row['due_date']) ? ', due ' . $row['due_date'] : '') . '.'),
            ];
            if (!empty($row['result_id'])) {
                $events[] = [
                    'date' => $row['survey_date'] ?? substr($row['assigned_at'], 0, 10),
                    'type' => 'Result Recorded',
                    'description' => trim(($row['form_code'] ?? 'Survey') . ' result: '
                        . ($row['condition_rating'] ?? 'No rating')
                        . (!empty($row['findings']) ? ' — ' . $row['findings'] : '') . '.'),
                ];
            }
        }

        usort($events, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $events;
    }
}
