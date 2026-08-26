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
    $action = $_GET['action'] ?? '';

    if ($action === 'stats') {
        respond(['status' => 'success', 'data' => $permitApplicationRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $application = $permitApplicationRepo->find((int)$_GET['id']);
        if (!$application) {
            respond(['status' => 'error', 'message' => 'Permit application not found.'], 404);
        }
        $application['discipline_reviews'] = $permitApplicationRepo->getDisciplineReviews($application['application_id']);
        $application['reviews'] = $permitApplicationRepo->getReviews($application['application_id']);
        $application['plan_documents'] = $permitPlanDocumentRepo->forApplication($application['application_id']);
        respond(['status' => 'success', 'data' => $application]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'application_type' => trim($_GET['application_type'] ?? ''),
        'application_status' => trim($_GET['application_status'] ?? ''),
        'consolidated_result' => trim($_GET['consolidated_result'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $permitApplicationRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $permitApplicationService->validateApplicationInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $permitApplicationService->createApplication($input);
    $row = $permitApplicationRepo->find($newId);
    $activityLogService->record('Urban Planning', 'Create', 'permit_applications', $newId, $row['reference_number'] ?? ('Application #' . $newId), 'Submitted a new ' . ($row['application_type'] ?? 'permit') . ' application.');
    respond(['status' => 'success', 'message' => 'Application submitted successfully.', 'application_id' => $newId, 'reference_number' => $row['reference_number'] ?? null], 201);
}

if ($method === 'PUT') {
    $applicationId = (int)($input['application_id'] ?? 0);
    if (!$applicationId) {
        respond(['status' => 'error', 'message' => 'application_id is required.'], 422);
    }

    $action = $input['action'] ?? '';

    if ($action === 'resubmit') {
        $result = $permitApplicationService->resubmit($applicationId, $input['remarks'] ?? '');
        if (isset($result['error'])) {
            respond(['status' => 'error', 'message' => $result['error']], 422);
        }
        $row = $permitApplicationRepo->find($applicationId);
        $activityLogService->record('Urban Planning', 'Update', 'permit_applications', $applicationId, $row['reference_number'] ?? ('Application #' . $applicationId), 'Application resubmitted for review.');
        respond(['status' => 'success', 'message' => 'Application resubmitted successfully.']);
    }

    if ($action === 'issue_permit') {
        $result = $permitApplicationService->issuePermit($applicationId, $input['conditions_of_approval'] ?? '', $input['expiry_date'] ?? '', $input['permit_number'] ?? null);
        if (isset($result['error'])) {
            respond(['status' => 'error', 'message' => $result['error']], 422);
        }
        $row = $permitApplicationRepo->find($applicationId);
        $activityLogService->record('Urban Planning', 'Approve', 'permit_applications', $applicationId, $row['reference_number'] ?? ('Application #' . $applicationId), 'Permit issued: ' . ($result['permit_number'] ?? ''));
        respond(['status' => 'success', 'message' => 'Permit issued successfully.', 'permit_number' => $result['permit_number'] ?? null]);
    }

    if (array_key_exists('discipline', $input) && array_key_exists('discipline_status', $input)) {
        $result = $permitApplicationService->transitionDisciplineReview(
            $applicationId,
            $input['discipline'],
            $input['discipline_status'],
            $input['remarks'] ?? ''
        );
        if (isset($result['error'])) {
            respond(['status' => 'error', 'message' => $result['error']], 422);
        }
        $row = $permitApplicationRepo->find($applicationId);
        $activityLogService->record('Urban Planning', 'Update', 'permit_applications', $applicationId, $row['reference_number'] ?? ('Application #' . $applicationId), $input['discipline'] . ' review marked ' . $input['discipline_status'] . '.');
        respond(['status' => 'success', 'message' => 'Discipline review updated successfully.']);
    }

    if (array_key_exists('application_status', $input)) {
        $result = $permitApplicationService->transitionApplicationStatus(
            $applicationId,
            $input['application_status'],
            $input['remarks'] ?? '',
            $input['reviewer_role'] ?? null
        );
        if (isset($result['error'])) {
            respond(['status' => 'error', 'message' => $result['error']], 422);
        }
        $row = $permitApplicationRepo->find($applicationId);
        $activityLogService->record('Urban Planning', 'Update', 'permit_applications', $applicationId, $row['reference_number'] ?? ('Application #' . $applicationId), "Application status changed to {$input['application_status']}.");
        respond(['status' => 'success', 'message' => 'Application status updated successfully.']);
    }

    $errors = $permitApplicationService->validateApplicationInput($input, true);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $permitApplicationService->updateApplication($applicationId, $input);
    $row = $permitApplicationRepo->find($applicationId);
    $activityLogService->record('Urban Planning', 'Update', 'permit_applications', $applicationId, $row['reference_number'] ?? ('Application #' . $applicationId), 'Updated a permit application.');
    respond(['status' => 'success', 'message' => 'Application updated successfully.']);
}

if ($method === 'DELETE') {
    $applicationId = (int)($_GET['id'] ?? $input['application_id'] ?? 0);
    if (!$applicationId) {
        respond(['status' => 'error', 'message' => 'application_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $row = $permitApplicationRepo->find($applicationId);
    $permitApplicationRepo->setStatus($applicationId, $newStatus);
    $label = ($row['reference_number'] ?? null) ?: ('Application #' . $applicationId);
    $activityLogService->record('Urban Planning', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'permit_applications', $applicationId, $label, "Marked permit application as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Application marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
