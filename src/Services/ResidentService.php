<?php

namespace App\Services;

class ResidentService
{
    private $residentRepo;
    private $householdRepo;
    private $documentRepo;

    public function __construct($residentRepo, $householdRepo, $documentRepo)
    {
        $this->residentRepo = $residentRepo;
        $this->householdRepo = $householdRepo;
        $this->documentRepo = $documentRepo;
    }

    public function validateResidentInput($input, $isUpdate = false)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('first_name', $input)) {
            if (empty(trim($input['first_name'] ?? ''))) {
                $errors[] = 'First name is required.';
            }
        }
        if (!$isUpdate || array_key_exists('last_name', $input)) {
            if (empty(trim($input['last_name'] ?? ''))) {
                $errors[] = 'Last name is required.';
            }
        }
        if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is invalid.';
        }
        if (!empty($input['gender']) && !in_array($input['gender'], ['Male', 'Female', 'Other'], true)) {
            $errors[] = 'Invalid gender value.';
        }
        if (!empty($input['civil_status']) && !in_array($input['civil_status'], ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'], true)) {
            $errors[] = 'Invalid civil status value.';
        }

        return $errors;
    }

    public function createResident($input)
    {
        $data = $this->sanitizeResidentFields($input);
        $data['status'] = 'Active';
        return $this->residentRepo->create($data);
    }

    public function updateResident($residentId, $input)
    {
        $data = $this->sanitizeResidentFields($input, true);
        return $this->residentRepo->update($residentId, $data);
    }

    private function sanitizeResidentFields($input, $partial = false)
    {
        $allowed = [
            'household_id', 'relationship_to_head', 'first_name', 'middle_name', 'last_name',
            'suffix', 'birth_date', 'gender', 'civil_status', 'contact_number', 'email',
            'barangay', 'street_address', 'occupation'
        ];

        $data = [];
        foreach ($allowed as $field) {
            if ($partial && !array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field] ?? null;
            $data[$field] = ($value === '' ) ? null : $value;
        }
        return $data;
    }

    public function getHouseholdMembers($householdId)
    {
        return $this->householdRepo->getMembers($householdId);
    }
}
