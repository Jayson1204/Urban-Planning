<?php

namespace App\Services;

class HousingProjectService
{
    private $housingProjectRepo;

    private const PROJECT_STATUSES = ['Existing', 'Ongoing', 'Proposed', 'Completed'];

    public function __construct($housingProjectRepo)
    {
        $this->housingProjectRepo = $housingProjectRepo;
    }

    public function validateInput($input)
    {
        $errors = [];

        if (trim($input['name'] ?? '') === '') {
            $errors[] = 'Housing project name is required.';
        }
        if (trim($input['source'] ?? '') === '') {
            $errors[] = 'Source is required.';
        }
        if (!is_numeric($input['latitude'] ?? null) || $input['latitude'] < -90 || $input['latitude'] > 90) {
            $errors[] = 'Latitude must be a number between -90 and 90.';
        }
        if (!is_numeric($input['longitude'] ?? null) || $input['longitude'] < -180 || $input['longitude'] > 180) {
            $errors[] = 'Longitude must be a number between -180 and 180.';
        }
        if (!empty($input['project_status']) && !in_array($input['project_status'], self::PROJECT_STATUSES, true)) {
            $errors[] = 'Invalid project status value.';
        }
        if (isset($input['units']) && $input['units'] !== '' && !ctype_digit((string)$input['units'])) {
            $errors[] = 'Units must be a whole number.';
        }
        if (!empty($input['boundary_geojson'])) {
            $geometry = is_string($input['boundary_geojson']) ? json_decode($input['boundary_geojson'], true) : $input['boundary_geojson'];
            if (!is_array($geometry) || empty($geometry['type']) || !in_array($geometry['type'], ['Polygon', 'MultiPolygon'], true) || !isset($geometry['coordinates'])) {
                $errors[] = 'Boundary must be a valid GeoJSON Polygon or MultiPolygon geometry.';
            }
        }

        return $errors;
    }

    public function create($input)
    {
        $data = $this->sanitizeFields($input);
        $data['status'] = 'Active';
        return $this->housingProjectRepo->create($data);
    }

    public function update($housingProjectId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->housingProjectRepo->update($housingProjectId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'name', 'barangay_id', 'barangay', 'latitude', 'longitude', 'boundary_geojson',
            'units', 'project_status', 'developer', 'source', 'description',
        ];

        $data = [];
        foreach ($allowed as $field) {
            if ($partial && !array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field] ?? null;
            if ($field === 'boundary_geojson') {
                $data[$field] = empty($value) ? null : (is_string($value) ? $value : json_encode($value));
                continue;
            }
            if (is_string($value)) {
                $value = trim($value);
            }
            $data[$field] = ($value === '') ? null : $value;
        }
        return $data;
    }
}
