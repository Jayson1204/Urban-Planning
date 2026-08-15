<?php

namespace App\Services;

class FieldSurveyFormService
{
    private $formRepo;

    private const CATEGORIES = ['Household Assessment', 'Infrastructure Condition', 'Land Use Assessment', 'Socioeconomic Survey', 'Other'];
    private const SUBJECT_TYPES = ['Resident', 'Household', 'Site'];

    public function __construct($formRepo)
    {
        $this->formRepo = $formRepo;
    }

    public function validateFormInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('form_code', $input)) {
            $code = trim($input['form_code'] ?? '');
            if ($code === '') {
                $errors[] = 'Form code is required.';
            } elseif ($this->formRepo->codeExists($code, $excludeId)) {
                $errors[] = 'Form code already exists.';
            }
        }
        if (!$isUpdate || array_key_exists('form_title', $input)) {
            $title = trim($input['form_title'] ?? '');
            if ($title === '') {
                $errors[] = 'Form title is required.';
            }
        }
        if (!empty($input['category']) && !in_array($input['category'], self::CATEGORIES, true)) {
            $errors[] = 'Invalid category value.';
        }
        if (!empty($input['subject_type']) && !in_array($input['subject_type'], self::SUBJECT_TYPES, true)) {
            $errors[] = 'Invalid subject type value.';
        }

        return $errors;
    }

    public function createForm($input)
    {
        $data = $this->sanitizeFields($input);
        if (empty($data['category'])) {
            $data['category'] = 'Other';
        }
        if (empty($data['subject_type'])) {
            $data['subject_type'] = 'Site';
        }
        $data['status'] = 'Active';
        return $this->formRepo->create($data);
    }

    public function updateForm($formId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->formRepo->update($formId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = ['form_code', 'form_title', 'category', 'subject_type', 'description'];

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
