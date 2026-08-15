<?php

namespace App\Services;

class FieldSurveyAssignmentService
{
    private $assignmentRepo;
    private $formRepo;
    private $residentRepo;
    private $householdRepo;

    private const SUBJECT_TYPES = ['Resident', 'Household', 'Site'];
    private const ASSIGNMENT_STATUSES = ['Pending', 'In Progress', 'Completed', 'Cancelled'];

    public function __construct($assignmentRepo, $formRepo, $residentRepo, $householdRepo)
    {
        $this->assignmentRepo = $assignmentRepo;
        $this->formRepo = $formRepo;
        $this->residentRepo = $residentRepo;
        $this->householdRepo = $householdRepo;
    }

    public function validateAssignmentInput($input, $isUpdate = false)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('form_id', $input)) {
            $formId = (int)($input['form_id'] ?? 0);
            if (!$formId) {
                $errors[] = 'Survey form is required.';
            } elseif (!$this->formRepo->find($formId)) {
                $errors[] = 'Selected survey form does not exist.';
            }
        }

        $subjectType = $input['subject_type'] ?? null;
        if (!$isUpdate || array_key_exists('subject_type', $input)) {
            if (empty($subjectType) || !in_array($subjectType, self::SUBJECT_TYPES, true)) {
                $errors[] = 'A valid subject type is required.';
            } elseif ($subjectType === 'Site') {
                if (empty(trim($input['site_label'] ?? ''))) {
                    $errors[] = 'Site label is required when subject type is Site.';
                }
            } else {
                $subjectId = (int)($input['subject_id'] ?? 0);
                if (!$subjectId) {
                    $errors[] = 'A ' . strtolower($subjectType) . ' must be selected.';
                } elseif ($subjectType === 'Resident' && !$this->residentRepo->find($subjectId)) {
                    $errors[] = 'Selected resident does not exist.';
                } elseif ($subjectType === 'Household' && !$this->householdRepo->find($subjectId)) {
                    $errors[] = 'Selected household does not exist.';
                }
            }
        }

        if (!empty($input['assignment_status']) && !in_array($input['assignment_status'], self::ASSIGNMENT_STATUSES, true)) {
            $errors[] = 'Invalid assignment status value.';
        }

        return $errors;
    }

    public function createAssignment($input)
    {
        $data = $this->sanitizeFields($input);
        if (empty($data['assignment_status'])) {
            $data['assignment_status'] = 'Pending';
        }
        $data['status'] = 'Active';
        return $this->assignmentRepo->create($data);
    }

    public function updateAssignment($assignmentId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->assignmentRepo->update($assignmentId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'form_id', 'subject_type', 'subject_id', 'site_label', 'site_address',
            'assigned_to', 'due_date', 'assignment_status', 'remarks'
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

        // Clear the fields that don't apply to the chosen subject type.
        if (array_key_exists('subject_type', $data)) {
            if ($data['subject_type'] === 'Site') {
                $data['subject_id'] = null;
            } else {
                $data['site_label'] = null;
                $data['site_address'] = null;
            }
        }

        if (array_key_exists('form_id', $data) && $data['form_id'] !== null) {
            $data['form_id'] = (int)$data['form_id'];
        }
        if (array_key_exists('subject_id', $data) && $data['subject_id'] !== null) {
            $data['subject_id'] = (int)$data['subject_id'];
        }

        return $data;
    }
}
