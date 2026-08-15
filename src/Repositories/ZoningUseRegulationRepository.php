<?php

namespace App\Repositories;

class ZoningUseRegulationRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findRegulation($zoneClassification, $useCategory)
    {
        $rows = $this->db->query(
            "SELECT * FROM zoning_use_regulations WHERE zone_classification = :zone AND use_category = :use",
            ['zone' => $zoneClassification, 'use' => $useCategory]
        );
        return $rows[0] ?? null;
    }

    public function all()
    {
        return $this->db->query(
            "SELECT * FROM zoning_use_regulations ORDER BY zone_classification ASC, use_category ASC"
        );
    }
}
