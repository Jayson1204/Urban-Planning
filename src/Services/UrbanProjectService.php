<?php

namespace App\Services;

class UrbanProjectService
{
    private $projectRepo;

    private const PROJECT_TYPES = ['Road', 'Drainage', 'Public Building', 'Utility', 'Park/Open Space', 'Other'];
    private const PROJECT_STATUSES = ['Planned', 'Ongoing', 'Completed', 'Delayed', 'Cancelled'];

    public function __construct($projectRepo)
    {
        $this->projectRepo = $projectRepo;
    }

    public function validateProjectInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('project_code', $input)) {
            $code = trim($input['project_code'] ?? '');
            if ($code === '') {
                $errors[] = 'Project code is required.';
            } elseif ($this->projectRepo->codeExists($code, $excludeId)) {
                $errors[] = 'Project code already exists.';
            }
        }
        if (!$isUpdate || array_key_exists('project_title', $input)) {
            if (trim($input['project_title'] ?? '') === '') {
                $errors[] = 'Project title is required.';
            }
        }
        if (!empty($input['project_type']) && !in_array($input['project_type'], self::PROJECT_TYPES, true)) {
            $errors[] = 'Invalid project type value.';
        }
        if (!empty($input['project_status']) && !in_array($input['project_status'], self::PROJECT_STATUSES, true)) {
            $errors[] = 'Invalid project status value.';
        }
        if (isset($input['budget']) && $input['budget'] !== '' && !is_numeric($input['budget'])) {
            $errors[] = 'Budget must be a number.';
        }
        if (!empty($input['start_date']) && !empty($input['target_completion_date'])
            && strtotime($input['target_completion_date']) < strtotime($input['start_date'])) {
            $errors[] = 'Target completion date cannot be earlier than the start date.';
        }

        return $errors;
    }

    public function createProject($input)
    {
        $data = $this->sanitizeFields($input);
        if (empty($data['project_type'])) {
            $data['project_type'] = 'Other';
        }
        if (empty($data['project_status'])) {
            $data['project_status'] = 'Planned';
        }
        $data['status'] = 'Active';
        return $this->projectRepo->create($data);
    }

    public function updateProject($projectId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->projectRepo->update($projectId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'plan_id', 'project_code', 'project_title', 'project_type', 'barangay', 'coverage_area',
            'contractor', 'budget', 'start_date', 'target_completion_date', 'actual_completion_date',
            'project_status', 'description'
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
