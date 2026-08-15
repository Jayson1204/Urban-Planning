<?php

namespace App\Repositories;

class BeneficiaryDocumentRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function forBeneficiary($beneficiaryId)
    {
        return $this->db->query(
            "SELECT * FROM housing_beneficiary_documents WHERE beneficiary_id = :id AND status = 'Active' ORDER BY uploaded_at DESC",
            ['id' => $beneficiaryId]
        );
    }

    public function find($documentId)
    {
        $rows = $this->db->query(
            "SELECT * FROM housing_beneficiary_documents WHERE document_id = :id",
            ['id' => $documentId]
        );
        return $rows[0] ?? null;
    }

    public function create($data)
    {
        return $this->db->insert('housing_beneficiary_documents', $data);
    }

    public function setReviewStatus($documentId, $reviewStatus, $reviewNotes, $reviewerName)
    {
        return $this->db->update('housing_beneficiary_documents', [
            'review_status' => $reviewStatus,
            'review_notes' => $reviewNotes,
            'reviewed_by_name' => $reviewerName,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], ['document_id' => $documentId]);
    }

    public function delete($documentId)
    {
        return $this->db->delete('housing_beneficiary_documents', ['document_id' => $documentId]);
    }
}
