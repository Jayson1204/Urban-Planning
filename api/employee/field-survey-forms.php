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
        respond(['status' => 'success', 'data' => $fieldSurveyFormRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $form = $fieldSurveyFormRepo->find((int)$_GET['id']);
        if (!$form) {
            respond(['status' => 'error', 'message' => 'Survey form not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $form]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'category' => trim($_GET['category'] ?? ''),
        'subject_type' => trim($_GET['subject_type'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $fieldSurveyFormRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $fieldSurveyFormService->validateFormInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $fieldSurveyFormService->createForm($input);
    $activityLogService->record('Field Survey', 'Create', 'field_survey_forms', $newId, $input['form_code'] ?? ('Form #' . $newId), 'Added a new survey form.');
    respond(['status' => 'success', 'message' => 'Survey form added successfully.', 'form_id' => $newId], 201);
}

if ($method === 'PUT') {
    $formId = (int)($input['form_id'] ?? 0);
    if (!$formId) {
        respond(['status' => 'error', 'message' => 'form_id is required.'], 422);
    }
    $errors = $fieldSurveyFormService->validateFormInput($input, true, $formId);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $fieldSurveyFormService->updateForm($formId, $input);
    $activityLogService->record('Field Survey', 'Update', 'field_survey_forms', $formId, $input['form_code'] ?? ('Form #' . $formId), 'Updated a survey form.');
    respond(['status' => 'success', 'message' => 'Survey form updated successfully.']);
}

if ($method === 'DELETE') {
    $formId = (int)($_GET['id'] ?? $input['form_id'] ?? 0);
    if (!$formId) {
        respond(['status' => 'error', 'message' => 'form_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $formRow = $fieldSurveyFormRepo->find($formId);
    $fieldSurveyFormRepo->setStatus($formId, $newStatus);
    $formLabel = ($formRow['form_code'] ?? null) ?: ('Form #' . $formId);
    $activityLogService->record('Field Survey', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'field_survey_forms', $formId, $formLabel, "Marked survey form as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Survey form marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
