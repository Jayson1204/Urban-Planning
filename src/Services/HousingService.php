<?php

namespace App\Services;

class HousingService
{
    private $housingUnitRepo;

    private const UNIT_TYPES = ['Single Detached', 'Duplex', 'Row House', 'Medium Rise', 'Studio', 'Other'];
    private const OCCUPANCY_STATUSES = ['Vacant', 'Occupied', 'Reserved', 'Under Maintenance'];

    public function __construct($housingUnitRepo)
    {
        $this->housingUnitRepo = $housingUnitRepo;
    }

    public function validateHousingInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('unit_code', $input)) {
            $code = trim($input['unit_code'] ?? '');
            if ($code === '') {
                $errors[] = 'Unit code is required.';
            } elseif ($this->housingUnitRepo->codeExists($code, $excludeId)) {
                $errors[] = 'Unit code already exists.';
            }
        }
        if (!empty($input['unit_type']) && !in_array($input['unit_type'], self::UNIT_TYPES, true)) {
            $errors[] = 'Invalid unit type value.';
        }
        if (!empty($input['occupancy_status']) && !in_array($input['occupancy_status'], self::OCCUPANCY_STATUSES, true)) {
            $errors[] = 'Invalid occupancy status value.';
        }
        if (isset($input['floor_area']) && $input['floor_area'] !== '' && !is_numeric($input['floor_area'])) {
            $errors[] = 'Floor area must be a number.';
        }
        if (isset($input['bedrooms']) && $input['bedrooms'] !== '' && (!ctype_digit((string)$input['bedrooms']))) {
            $errors[] = 'Bedrooms must be a whole number.';
        }
        if (isset($input['monthly_amortization']) && $input['monthly_amortization'] !== '' && !is_numeric($input['monthly_amortization'])) {
            $errors[] = 'Monthly amortization must be a number.';
        }

        return $errors;
    }

    public function createHousingUnit($input)
    {
        $data = $this->sanitizeFields($input);
        if (empty($data['unit_type'])) {
            $data['unit_type'] = 'Other';
        }
        if (empty($data['occupancy_status'])) {
            $data['occupancy_status'] = 'Vacant';
        }
        $data['status'] = 'Active';
        return $this->housingUnitRepo->create($data);
    }

    public function updateHousingUnit($unitId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->housingUnitRepo->update($unitId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'unit_code', 'project_name', 'unit_type', 'barangay', 'street_address',
            'floor_area', 'bedrooms', 'monthly_amortization', 'occupancy_status', 'remarks'
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
