<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Sends the response now and keeps the script running for slow follow-up work
// (e.g. the SMTP call for the citizen account invite) so the client isn't stuck
// waiting on it. Falls back to a normal flush if fastcgi_finish_request is unavailable
// (mod_php on XAMPP/Apache).
function respondAndContinue(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    $body = json_encode($payload);
    header('Content-Length: ' . strlen($body));
    header('Connection: close');
    echo $body;
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

if (!$authService->isLoggedIn()) {
    respond(['status' => 'error', 'message' => 'Authentication required.'], 401);
}

\App\Middleware\PermissionMiddleware::requireResource('resident management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (($_GET['action'] ?? '') === 'stats') {
        respond(['status' => 'success', 'data' => $residentRepo->stats()]);
    }

    if (!empty($_GET['id'])) {
        $resident = $residentRepo->find((int)$_GET['id']);
        if (!$resident) {
            respond(['status' => 'error', 'message' => 'Resident not found.'], 404);
        }
        $resident['documents'] = $residentDocumentRepo->forResident($resident['resident_id']);
        $resident['household_members'] = $resident['household_id']
            ? $residentService->getHouseholdMembers($resident['household_id'])
            : [];
        respond(['status' => 'success', 'data' => $resident]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'barangay' => trim($_GET['barangay'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $residentRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $residentService->validateResidentInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $residentService->createResident($input);
    $activityLogService->record('Resident Management', 'Create', 'residents', $newId, trim(($input['first_name'] ?? '') . ' ' . ($input['last_name'] ?? '')), 'Added a new resident record.');

    // Respond now -- the client shouldn't wait on the SMTP call below.
    ignore_user_abort(true);
    respondAndContinue(['status' => 'success', 'message' => 'Resident added successfully.', 'resident_id' => $newId], 201);

    // Auto-create a citizen mobile app account when an email is given -- failure to
    // send the invite email must not fail resident creation, and runs after the
    // response above so the slow SMTP call doesn't block the Save Resident click.
    if (!empty(trim($input['email'] ?? ''))) {
        try {
            $citizenAccountService->createForResident($newId, $input['email']);
        } catch (\Throwable $e) {
            error_log('Citizen account auto-creation failed for resident #' . $newId . ': ' . $e->getMessage());
        }
    }
    exit;
}

if ($method === 'PUT') {
    $residentId = (int)($input['resident_id'] ?? 0);
    if (!$residentId) {
        respond(['status' => 'error', 'message' => 'resident_id is required.'], 422);
    }
    $errors = $residentService->validateResidentInput($input, true);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $residentService->updateResident($residentId, $input);
    $activityLogService->record('Resident Management', 'Update', 'residents', $residentId, trim(($input['first_name'] ?? '') . ' ' . ($input['last_name'] ?? '')), 'Updated a resident record.');
    respond(['status' => 'success', 'message' => 'Resident updated successfully.']);
}

if ($method === 'DELETE') {
    $residentId = (int)($_GET['id'] ?? $input['resident_id'] ?? 0);
    if (!$residentId) {
        respond(['status' => 'error', 'message' => 'resident_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $residentRow = $residentRepo->find($residentId);
    $residentRepo->setStatus($residentId, $newStatus);
    $residentLabel = $residentRow ? trim($residentRow['first_name'] . ' ' . $residentRow['last_name']) : ('Resident #' . $residentId);
    $activityLogService->record('Resident Management', $newStatus === 'Archived' ? 'Archive' : 'Restore', 'residents', $residentId, $residentLabel, "Marked resident as {$newStatus}.");
    respond(['status' => 'success', 'message' => "Resident marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
