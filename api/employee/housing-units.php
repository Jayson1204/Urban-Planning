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

\App\Middleware\PermissionMiddleware::requireResource('housing management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (($_GET['action'] ?? '') === 'stats') {
        respond(['status' => 'success', 'data' => $housingUnitRepo->stats()]);
    }

    if (!empty($_GET['unit_id']) && ($_GET['action'] ?? '') === 'history') {
        respond(['status' => 'success', 'data' => $housingUnitRepo->getHistory((int)$_GET['unit_id'])]);
    }

    if (!empty($_GET['id'])) {
        $unit = $housingUnitRepo->find((int)$_GET['id']);
        if (!$unit) {
            respond(['status' => 'error', 'message' => 'Housing unit not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $unit]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'barangay' => trim($_GET['barangay'] ?? ''),
        'occupancy_status' => trim($_GET['occupancy_status'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $housingUnitRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $housingService->validateHousingInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $housingService->createHousingUnit($input);
    $activityLogService->record('Housing Management', 'Create', 'housing_units', $newId, $input['unit_code'] ?? ('Unit #' . $newId), 'Added a new housing unit.');
    respond(['status' => 'success', 'message' => 'Housing unit added successfully.', 'unit_id' => $newId], 201);
}

if ($method === 'PUT') {
    $unitId = (int)($input['unit_id'] ?? 0);
    if (!$unitId) {
        respond(['status' => 'error', 'message' => 'unit_id is required.'], 422);
    }
    $errors = $housingService->validateHousingInput($input, true, $unitId);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $housingService->updateHousingUnit($unitId, $input);
    $activityLogService->record('Housing Management', 'Update', 'housing_units', $unitId, $input['unit_code'] ?? ('Unit #' . $unitId), 'Updated a housing unit.');
    respond(['status' => 'success', 'message' => 'Housing unit updated successfully.']);
}

if ($method === 'DELETE') {
    $unitId = (int)($_GET['id'] ?? $input['unit_id'] ?? 0);
    if (!$unitId) {
        respond(['status' => 'error', 'message' => 'unit_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $unitRow = $housingUnitRepo->find($unitId);
    $housingUnitRepo->setStatus($unitId, $newStatus);
    $unitLabel = ($unitRow['unit_code'] ?? null) ?: ('Unit #' . $unitId);
    $activityLogService->record('Housing Management', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'housing_units', $unitId, $unitLabel, "Marked housing unit as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Housing unit marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
