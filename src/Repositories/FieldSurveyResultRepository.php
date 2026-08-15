<?php

namespace App\Repositories;

class FieldSurveyResultRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private function baseSelect()
    {
        return "SELECT res.*,
                    a.form_id, a.subject_type, a.assigned_to, a.due_date, a.assignment_status,
                    f.form_code, f.form_title,
                    CASE
                        WHEN a.subject_type = 'Resident' THEN CONCAT_WS(' ', r.first_name, r.middle_name, r.last_name)
                        WHEN a.subject_type = 'Household' THEN h.household_number
                        ELSE a.site_label
                    END AS subject_name
                FROM field_survey_results res
                LEFT JOIN field_survey_assignments a ON res.assignment_id = a.assignment_id
                LEFT JOIN field_survey_forms f ON a.form_id = f.form_id
                LEFT JOIN residents r ON a.subject_type = 'Resident' AND a.subject_id = r.resident_id
                LEFT JOIN households h ON a.subject_type = 'Household' AND a.subject_id = h.household_id";
    }

    public function find($resultId)
    {
        $sql = $this->baseSelect() . " WHERE res.result_id = :id";
        $rows = $this->db->query($sql, ['id' => $resultId]);
        return $rows[0] ?? null;
    }

    public function findByAssignment($assignmentId)
    {
        $sql = $this->baseSelect() . " WHERE res.assignment_id = :assignment_id";
        $rows = $this->db->query($sql, ['assignment_id' => $assignmentId]);
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', f.form_code, f.form_title, a.site_label) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['condition_rating'])) {
            $where[] = "res.condition_rating = :condition_rating";
            $params['condition_rating'] = $filters['condition_rating'];
        }
        if (!empty($filters['status'])) {
            $where[] = "res.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "res.survey_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "res.survey_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM field_survey_results res
                     LEFT JOIN field_survey_assignments a ON res.assignment_id = a.assignment_id
                     LEFT JOIN field_survey_forms f ON a.form_id = f.form_id
                     {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = $this->baseSelect() . "
                {$whereSql}
                ORDER BY res.result_id DESC
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
                SUM(status = 'Archived') AS archived
             FROM field_survey_results"
        );
        return $rows[0] ?? ['total' => 0, 'active' => 0, 'archived' => 0];
    }

    public function assignmentHasResult($assignmentId, $excludeId = null)
    {
        $sql = "SELECT result_id FROM field_survey_results WHERE assignment_id = :assignment_id";
        $params = ['assignment_id' => $assignmentId];
        if ($excludeId) {
            $sql .= " AND result_id <> :id";
            $params['id'] = $excludeId;
        }
        $rows = $this->db->query($sql, $params);
        return !empty($rows);
    }

    public function create($data)
    {
        return $this->db->insert('field_survey_results', $data);
    }

    public function update($resultId, $data)
    {
        return $this->db->update('field_survey_results', $data, ['result_id' => $resultId]);
    }

    public function setStatus($resultId, $status)
    {
        return $this->db->update('field_survey_results', ['status' => $status], ['result_id' => $resultId]);
    }
}
