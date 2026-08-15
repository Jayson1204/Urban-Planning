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

\App\Middleware\PermissionMiddleware::requireResource('housing management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (($_GET['action'] ?? '') === 'stats') {
        respond(['status' => 'success', 'data' => $housingRelocationRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $relocation = $housingRelocationRepo->find((int)$_GET['id']);
        if (!$relocation) {
            respond(['status' => 'error', 'message' => 'Relocation record not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $relocation]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'reason' => trim($_GET['reason'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
        'unit_id' => (int)($_GET['unit_id'] ?? 0),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $housingRelocationRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $housingRelocationService->validateRelocationInput($input);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $housingRelocationService->relocate($input);
    $relResident = $residentRepo->find((int)($input['resident_id'] ?? 0));
    $relLabel = $relResident ? trim($relResident['first_name'] . ' ' . $relResident['last_name']) : ('Relocation #' . $newId);
    $activityLogService->record('Housing Management', 'Create', 'housing_relocations', $newId, $relLabel, 'Recorded a resident relocation.');
    respond(['status' => 'success', 'message' => 'Relocation recorded successfully.', 'relocation_id' => $newId], 201);
}

if ($method === 'DELETE') {
    $relocationId = (int)($_GET['id'] ?? $input['relocation_id'] ?? 0);
    if (!$relocationId) {
        respond(['status' => 'error', 'message' => 'relocation_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $relRow = $housingRelocationRepo->find($relocationId);
    $housingRelocationService->setStatus($relocationId, $newStatus);
    $relResident = $relRow ? $residentRepo->find((int)$relRow['resident_id']) : null;
    $relLabel = $relResident ? trim($relResident['first_name'] . ' ' . $relResident['last_name']) : ('Relocation #' . $relocationId);
    $activityLogService->record('Housing Management', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'housing_relocations', $relocationId, $relLabel, "Marked relocation record as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Relocation record marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
