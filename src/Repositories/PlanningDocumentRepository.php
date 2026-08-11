<?php

namespace App\Repositories;

class PlanningDocumentRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function forPlan($planId)
    {
        return $this->db->query(
            "SELECT * FROM planning_documents WHERE plan_id = :id ORDER BY uploaded_at DESC",
            ['id' => $planId]
        );
    }

    public function find($documentId)
    {
        $rows = $this->db->query(
            "SELECT * FROM planning_documents WHERE document_id = :id",
            ['id' => $documentId]
        );
        return $rows[0] ?? null;
    }

    public function create($data)
    {
        return $this->db->insert('planning_documents', $data);
    }

    public function delete($documentId)
    {
        return $this->db->delete('planning_documents', ['document_id' => $documentId]);
    }
}
