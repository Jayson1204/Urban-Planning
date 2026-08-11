<?php

namespace App\Services;

class HousingOccupancyService
{
    private $occupancyRepo;
    private $residentRepo;
    private $housingUnitRepo;

    public function __construct($occupancyRepo, $residentRepo, $housingUnitRepo)
    {
        $this->occupancyRepo = $occupancyRepo;
        $this->residentRepo = $residentRepo;
        $this->housingUnitRepo = $housingUnitRepo;
    }

    public function validateMoveInInput($input)
    {
        $errors = [];

        $residentId = (int)($input['resident_id'] ?? 0);
        if (!$residentId) {
            $errors[] = 'A resident must be selected.';
        } elseif (!$this->residentRepo->find($residentId)) {
            $errors[] = 'Selected resident does not exist.';
        }

        $unitId = (int)($input['unit_id'] ?? 0);
        if (!$unitId) {
            $errors[] = 'A housing unit must be selected.';
        } elseif (!$this->housingUnitRepo->find($unitId)) {
            $errors[] = 'Selected housing unit does not exist.';
        } elseif ($this->occupancyRepo->activeForUnit($unitId)) {
            $errors[] = 'This housing unit already has an active occupant.';
        }

        if (empty($input['move_in_date'])) {
            $errors[] = 'Move-in date is required.';
        }

        return $errors;
    }

    public function moveIn($input)
    {
        $data = [
            'unit_id' => (int)$input['unit_id'],
            'resident_id' => (int)$input['resident_id'],
            'beneficiary_id' => !empty($input['beneficiary_id']) ? (int)$input['beneficiary_id'] : null,
            'move_in_date' => $input['move_in_date'],
            'status' => 'Active',
            'remarks' => trim($input['remarks'] ?? '') ?: null,
        ];
        $newId = $this->occupancyRepo->create($data);
        $this->syncUnitOccupancy($data['unit_id']);
        return $newId;
    }

    public function vacate($occupancyId, $moveOutDate = null)
    {
        $occupancy = $this->occupancyRepo->find($occupancyId);
        if (!$occupancy) {
            return false;
        }
        $this->occupancyRepo->endOccupancy($occupancyId, $moveOutDate ?: date('Y-m-d'));
        $this->syncUnitOccupancy($occupancy['unit_id']);
        return true;
    }

    /**
     * Keep the unit's occupancy_status in step with its active occupancy record,
     * without clobbering manually-set Reserved / Under Maintenance states.
     */
    public function syncUnitOccupancy($unitId)
    {
        if (empty($unitId)) {
            return;
        }
        $unit = $this->housingUnitRepo->find($unitId);
        if (!$unit) {
            return;
        }
        if (in_array($unit['occupancy_status'], ['Reserved', 'Under Maintenance'], true)) {
            return;
        }
        $target = $this->occupancyRepo->activeForUnit($unitId) ? 'Occupied' : 'Vacant';
        if ($unit['occupancy_status'] !== $target) {
            $this->housingUnitRepo->update($unitId, ['occupancy_status' => $target]);
        }
    }
}
