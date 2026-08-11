<?php

namespace App\Services;

class HousingRelocationService
{
    private $relocationRepo;
    private $occupancyRepo;
    private $occupancyService;
    private $residentRepo;
    private $housingUnitRepo;

    private const REASONS = ['Overcrowding', 'Safety Issue', 'Unit Upgrade', 'Personal Request', 'Government Directive', 'Other'];

    public function __construct($relocationRepo, $occupancyRepo, $occupancyService, $residentRepo, $housingUnitRepo)
    {
        $this->relocationRepo = $relocationRepo;
        $this->occupancyRepo = $occupancyRepo;
        $this->occupancyService = $occupancyService;
        $this->residentRepo = $residentRepo;
        $this->housingUnitRepo = $housingUnitRepo;
    }

    public function validateRelocationInput($input)
    {
        $errors = [];

        $residentId = (int)($input['resident_id'] ?? 0);
        if (!$residentId) {
            $errors[] = 'A resident must be selected.';
        } elseif (!$this->residentRepo->find($residentId)) {
            $errors[] = 'Selected resident does not exist.';
        }

        $toUnitId = (int)($input['to_unit_id'] ?? 0);
        if (!$toUnitId) {
            $errors[] = 'A destination housing unit must be selected.';
        } elseif (!$this->housingUnitRepo->find($toUnitId)) {
            $errors[] = 'Destination housing unit does not exist.';
        } elseif ($this->occupancyRepo->activeForUnit($toUnitId)) {
            $errors[] = 'The destination unit already has an active occupant.';
        }

        $fromUnitId = (int)($input['from_unit_id'] ?? 0);
        if ($fromUnitId && $fromUnitId === $toUnitId) {
            $errors[] = 'Destination unit must be different from the source unit.';
        }

        if (!empty($input['reason']) && !in_array($input['reason'], self::REASONS, true)) {
            $errors[] = 'Invalid relocation reason.';
        }
        if (empty($input['relocation_date'])) {
            $errors[] = 'Relocation date is required.';
        }

        return $errors;
    }

    public function relocate($input)
    {
        $residentId = (int)$input['resident_id'];
        $toUnitId = (int)$input['to_unit_id'];
        $fromUnitId = !empty($input['from_unit_id']) ? (int)$input['from_unit_id'] : null;
        $relocationDate = $input['relocation_date'];

        $data = [
            'resident_id' => $residentId,
            'from_unit_id' => $fromUnitId,
            'to_unit_id' => $toUnitId,
            'relocation_date' => $relocationDate,
            'reason' => $input['reason'] ?? 'Other',
            'remarks' => trim($input['remarks'] ?? '') ?: null,
            'status' => 'Active',
        ];
        $newId = $this->relocationRepo->create($data);

        // End the resident's active occupancy on the source unit, if any is on record.
        $activeOccupancy = $this->occupancyRepo->activeForResident($residentId);
        if ($activeOccupancy) {
            $this->occupancyService->vacate($activeOccupancy['occupancy_id'], $relocationDate);
        }

        // Start a fresh occupancy on the destination unit.
        $this->occupancyService->moveIn([
            'unit_id' => $toUnitId,
            'resident_id' => $residentId,
            'beneficiary_id' => $activeOccupancy['beneficiary_id'] ?? null,
            'move_in_date' => $relocationDate,
            'remarks' => 'Relocated from ' . ($fromUnitId ? 'unit #' . $fromUnitId : 'a prior residence') . '.',
        ]);

        return $newId;
    }

    public function setStatus($relocationId, $status)
    {
        return $this->relocationRepo->setStatus($relocationId, $status);
    }
}
