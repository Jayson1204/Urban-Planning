<?php

namespace App\Services;

class InfrastructureRecordService
{
    private $infraRepo;

    private const TYPES = ['Road', 'Bridge', 'Drainage System', 'Water Supply', 'Street Lighting', 'Public Facility', 'Other'];
    private const CONDITIONS = ['Good', 'Needs Repair', 'Under Construction', 'Non-Functional'];

    public function __construct($infraRepo)
    {
        $this->infraRepo = $infraRepo;
    }

    public function validateRecordInput($input, $isUpdate = false)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('infrastructure_name', $input)) {
            if (trim($input['infrastructure_name'] ?? '') === '') {
                $errors[] = 'Infrastructure name is required.';
            }
        }
        if (!empty($input['infrastructure_type']) && !in_array($input['infrastructure_type'], self::TYPES, true)) {
            $errors[] = 'Invalid infrastructure type value.';
        }
        if (!empty($input['condition_status']) && !in_array($input['condition_status'], self::CONDITIONS, true)) {
            $errors[] = 'Invalid condition status value.';
        }

        return $errors;
    }

    public function createRecord($input)
    {
        $data = $this->sanitizeFields($input);
        if (empty($data['infrastructure_type'])) {
            $data['infrastructure_type'] = 'Other';
        }
        if (empty($data['condition_status'])) {
            $data['condition_status'] = 'Good';
        }
        $data['status'] = 'Active';
        return $this->infraRepo->create($data);
    }

    public function updateRecord($recordId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->infraRepo->update($recordId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'project_id', 'infrastructure_type', 'infrastructure_name', 'barangay',
            'location_details', 'condition_status', 'completion_date', 'remarks'
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
