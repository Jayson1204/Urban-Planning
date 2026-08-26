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
    if (($_GET['action'] ?? '') === 'map') {
        respond(['status' => 'success', 'data' => $housingProjectRepo->allActive()]);
    }

    if (!empty($_GET['id'])) {
        $project = $housingProjectRepo->find((int)$_GET['id']);
        if (!$project) {
            respond(['status' => 'error', 'message' => 'Housing project not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $project]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'barangay_id' => trim($_GET['barangay_id'] ?? ''),
        'project_status' => trim($_GET['project_status'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $housingProjectRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $housingProjectService->validateInput($input);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $housingProjectService->create($input);
    $activityLogService->record('Urban Planning', 'Create', 'housing_projects', $newId, $input['name'] ?? ('Housing Project #' . $newId), 'Added a new housing project.');
    respond(['status' => 'success', 'message' => 'Housing project added successfully.', 'housing_project_id' => $newId], 201);
}

if ($method === 'PUT') {
    $housingProjectId = (int)($input['housing_project_id'] ?? 0);
    if (!$housingProjectId) {
        respond(['status' => 'error', 'message' => 'housing_project_id is required.'], 422);
    }
    $errors = $housingProjectService->validateInput($input);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $housingProjectService->update($housingProjectId, $input);
    $activityLogService->record('Urban Planning', 'Update', 'housing_projects', $housingProjectId, $input['name'] ?? ('Housing Project #' . $housingProjectId), 'Updated a housing project.');
    respond(['status' => 'success', 'message' => 'Housing project updated successfully.']);
}

if ($method === 'DELETE') {
    $housingProjectId = (int)($_GET['id'] ?? $input['housing_project_id'] ?? 0);
    if (!$housingProjectId) {
        respond(['status' => 'error', 'message' => 'housing_project_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $row = $housingProjectRepo->find($housingProjectId);
    $housingProjectRepo->setStatus($housingProjectId, $newStatus);
    $label = ($row['name'] ?? null) ?: ('Housing Project #' . $housingProjectId);
    $activityLogService->record('Urban Planning', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'housing_projects', $housingProjectId, $label, "Marked housing project as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Housing project marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
