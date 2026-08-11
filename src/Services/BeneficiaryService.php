<?php

namespace App\Services;

class BeneficiaryService
{
    private $beneficiaryRepo;
    private $residentRepo;
    private $housingUnitRepo;

    private const BENEFICIARY_STATUSES = ['Applicant', 'Qualified', 'Awarded', 'Disqualified', 'Cancelled'];
    private const CATEGORIES = ['Informal Settler', 'Calamity Victim', 'Senior Citizen', 'PWD', 'Government Employee', 'Other'];

    public function __construct($beneficiaryRepo, $residentRepo, $housingUnitRepo)
    {
        $this->beneficiaryRepo = $beneficiaryRepo;
        $this->residentRepo = $residentRepo;
        $this->housingUnitRepo = $housingUnitRepo;
    }

    public function validateBeneficiaryInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('resident_id', $input)) {
            $residentId = (int)($input['resident_id'] ?? 0);
            if (!$residentId) {
                $errors[] = 'A resident must be selected.';
            } elseif (!$this->residentRepo->find($residentId)) {
                $errors[] = 'Selected resident does not exist.';
            }
        }
        if (!empty($input['beneficiary_status']) && !in_array($input['beneficiary_status'], self::BENEFICIARY_STATUSES, true)) {
            $errors[] = 'Invalid beneficiary status value.';
        }
        if (!empty($input['category']) && !in_array($input['category'], self::CATEGORIES, true)) {
            $errors[] = 'Invalid category value.';
        }
        if (isset($input['monthly_income']) && $input['monthly_income'] !== '' && !is_numeric($input['monthly_income'])) {
            $errors[] = 'Monthly income must be a number.';
        }
        if (isset($input['family_size']) && $input['family_size'] !== '' && !ctype_digit((string)$input['family_size'])) {
            $errors[] = 'Family size must be a whole number.';
        }

        // Awarding requires a unit + award date, and the unit must be free.
        if (($input['beneficiary_status'] ?? '') === 'Awarded') {
            $unitId = (int)($input['unit_id'] ?? 0);
            if (!$unitId) {
                $errors[] = 'A housing unit is required to award a beneficiary.';
            } else {
                if (!$this->housingUnitRepo->find($unitId)) {
                    $errors[] = 'Selected housing unit does not exist.';
                } elseif ($this->beneficiaryRepo->hasActiveAward($unitId, $excludeId)) {
                    $errors[] = 'This housing unit already has an awarded beneficiary.';
                }
            }
            if (empty($input['award_date'])) {
                $errors[] = 'Award date is required when awarding a beneficiary.';
            }
        }

        return $errors;
    }

    public function createBeneficiary($input)
    {
        $data = $this->sanitizeFields($input);
        if (empty($data['beneficiary_status'])) {
            $data['beneficiary_status'] = 'Applicant';
        }
        if (empty($data['category'])) {
            $data['category'] = 'Other';
        }
        if (empty($data['application_date'])) {
            $data['application_date'] = date('Y-m-d');
        }
        $data['status'] = 'Active';

        $newId = $this->beneficiaryRepo->create($data);
        $this->syncUnitOccupancy($data['unit_id'] ?? null);
        return $newId;
    }

    public function updateBeneficiary($beneficiaryId, $input)
    {
        $existing = $this->beneficiaryRepo->find($beneficiaryId);
        $oldUnitId = $existing['unit_id'] ?? null;

        $data = $this->sanitizeFields($input, true);
        $this->beneficiaryRepo->update($beneficiaryId, $data);

        $newUnitId = array_key_exists('unit_id', $data) ? $data['unit_id'] : $oldUnitId;
        $this->syncUnitOccupancy($oldUnitId);
        if ((int)$newUnitId !== (int)$oldUnitId) {
            $this->syncUnitOccupancy($newUnitId);
        }
        return true;
    }

    public function setStatus($beneficiaryId, $status)
    {
        $existing = $this->beneficiaryRepo->find($beneficiaryId);
        $this->beneficiaryRepo->setStatus($beneficiaryId, $status);
        if ($existing) {
            $this->syncUnitOccupancy($existing['unit_id'] ?? null);
        }
    }

    /**
     * Keep the unit's occupancy in step with its awarded beneficiary, without
     * clobbering manually-set Reserved / Under Maintenance states.
     */
    private function syncUnitOccupancy($unitId)
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
        $target = $this->beneficiaryRepo->hasActiveAward($unitId) ? 'Occupied' : 'Vacant';
        if ($unit['occupancy_status'] !== $target) {
            $this->housingUnitRepo->update($unitId, ['occupancy_status' => $target]);
        }
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'resident_id', 'household_id', 'unit_id', 'application_date', 'award_date',
            'beneficiary_status', 'category', 'monthly_income', 'family_size', 'remarks'
        ];

        $data = [];
        foreach ($allowed as $field) {
            if ($partial && !array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $data[$field] = ($value === '') ? null : $value;
        }
        return $data;
    }
}
