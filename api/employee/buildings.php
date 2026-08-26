<?php

$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

if (!$authService->isLoggedIn()) {
    respond(['status' => 'error', 'message' => 'Authentication required.'], 401);
}

\App\Middleware\PermissionMiddleware::requireResource('urban planning', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

if (!empty($_GET['id'])) {
    $building = $buildingRepo->find((int)$_GET['id']);
    if (!$building) {
        respond(['status' => 'error', 'message' => 'Building not found.'], 404);
    }
    respond(['status' => 'success', 'data' => $building]);
}

if (($_GET['action'] ?? '') === 'stats') {
    respond(['status' => 'success', 'data' => ['total' => $buildingRepo->countAll()]]);
}

if (empty($_GET['bbox'])) {
    respond(['status' => 'error', 'message' => 'bbox is required, e.g. ?bbox=minLng,minLat,maxLng,maxLat'], 422);
}

$result = $buildingService->buildingsForBbox($_GET['bbox']);
if (isset($result['error'])) {
    respond(['status' => 'error', 'message' => $result['error']], 422);
}

$features = array_map(function ($row) {
    return [
        'type' => 'Feature',
        'geometry' => json_decode($row['footprint_geojson'], true),
        'properties' => [
            'building_id' => (int)$row['building_id'],
            'barangay_id' => $row['barangay_id'] !== null ? (int)$row['barangay_id'] : null,
            'building_type' => $row['building_type'],
            'source' => $row['source'],
        ],
    ];
}, $result['rows']);

respond([
    'status' => 'success',
    'type' => 'FeatureCollection',
    'features' => $features,
    'truncated' => $result['truncated'],
    'total_in_view' => $result['total_in_view'],
]);
