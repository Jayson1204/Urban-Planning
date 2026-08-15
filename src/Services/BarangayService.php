<?php

namespace App\Services;

class BarangayService
{
    private $barangayRepo;

    public function __construct($barangayRepo)
    {
        $this->barangayRepo = $barangayRepo;
    }

    public function detail($barangayId)
    {
        $barangay = $this->barangayRepo->find($barangayId);
        if (!$barangay) {
            return null;
        }
        return array_merge($barangay, $this->barangayRepo->stats($barangay['name']));
    }
}
