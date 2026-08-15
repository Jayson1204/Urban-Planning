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
        respond(['status' => 'success', 'data' => $zoningClearanceRepo->stats()]);
    }

    if ($action === 'regulations') {
        respond(['status' => 'success', 'data' => $zoningUseRegulationRepo->all()]);
    }

    if ($action === 'preview') {
        $result = $zoningConformityService->evaluate(
            $_GET['zone_classification'] ?? null,
            $_GET['use_category'] ?? null,
            ($_GET['proposed_height_m'] ?? '') !== '' ? $_GET['proposed_height_m'] : null,
            ($_GET['proposed_setback_m'] ?? '') !== '' ? $_GET['proposed_setback_m'] : null,
            ($_GET['proposed_far'] ?? '') !== '' ? $_GET['proposed_far'] : null,
            ($_GET['proposed_lot_occupancy_pct'] ?? '') !== '' ? $_GET['proposed_lot_occupancy_pct'] : null
        );
        respond(['status' => 'success', 'data' => $result]);
    }

    if (!empty($_GET['id'])) {
        $clearance = $zoningClearanceRepo->find((int)$_GET['id']);
        if (!$clearance) {
            respond(['status' => 'error', 'message' => 'Zoning clearance not found.'], 404);
        }
        $clearance['reviews'] = $zoningClearanceRepo->getReviews($clearance['clearance_id']);
        respond(['status' => 'success', 'data' => $clearance]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'clearance_status' => trim($_GET['clearance_status'] ?? ''),
        'zone_classification' => trim($_GET['zone_classification'] ?? ''),
        'conformity_result' => trim($_GET['conformity_result'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $zoningClearanceRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $zoningClearanceService->validateClearanceInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $zoningClearanceService->createClearance($input);
    $row = $zoningClearanceRepo->find($newId);
    $activityLogService->record('Urban Planning', 'Create', 'zoning_clearances', $newId, $row['reference_number'] ?? ('Clearance #' . $newId), 'Submitted a new zoning clearance application.');
    respond(['status' => 'success', 'message' => 'Zoning clearance application submitted successfully.', 'clearance_id' => $newId, 'reference_number' => $row['reference_number'] ?? null], 201);
}

if ($method === 'PUT') {
    $clearanceId = (int)($input['clearance_id'] ?? 0);
    if (!$clearanceId) {
        respond(['status' => 'error', 'message' => 'clearance_id is required.'], 422);
    }

    if (array_key_exists('clearance_status', $input)) {
        $result = $zoningClearanceService->transitionStatus(
            $clearanceId,
            $input['clearance_status'],
            $input['remarks'] ?? '',
            $input['reviewer_role'] ?? null
        );
        if (isset($result['error'])) {
            respond(['status' => 'error', 'message' => $result['error']], 422);
        }
        $row = $zoningClearanceRepo->find($clearanceId);
        $activityLogService->record('Urban Planning', 'Update', 'zoning_clearances', $clearanceId, $row['reference_number'] ?? ('Clearance #' . $clearanceId), "Clearance status changed to {$input['clearance_status']}.");
        respond(['status' => 'success', 'message' => 'Clearance status updated successfully.']);
    }

    $errors = $zoningClearanceService->validateClearanceInput($input, true, $clearanceId);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $zoningClearanceService->updateClearance($clearanceId, $input);
    $row = $zoningClearanceRepo->find($clearanceId);
    $activityLogService->record('Urban Planning', 'Update', 'zoning_clearances', $clearanceId, $row['reference_number'] ?? ('Clearance #' . $clearanceId), 'Updated a zoning clearance application.');
    respond(['status' => 'success', 'message' => 'Zoning clearance updated successfully.']);
}

if ($method === 'DELETE') {
    $clearanceId = (int)($_GET['id'] ?? $input['clearance_id'] ?? 0);
    if (!$clearanceId) {
        respond(['status' => 'error', 'message' => 'clearance_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $row = $zoningClearanceRepo->find($clearanceId);
    $zoningClearanceRepo->setStatus($clearanceId, $newStatus);
    $label = ($row['reference_number'] ?? null) ?: ('Clearance #' . $clearanceId);
    $activityLogService->record('Urban Planning', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'zoning_clearances', $clearanceId, $label, "Marked zoning clearance as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Zoning clearance marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
