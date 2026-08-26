<?php

namespace App\Services;

class BarangayService
{
    private $barangayRepo;
    private $subdivisionRepo;
    private $housingProjectRepo;
    private $buildingRepo;

    public function __construct($barangayRepo, $subdivisionRepo = null, $housingProjectRepo = null, $buildingRepo = null)
    {
        $this->barangayRepo = $barangayRepo;
        $this->subdivisionRepo = $subdivisionRepo;
        $this->housingProjectRepo = $housingProjectRepo;
        $this->buildingRepo = $buildingRepo;
    }

    public function detail($barangayId)
    {
        $barangay = $this->barangayRepo->find($barangayId);
        if (!$barangay) {
            return null;
        }

        $detail = array_merge($barangay, $this->barangayRepo->stats($barangay['name']));

        $detail['subdivision_count'] = $this->subdivisionRepo ? $this->subdivisionRepo->countByBarangay($barangayId) : 0;
        $detail['housing_project_count'] = $this->housingProjectRepo ? $this->housingProjectRepo->countByBarangay($barangayId) : 0;
        $detail['building_count'] = $this->buildingRepo ? $this->buildingRepo->countByBarangay($barangayId) : 0;

        return $detail;
    }
}
