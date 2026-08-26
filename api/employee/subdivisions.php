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
        respond(['status' => 'success', 'data' => $subdivisionRepo->allActive()]);
    }

    if (!empty($_GET['id'])) {
        $subdivision = $subdivisionRepo->find((int)$_GET['id']);
        if (!$subdivision) {
            respond(['status' => 'error', 'message' => 'Subdivision not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $subdivision]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'barangay_id' => trim($_GET['barangay_id'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $subdivisionRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $subdivisionService->validateInput($input);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $subdivisionService->create($input);
    $activityLogService->record('Urban Planning', 'Create', 'subdivisions', $newId, $input['name'] ?? ('Subdivision #' . $newId), 'Added a new subdivision.');
    respond(['status' => 'success', 'message' => 'Subdivision added successfully.', 'subdivision_id' => $newId], 201);
}

if ($method === 'PUT') {
    $subdivisionId = (int)($input['subdivision_id'] ?? 0);
    if (!$subdivisionId) {
        respond(['status' => 'error', 'message' => 'subdivision_id is required.'], 422);
    }
    $errors = $subdivisionService->validateInput($input);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $subdivisionService->update($subdivisionId, $input);
    $activityLogService->record('Urban Planning', 'Update', 'subdivisions', $subdivisionId, $input['name'] ?? ('Subdivision #' . $subdivisionId), 'Updated a subdivision.');
    respond(['status' => 'success', 'message' => 'Subdivision updated successfully.']);
}

if ($method === 'DELETE') {
    $subdivisionId = (int)($_GET['id'] ?? $input['subdivision_id'] ?? 0);
    if (!$subdivisionId) {
        respond(['status' => 'error', 'message' => 'subdivision_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $row = $subdivisionRepo->find($subdivisionId);
    $subdivisionRepo->setStatus($subdivisionId, $newStatus);
    $label = ($row['name'] ?? null) ?: ('Subdivision #' . $subdivisionId);
    $activityLogService->record('Urban Planning', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'subdivisions', $subdivisionId, $label, "Marked subdivision as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Subdivision marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
