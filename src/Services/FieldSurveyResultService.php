<?php

namespace App\Services;

class FieldSurveyResultService
{
    private $resultRepo;
    private $assignmentRepo;

    private const CONDITION_RATINGS = ['Excellent', 'Good', 'Fair', 'Poor', 'Critical'];

    public function __construct($resultRepo, $assignmentRepo)
    {
        $this->resultRepo = $resultRepo;
        $this->assignmentRepo = $assignmentRepo;
    }

    public function validateResultInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('assignment_id', $input)) {
            $assignmentId = (int)($input['assignment_id'] ?? 0);
            if (!$assignmentId) {
                $errors[] = 'Survey assignment is required.';
            } elseif (!$this->assignmentRepo->find($assignmentId)) {
                $errors[] = 'Selected survey assignment does not exist.';
            } elseif ($this->resultRepo->assignmentHasResult($assignmentId, $excludeId)) {
                $errors[] = 'This assignment already has a recorded result.';
            }
        }

        if (!empty($input['condition_rating']) && !in_array($input['condition_rating'], self::CONDITION_RATINGS, true)) {
            $errors[] = 'Invalid condition rating value.';
        }
        if (isset($input['population_count']) && $input['population_count'] !== '' && !ctype_digit((string)$input['population_count'])) {
            $errors[] = 'Population count must be a whole number.';
        }

        return $errors;
    }

    public function createResult($input)
    {
        $data = $this->sanitizeFields($input);
        $data['status'] = 'Active';
        $resultId = $this->resultRepo->create($data);

        // Recording a result implies the fieldwork for this assignment is done.
        $this->assignmentRepo->update((int)$data['assignment_id'], ['assignment_status' => 'Completed']);

        return $resultId;
    }

    public function updateResult($resultId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->resultRepo->update($resultId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'assignment_id', 'survey_date', 'condition_rating', 'population_count',
            'income_bracket', 'findings', 'recommendations', 'additional_notes'
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

        if (array_key_exists('assignment_id', $data) && $data['assignment_id'] !== null) {
            $data['assignment_id'] = (int)$data['assignment_id'];
        }
        if (array_key_exists('population_count', $data) && $data['population_count'] !== null) {
            $data['population_count'] = (int)$data['population_count'];
        }

        return $data;
    }
}
