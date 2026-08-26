<?php

namespace App\Services;

class SubdivisionService
{
    private $subdivisionRepo;

    public function __construct($subdivisionRepo)
    {
        $this->subdivisionRepo = $subdivisionRepo;
    }

    public function validateInput($input)
    {
        $errors = [];

        if (trim($input['name'] ?? '') === '') {
            $errors[] = 'Subdivision name is required.';
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
        if (!empty($input['boundary_geojson'])) {
            $geometry = is_string($input['boundary_geojson']) ? json_decode($input['boundary_geojson'], true) : $input['boundary_geojson'];
            if (!$this->isValidPolygonGeometry($geometry)) {
                $errors[] = 'Boundary must be a valid GeoJSON Polygon or MultiPolygon geometry.';
            }
        }

        return $errors;
    }

    public function isValidPolygonGeometry($geometry)
    {
        if (!is_array($geometry) || empty($geometry['type']) || !isset($geometry['coordinates'])) {
            return false;
        }
        return in_array($geometry['type'], ['Polygon', 'MultiPolygon'], true) && is_array($geometry['coordinates']);
    }

    public function create($input)
    {
        $data = $this->sanitizeFields($input);
        $data['status'] = 'Active';
        return $this->subdivisionRepo->create($data);
    }

    public function update($subdivisionId, $input)
    {
        $data = $this->sanitizeFields($input, true);
        return $this->subdivisionRepo->update($subdivisionId, $data);
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'name', 'barangay_id', 'barangay', 'latitude', 'longitude',
            'boundary_geojson', 'subdivision_type', 'subdivision_status', 'source', 'description',
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
