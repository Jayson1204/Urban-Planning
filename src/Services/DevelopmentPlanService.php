<?php

namespace App\Services;

class DevelopmentPlanService
{
    private $developmentPlanRepo;

    private const PLAN_TYPES = ['Comprehensive Land Use Plan', 'Zoning Plan', 'Infrastructure Plan', 'Development Framework', 'Other'];
    private const PLAN_STATUSES = ['Draft', 'Active', 'Completed', 'Archived'];

    public function __construct($developmentPlanRepo)
    {
        $this->developmentPlanRepo = $developmentPlanRepo;
    }

    public function validatePlanInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('plan_code', $input)) {
            $code = trim($input['plan_code'] ?? '');
            if ($code === '') {
                $errors[] = 'Plan code is required.';
            } elseif ($this->developmentPlanRepo->codeExists($code, $excludeId)) {
                $errors[] = 'Plan code already exists.';
            }
        }
        if (!$isUpdate || array_key_exists('plan_title', $input)) {
            $title = trim($input['plan_title'] ?? '');
            if ($title === '') {
                $errors[] = 'Plan title is required.';
            }
        }
        if (!empty($input['plan_type']) && !in_array($input['plan_type'], self::PLAN_TYPES, true)) {
            $errors[] = 'Invalid plan type value.';
        }
        if (!empty($input['plan_status']) && !in_array($input['plan_status'], self::PLAN_STATUSES, true)) {
            $errors[] = 'Invalid plan status value.';
        }
        if (isset($input['budget_allocation']) && $input['budget_allocation'] !== '' && !is_numeric($input['budget_allocation'])) {
            $errors[] = 'Budget allocation must be a number.';
        }
        if (!empty($input['start_date']) && !empty($input['end_date']) && strtotime($input['end_date']) < strtotime($input['start_date'])) {
            $errors[] = 'End date cannot be earlier than start date.';
        }

        return $errors;
    }

    public function createDevelopmentPlan($input)
    {
        $data = $this->sanitizeFields($input);
        if (empty($data['plan_type'])) {
            $data['plan_type'] = 'Other';
        }
        if (empty($data['plan_status'])) {
            $data['plan_status'] = 'Draft';
        }
        $data['status'] = 'Active';
        return $this->developmentPlanRepo->create($data);
    }

    public function updateDevelopmentPlan($planId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->developmentPlanRepo->update($planId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'plan_code', 'plan_title', 'plan_type', 'barangay', 'coverage_area',
            'lead_department', 'budget_allocation', 'start_date', 'end_date',
            'plan_status', 'description'
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
