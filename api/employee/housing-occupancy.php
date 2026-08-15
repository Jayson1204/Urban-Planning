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
        respond(['status' => 'success', 'data' => $housingOccupancyRepo->stats()]);
    }

    if (!empty($_GET['unit_id']) && ($_GET['action'] ?? '') === 'by_unit') {
        respond(['status' => 'success', 'data' => $housingOccupancyRepo->forUnit((int)$_GET['unit_id'])]);
    }

    if (!empty($_GET['resident_id']) && ($_GET['action'] ?? '') === 'active_for_resident') {
        respond(['status' => 'success', 'data' => $housingOccupancyRepo->activeForResident((int)$_GET['resident_id'])]);
    }

    if (!empty($_GET['id'])) {
        $occupancy = $housingOccupancyRepo->find((int)$_GET['id']);
        if (!$occupancy) {
            respond(['status' => 'error', 'message' => 'Occupancy record not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $occupancy]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
        'unit_id' => (int)($_GET['unit_id'] ?? 0),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $housingOccupancyRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $housingOccupancyService->validateMoveInInput($input);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $housingOccupancyService->moveIn($input);
    $occResident = $residentRepo->find((int)($input['resident_id'] ?? 0));
    $occLabel = $occResident ? trim($occResident['first_name'] . ' ' . $occResident['last_name']) : ('Occupancy #' . $newId);
    $activityLogService->record('Housing Management', 'Create', 'housing_occupancy', $newId, $occLabel, 'Recorded a resident move-in.');
    respond(['status' => 'success', 'message' => 'Occupancy recorded successfully.', 'occupancy_id' => $newId], 201);
}

if ($method === 'DELETE') {
    $occupancyId = (int)($_GET['id'] ?? $input['occupancy_id'] ?? 0);
    if (!$occupancyId) {
        respond(['status' => 'error', 'message' => 'occupancy_id is required.'], 422);
    }
    $moveOutDate = $_GET['move_out_date'] ?? $input['move_out_date'] ?? null;
    $occRow = $housingOccupancyRepo->find($occupancyId);
    $ok = $housingOccupancyService->vacate($occupancyId, $moveOutDate);
    if (!$ok) {
        respond(['status' => 'error', 'message' => 'Occupancy record not found.'], 404);
    }
    $occResident = $occRow ? $residentRepo->find((int)$occRow['resident_id']) : null;
    $occLabel = $occResident ? trim($occResident['first_name'] . ' ' . $occResident['last_name']) : ('Occupancy #' . $occupancyId);
    $activityLogService->record('Housing Management', 'Update', 'housing_occupancy', $occupancyId, $occLabel, 'Recorded a resident move-out.');
    respond(['status' => 'success', 'message' => 'Resident marked as moved out.']);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
