<?php

namespace App\Services;

class BeneficiaryService
{
    private $beneficiaryRepo;
    private $residentRepo;
    private $housingUnitRepo;
    private $householdRepo;

    private const BENEFICIARY_STATUSES = ['Applicant', 'Qualified', 'Awarded', 'Disqualified', 'Cancelled'];
    private const OPEN_BENEFICIARY_STATUSES = ['Applicant', 'Qualified', 'Awarded'];
    private const CATEGORIES = ['Informal Settler', 'Calamity Victim', 'Senior Citizen', 'PWD', 'Government Employee', 'Other'];
    private const AMORTIZATION_STATUSES = ['Not Started', 'Current', 'Delinquent', 'Completed'];

    // Eligibility scoring weights (0-100 total). Kept as constants for now; a
    // configurable-weights admin UI is a documented follow-up, not built yet.
    private const INCOME_WEIGHT = 35;
    private const FAMILY_SIZE_WEIGHT = 15;
    private const CATEGORY_WEIGHT = 25;
    private const TENURE_WEIGHT = 25;
    private const FAMILY_SIZE_CAP = 8;
    // Rough NCR monthly poverty income threshold used as the scoring baseline.
    private const POVERTY_INCOME_THRESHOLD = 12030;

    private const CATEGORY_SCORES = [
        'Senior Citizen' => 25,
        'PWD' => 25,
        'Calamity Victim' => 20,
        'Informal Settler' => 15,
        'Government Employee' => 5,
        'Other' => 0,
    ];

    private const TENURE_SCORES = [
        'Informal Settler' => 25,
        'Renting' => 15,
        'Other' => 10,
        'Owned' => 0,
    ];

    public function __construct($beneficiaryRepo, $residentRepo, $housingUnitRepo, $householdRepo)
    {
        $this->beneficiaryRepo = $beneficiaryRepo;
        $this->residentRepo = $residentRepo;
        $this->housingUnitRepo = $housingUnitRepo;
        $this->householdRepo = $householdRepo;
    }

    public function validateBeneficiaryInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];
        $existing = ($isUpdate && $excludeId) ? $this->beneficiaryRepo->find($excludeId) : null;

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
        if (!empty($input['amortization_status']) && !in_array($input['amortization_status'], self::AMORTIZATION_STATUSES, true)) {
            $errors[] = 'Invalid amortization status value.';
        }
        if (isset($input['monthly_income']) && $input['monthly_income'] !== '' && !is_numeric($input['monthly_income'])) {
            $errors[] = 'Monthly income must be a number.';
        }
        if (isset($input['family_size']) && $input['family_size'] !== '' && !ctype_digit((string)$input['family_size'])) {
            $errors[] = 'Family size must be a whole number.';
        }

        // Duplicate detection: at most one open application per resident, and
        // at most one per household (matches "one award per household" intent).
        $effectiveResidentId = array_key_exists('resident_id', $input)
            ? (int)$input['resident_id']
            : (int)($existing['resident_id'] ?? 0);
        $effectiveHouseholdId = array_key_exists('household_id', $input)
            ? (int)$input['household_id']
            : (int)($existing['household_id'] ?? 0);
        $effectiveStatus = array_key_exists('beneficiary_status', $input)
            ? $input['beneficiary_status']
            : ($existing['beneficiary_status'] ?? 'Applicant');

        if (in_array($effectiveStatus, self::OPEN_BENEFICIARY_STATUSES, true)) {
            if ($this->beneficiaryRepo->hasActiveApplicationForResident($effectiveResidentId, $excludeId)) {
                $errors[] = 'This resident already has an active housing beneficiary application.';
            } elseif ($effectiveHouseholdId && $this->beneficiaryRepo->hasActiveApplicationForHousehold($effectiveHouseholdId, $excludeId)) {
                $errors[] = 'This household already has an active housing beneficiary application.';
            }
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
        if (empty($data['amortization_status'])) {
            $data['amortization_status'] = 'Not Started';
        }
        if (empty($data['application_date'])) {
            $data['application_date'] = date('Y-m-d');
        }
        $data['status'] = 'Active';
        $data['eligibility_score'] = $this->computeEligibilityScore($data);

        $newId = $this->beneficiaryRepo->create($data);
        $this->syncUnitOccupancy($data['unit_id'] ?? null);
        return $newId;
    }

    public function updateBeneficiary($beneficiaryId, $input)
    {
        $existing = $this->beneficiaryRepo->find($beneficiaryId);
        $oldUnitId = $existing['unit_id'] ?? null;

        $data = $this->sanitizeFields($input, true);
        if (array_key_exists('amortization_status', $data) && empty($data['amortization_status'])) {
            $data['amortization_status'] = 'Not Started';
        }

        // Score depends on income/family size/category/household, so recompute
        // using the merged view of existing + incoming fields on every update.
        $scoringInput = array_merge($existing ?? [], $data);
        $data['eligibility_score'] = $this->computeEligibilityScore($scoringInput);

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

    /**
     * Weighted eligibility score (0-100) against configurable criteria: income,
     * family size, vulnerability category, and household tenure (RA 7279-aligned).
     * $data is expected to already be sanitized/merged-with-existing field values.
     */
    private function computeEligibilityScore($data)
    {
        $score = 0.0;

        $income = $data['monthly_income'] ?? null;
        if ($income !== null && $income !== '' && is_numeric($income)) {
            $income = (float)$income;
            $threshold = self::POVERTY_INCOME_THRESHOLD;
            if ($income <= $threshold) {
                $score += self::INCOME_WEIGHT;
            } elseif ($income < 2 * $threshold) {
                $ratio = 1 - (($income - $threshold) / $threshold);
                $score += self::INCOME_WEIGHT * max(0, $ratio);
            }
        }

        $familySize = $data['family_size'] ?? null;
        if ($familySize !== null && $familySize !== '' && ctype_digit((string)$familySize)) {
            $ratio = min((int)$familySize, self::FAMILY_SIZE_CAP) / self::FAMILY_SIZE_CAP;
            $score += self::FAMILY_SIZE_WEIGHT * $ratio;
        }

        $category = $data['category'] ?? null;
        if ($category && isset(self::CATEGORY_SCORES[$category])) {
            $score += self::CATEGORY_SCORES[$category];
        }

        $householdId = $data['household_id'] ?? null;
        if (!empty($householdId)) {
            $household = $this->householdRepo->find($householdId);
            $tenure = $household['household_type'] ?? null;
            if ($tenure && isset(self::TENURE_SCORES[$tenure])) {
                $score += self::TENURE_SCORES[$tenure];
            }
        }

        return round(min(100, max(0, $score)), 2);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'resident_id', 'household_id', 'unit_id', 'application_date', 'award_date',
            'beneficiary_status', 'category', 'monthly_income', 'family_size',
            'amortization_status', 'remarks'
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
