<?php

namespace App\Repositories;

class ResidentDocumentRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function forResident($residentId)
    {
        return $this->db->query(
            "SELECT * FROM resident_documents WHERE resident_id = :id ORDER BY uploaded_at DESC",
            ['id' => $residentId]
        );
    }

    public function find($documentId)
    {
        $rows = $this->db->query(
            "SELECT * FROM resident_documents WHERE document_id = :id",
            ['id' => $documentId]
        );
        return $rows[0] ?? null;
    }

    public function create($data)
    {
        return $this->db->insert('resident_documents', $data);
    }

    public function delete($documentId)
    {
        return $this->db->delete('resident_documents', ['document_id' => $documentId]);
    }
}
