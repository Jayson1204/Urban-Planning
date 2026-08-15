<?php

namespace App\Repositories;

class FieldSurveyFormRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($formId)
    {
        $rows = $this->db->query(
            "SELECT * FROM field_survey_forms WHERE form_id = :id",
            ['id' => $formId]
        );
        return $rows[0] ?? null;
    }

    public function paginate($filters, $page = 1, $perPage = 10)
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "CONCAT_WS(' ', f.form_code, f.form_title) LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['category'])) {
            $where[] = "f.category = :category";
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['subject_type'])) {
            $where[] = "f.subject_type = :subject_type";
            $params['subject_type'] = $filters['subject_type'];
        }
        if (!empty($filters['status'])) {
            $where[] = "f.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM field_survey_forms f {$whereSql}";
        $countRows = $this->db->query($countSql, $params);
        $total = (int)($countRows[0]['total'] ?? 0);

        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT f.*
                FROM field_survey_forms f
                {$whereSql}
                ORDER BY f.form_code ASC
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
             FROM field_survey_forms"
        );
        return $rows[0] ?? ['total' => 0, 'active' => 0, 'archived' => 0];
    }

    public function codeExists($formCode, $excludeId = null)
    {
        $sql = "SELECT form_id FROM field_survey_forms WHERE form_code = :code";
        $params = ['code' => $formCode];
        if ($excludeId) {
            $sql .= " AND form_id <> :id";
            $params['id'] = $excludeId;
        }
        $rows = $this->db->query($sql, $params);
        return !empty($rows);
    }

    public function create($data)
    {
        return $this->db->insert('field_survey_forms', $data);
    }

    public function update($formId, $data)
    {
        return $this->db->update('field_survey_forms', $data, ['form_id' => $formId]);
    }

    public function setStatus($formId, $status)
    {
        return $this->db->update('field_survey_forms', ['status' => $status], ['form_id' => $formId]);
    }
}
