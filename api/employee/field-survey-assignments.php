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
        respond(['status' => 'success', 'data' => $fieldSurveyAssignmentRepo->stats()]);
    }

    if (($_GET['action'] ?? '') === 'history') {
        $subjectType = trim($_GET['subject_type'] ?? '');
        if (!in_array($subjectType, ['Resident', 'Household', 'Site'], true)) {
            respond(['status' => 'error', 'message' => 'A valid subject_type is required.'], 422);
        }
        if ($subjectType === 'Site') {
            $siteLabel = trim($_GET['site_label'] ?? '');
            if ($siteLabel === '') {
                respond(['status' => 'error', 'message' => 'site_label is required for Site history.'], 422);
            }
            respond(['status' => 'success', 'data' => $fieldSurveyAssignmentRepo->getHistoryForSubject('Site', null, $siteLabel)]);
        }
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        if (!$subjectId) {
            respond(['status' => 'error', 'message' => 'subject_id is required.'], 422);
        }
        respond(['status' => 'success', 'data' => $fieldSurveyAssignmentRepo->getHistoryForSubject($subjectType, $subjectId)]);
    }

    if (!empty($_GET['id'])) {
        $assignment = $fieldSurveyAssignmentRepo->find((int)$_GET['id']);
        if (!$assignment) {
            respond(['status' => 'error', 'message' => 'Survey assignment not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $assignment]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'subject_type' => trim($_GET['subject_type'] ?? ''),
        'assignment_status' => trim($_GET['assignment_status'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $fieldSurveyAssignmentRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $fieldSurveyAssignmentService->validateAssignmentInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $fieldSurveyAssignmentService->createAssignment($input);
    $activityLogService->record('Field Survey', 'Create', 'field_survey_assignments', $newId, $input['site_label'] ?? ('Assignment #' . $newId), 'Added a new survey assignment.');
    respond(['status' => 'success', 'message' => 'Survey assignment added successfully.', 'assignment_id' => $newId], 201);
}

if ($method === 'PUT') {
    $assignmentId = (int)($input['assignment_id'] ?? 0);
    if (!$assignmentId) {
        respond(['status' => 'error', 'message' => 'assignment_id is required.'], 422);
    }
    $errors = $fieldSurveyAssignmentService->validateAssignmentInput($input, true);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $fieldSurveyAssignmentService->updateAssignment($assignmentId, $input);
    $activityLogService->record('Field Survey', 'Update', 'field_survey_assignments', $assignmentId, $input['site_label'] ?? ('Assignment #' . $assignmentId), 'Updated a survey assignment.');
    respond(['status' => 'success', 'message' => 'Survey assignment updated successfully.']);
}

if ($method === 'DELETE') {
    $assignmentId = (int)($_GET['id'] ?? $input['assignment_id'] ?? 0);
    if (!$assignmentId) {
        respond(['status' => 'error', 'message' => 'assignment_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $assignmentRow = $fieldSurveyAssignmentRepo->find($assignmentId);
    $fieldSurveyAssignmentRepo->setStatus($assignmentId, $newStatus);
    $assignmentLabel = ($assignmentRow['site_label'] ?? null) ?: ('Assignment #' . $assignmentId);
    $activityLogService->record('Field Survey', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'field_survey_assignments', $assignmentId, $assignmentLabel, "Marked survey assignment as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Survey assignment marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
