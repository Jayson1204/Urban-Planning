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
        respond(['status' => 'success', 'data' => $developmentPlanRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $plan = $developmentPlanRepo->find((int)$_GET['id']);
        if (!$plan) {
            respond(['status' => 'error', 'message' => 'Development plan not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $plan]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'barangay' => trim($_GET['barangay'] ?? ''),
        'plan_type' => trim($_GET['plan_type'] ?? ''),
        'plan_status' => trim($_GET['plan_status'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $developmentPlanRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $developmentPlanService->validatePlanInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $developmentPlanService->createDevelopmentPlan($input);
    $activityLogService->record('Urban Planning', 'Create', 'development_plans', $newId, $input['plan_code'] ?? ('Plan #' . $newId), 'Added a new development plan.');
    respond(['status' => 'success', 'message' => 'Development plan added successfully.', 'plan_id' => $newId], 201);
}

if ($method === 'PUT') {
    $planId = (int)($input['plan_id'] ?? 0);
    if (!$planId) {
        respond(['status' => 'error', 'message' => 'plan_id is required.'], 422);
    }
    $errors = $developmentPlanService->validatePlanInput($input, true, $planId);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $developmentPlanService->updateDevelopmentPlan($planId, $input);
    $activityLogService->record('Urban Planning', 'Update', 'development_plans', $planId, $input['plan_code'] ?? ('Plan #' . $planId), 'Updated a development plan.');
    respond(['status' => 'success', 'message' => 'Development plan updated successfully.']);
}

if ($method === 'DELETE') {
    $planId = (int)($_GET['id'] ?? $input['plan_id'] ?? 0);
    if (!$planId) {
        respond(['status' => 'error', 'message' => 'plan_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $planRow = $developmentPlanRepo->find($planId);
    $developmentPlanRepo->setStatus($planId, $newStatus);
    $planLabel = ($planRow['plan_code'] ?? null) ?: ('Plan #' . $planId);
    $activityLogService->record('Urban Planning', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'development_plans', $planId, $planLabel, "Marked development plan as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Development plan marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
