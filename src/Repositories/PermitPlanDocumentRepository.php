<?php

namespace App\Repositories;

class PermitPlanDocumentRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function forApplication($applicationId)
    {
        return $this->db->query(
            "SELECT * FROM permit_plan_documents WHERE application_id = :id ORDER BY document_type ASC, version_number DESC",
            ['id' => $applicationId]
        );
    }

    public function find($documentId)
    {
        $rows = $this->db->query(
            "SELECT * FROM permit_plan_documents WHERE document_id = :id",
            ['id' => $documentId]
        );
        return $rows[0] ?? null;
    }

    public function latestVersion($applicationId, $documentType)
    {
        $rows = $this->db->query(
            "SELECT * FROM permit_plan_documents WHERE application_id = :id AND document_type = :type ORDER BY version_number DESC LIMIT 1",
            ['id' => $applicationId, 'type' => $documentType]
        );
        return $rows[0] ?? null;
    }

    public function create($data)
    {
        return $this->db->insert('permit_plan_documents', $data);
    }

    public function markSuperseded($documentId)
    {
        return $this->db->update('permit_plan_documents', ['document_status' => 'Superseded'], ['document_id' => $documentId]);
    }

    public function delete($documentId)
    {
        return $this->db->delete('permit_plan_documents', ['document_id' => $documentId]);
    }
}
