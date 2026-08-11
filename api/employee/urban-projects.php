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
        respond(['status' => 'success', 'data' => $urbanProjectRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $project = $urbanProjectRepo->find((int)$_GET['id']);
        if (!$project) {
            respond(['status' => 'error', 'message' => 'Urban project not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $project]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'barangay' => trim($_GET['barangay'] ?? ''),
        'project_type' => trim($_GET['project_type'] ?? ''),
        'project_status' => trim($_GET['project_status'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
        'plan_id' => (int)($_GET['plan_id'] ?? 0),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $urbanProjectRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $urbanProjectService->validateProjectInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $urbanProjectService->createProject($input);
    respond(['status' => 'success', 'message' => 'Urban project added successfully.', 'project_id' => $newId], 201);
}

if ($method === 'PUT') {
    $projectId = (int)($input['project_id'] ?? 0);
    if (!$projectId) {
        respond(['status' => 'error', 'message' => 'project_id is required.'], 422);
    }
    $errors = $urbanProjectService->validateProjectInput($input, true, $projectId);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $urbanProjectService->updateProject($projectId, $input);
    respond(['status' => 'success', 'message' => 'Urban project updated successfully.']);
}

if ($method === 'DELETE') {
    $projectId = (int)($_GET['id'] ?? $input['project_id'] ?? 0);
    if (!$projectId) {
        respond(['status' => 'error', 'message' => 'project_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $urbanProjectRepo->setStatus($projectId, $newStatus);
    respond(['status' => 'success', 'message' => "Urban project marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
