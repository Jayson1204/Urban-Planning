<?php

namespace App\Repositories;

class FieldSurveyPhotoRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function forResult($resultId)
    {
        return $this->db->query(
            "SELECT * FROM field_survey_photos WHERE result_id = :id ORDER BY uploaded_at DESC",
            ['id' => $resultId]
        );
    }

    public function find($photoId)
    {
        $rows = $this->db->query(
            "SELECT * FROM field_survey_photos WHERE photo_id = :id",
            ['id' => $photoId]
        );
        return $rows[0] ?? null;
    }

    public function create($data)
    {
        return $this->db->insert('field_survey_photos', $data);
    }

    public function delete($photoId)
    {
        return $this->db->delete('field_survey_photos', ['photo_id' => $photoId]);
    }
}
