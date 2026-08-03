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

\App\Middleware\PermissionMiddleware::requireResource('resident management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $term = trim($_GET['search'] ?? '');
    respond(['status' => 'success', 'data' => $householdRepo->search($term, 15)]);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty(trim($input['barangay'] ?? '')) || empty(trim($input['street_address'] ?? ''))) {
        respond(['status' => 'error', 'message' => 'Barangay and street address are required.'], 422);
    }

    $data = [
        'household_number' => trim($input['household_number'] ?? '') ?: null,
        'barangay' => trim($input['barangay']),
        'street_address' => trim($input['street_address']),
        'household_type' => in_array($input['household_type'] ?? '', ['Owned', 'Renting', 'Informal Settler', 'Other'], true)
            ? $input['household_type'] : 'Other',
    ];

    $newId = $householdRepo->create($data);
    respond(['status' => 'success', 'message' => 'Household created successfully.', 'household_id' => $newId], 201);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
