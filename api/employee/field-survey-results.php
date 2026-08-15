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

\App\Middleware\PermissionMiddleware::requireResource('field survey management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (($_GET['action'] ?? '') === 'stats') {
        respond(['status' => 'success', 'data' => $fieldSurveyResultRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $result = $fieldSurveyResultRepo->find((int)$_GET['id']);
        if (!$result) {
            respond(['status' => 'error', 'message' => 'Survey result not found.'], 404);
        }
        $result['photos'] = $fieldSurveyPhotoRepo->forResult($result['result_id']);
        respond(['status' => 'success', 'data' => $result]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'condition_rating' => trim($_GET['condition_rating'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $fieldSurveyResultRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $fieldSurveyResultService->validateResultInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $fieldSurveyResultService->createResult($input);
    $activityLogService->record('Field Survey', 'Create', 'field_survey_results', $newId, 'Result #' . $newId, 'Recorded a new survey result.');
    respond(['status' => 'success', 'message' => 'Survey result recorded successfully.', 'result_id' => $newId], 201);
}

if ($method === 'PUT') {
    $resultId = (int)($input['result_id'] ?? 0);
    if (!$resultId) {
        respond(['status' => 'error', 'message' => 'result_id is required.'], 422);
    }
    $errors = $fieldSurveyResultService->validateResultInput($input, true, $resultId);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $fieldSurveyResultService->updateResult($resultId, $input);
    $activityLogService->record('Field Survey', 'Update', 'field_survey_results', $resultId, 'Result #' . $resultId, 'Updated a survey result.');
    respond(['status' => 'success', 'message' => 'Survey result updated successfully.']);
}

if ($method === 'DELETE') {
    $resultId = (int)($_GET['id'] ?? $input['result_id'] ?? 0);
    if (!$resultId) {
        respond(['status' => 'error', 'message' => 'result_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $fieldSurveyResultRepo->setStatus($resultId, $newStatus);
    $activityLogService->record('Field Survey', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'field_survey_results', $resultId, 'Result #' . $resultId, "Marked survey result as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Survey result marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
