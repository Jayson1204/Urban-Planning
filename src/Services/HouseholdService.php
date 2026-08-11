<?php

namespace App\Services;

class HouseholdService
{
    private $householdRepo;

    public function __construct($householdRepo)
    {
        $this->householdRepo = $householdRepo;
    }

    public function validateHouseholdInput($input, $isUpdate = false)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('barangay', $input)) {
            if (empty(trim($input['barangay'] ?? ''))) {
                $errors[] = 'Barangay is required.';
            }
        }
        if (!$isUpdate || array_key_exists('street_address', $input)) {
            if (empty(trim($input['street_address'] ?? ''))) {
                $errors[] = 'Street address is required.';
            }
        }
        if (!empty($input['household_type']) && !in_array($input['household_type'], ['Owned', 'Renting', 'Informal Settler', 'Other'], true)) {
            $errors[] = 'Invalid household type.';
        }

        return $errors;
    }

    public function createHousehold($input)
    {
        $data = $this->sanitizeHouseholdFields($input);
        $data['status'] = 'Active';
        return $this->householdRepo->create($data);
    }

    public function updateHousehold($householdId, $input)
    {
        $data = $this->sanitizeHouseholdFields($input, true);
        return $this->householdRepo->update($householdId, $data);
    }

    private function sanitizeHouseholdFields($input, $partial = false)
    {
        $allowed = ['household_number', 'barangay', 'street_address', 'household_type'];

        $data = [];
        foreach ($allowed as $field) {
            if ($partial && !array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field] ?? null;
            $data[$field] = ($value === '') ? null : $value;
        }
        return $data;
    }
}
