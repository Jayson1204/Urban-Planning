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

if ($method === 'GET') {
    if (($_GET['action'] ?? '') === 'stats') {
        respond(['status' => 'success', 'data' => $infrastructureRecordRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $record = $infrastructureRecordRepo->find((int)$_GET['id']);
        if (!$record) {
            respond(['status' => 'error', 'message' => 'Infrastructure record not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $record]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'barangay' => trim($_GET['barangay'] ?? ''),
        'infrastructure_type' => trim($_GET['infrastructure_type'] ?? ''),
        'condition_status' => trim($_GET['condition_status'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $infrastructureRecordRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $infrastructureRecordService->validateRecordInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $infrastructureRecordService->createRecord($input);
    $activityLogService->record('Urban Planning', 'Create', 'infrastructure_records', $newId, $input['infrastructure_name'] ?? ('Infrastructure #' . $newId), 'Added a new infrastructure record.');
    respond(['status' => 'success', 'message' => 'Infrastructure record added successfully.', 'record_id' => $newId], 201);
}

if ($method === 'PUT') {
    $recordId = (int)($input['record_id'] ?? 0);
    if (!$recordId) {
        respond(['status' => 'error', 'message' => 'record_id is required.'], 422);
    }
    $errors = $infrastructureRecordService->validateRecordInput($input, true);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $infrastructureRecordService->updateRecord($recordId, $input);
    $activityLogService->record('Urban Planning', 'Update', 'infrastructure_records', $recordId, $input['infrastructure_name'] ?? ('Infrastructure #' . $recordId), 'Updated an infrastructure record.');
    respond(['status' => 'success', 'message' => 'Infrastructure record updated successfully.']);
}

if ($method === 'DELETE') {
    $recordId = (int)($_GET['id'] ?? $input['record_id'] ?? 0);
    if (!$recordId) {
        respond(['status' => 'error', 'message' => 'record_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $infraRow = $infrastructureRecordRepo->find($recordId);
    $infrastructureRecordRepo->setStatus($recordId, $newStatus);
    $infraLabel = ($infraRow['infrastructure_name'] ?? null) ?: ('Infrastructure #' . $recordId);
    $activityLogService->record('Urban Planning', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'infrastructure_records', $recordId, $infraLabel, "Marked infrastructure record as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Infrastructure record marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
