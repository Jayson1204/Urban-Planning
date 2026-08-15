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
    if (isset($_GET['search'])) {
        respond(['status' => 'success', 'data' => $householdRepo->search(trim($_GET['search']), 15)]);
    }

    if (($_GET['action'] ?? '') === 'stats') {
        respond(['status' => 'success', 'data' => $householdRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $household = $householdRepo->find((int)$_GET['id']);
        if (!$household) {
            respond(['status' => 'error', 'message' => 'Household not found.'], 404);
        }
        $household['members'] = $householdRepo->getMembers($household['household_id']);
        respond(['status' => 'success', 'data' => $household]);
    }

    $filters = [
        'search' => trim($_GET['list_search'] ?? ''),
        'barangay' => trim($_GET['barangay'] ?? ''),
        'household_type' => trim($_GET['household_type'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $householdRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $householdService->validateHouseholdInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $householdService->createHousehold($input);
    $activityLogService->record('Resident Management', 'Create', 'households', $newId, $input['household_number'] ?: ('Household #' . $newId), 'Added a new household record.');
    respond(['status' => 'success', 'message' => 'Household created successfully.', 'household_id' => $newId], 201);
}

if ($method === 'PUT') {
    $householdId = (int)($input['household_id'] ?? 0);
    if (!$householdId) {
        respond(['status' => 'error', 'message' => 'household_id is required.'], 422);
    }
    $errors = $householdService->validateHouseholdInput($input, true);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $householdService->updateHousehold($householdId, $input);
    $activityLogService->record('Resident Management', 'Update', 'households', $householdId, $input['household_number'] ?: ('Household #' . $householdId), 'Updated a household record.');
    respond(['status' => 'success', 'message' => 'Household updated successfully.']);
}

if ($method === 'DELETE') {
    $householdId = (int)($_GET['id'] ?? $input['household_id'] ?? 0);
    if (!$householdId) {
        respond(['status' => 'error', 'message' => 'household_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $householdRow = $householdRepo->find($householdId);
    $householdRepo->setStatus($householdId, $newStatus);
    $householdLabel = ($householdRow['household_number'] ?? null) ?: ('Household #' . $householdId);
    $activityLogService->record('Resident Management', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'households', $householdId, $householdLabel, "Marked household as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Household marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
