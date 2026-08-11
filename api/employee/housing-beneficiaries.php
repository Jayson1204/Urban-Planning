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
        respond(['status' => 'success', 'data' => $housingBeneficiaryRepo->stats()]);
    }

    if (!empty($_GET['unit_id']) && ($_GET['action'] ?? '') === 'by_unit') {
        respond(['status' => 'success', 'data' => $housingBeneficiaryRepo->forUnit((int)$_GET['unit_id'])]);
    }

    if (!empty($_GET['id'])) {
        $beneficiary = $housingBeneficiaryRepo->find((int)$_GET['id']);
        if (!$beneficiary) {
            respond(['status' => 'error', 'message' => 'Beneficiary not found.'], 404);
        }
        respond(['status' => 'success', 'data' => $beneficiary]);
    }

    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'beneficiary_status' => trim($_GET['beneficiary_status'] ?? ''),
        'category' => trim($_GET['category'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
        'unit_id' => (int)($_GET['unit_id'] ?? 0),
    ];
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 10);

    respond(['status' => 'success'] + $housingBeneficiaryRepo->paginate($filters, $page, $perPage));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $errors = $beneficiaryService->validateBeneficiaryInput($input, false);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $newId = $beneficiaryService->createBeneficiary($input);
    respond(['status' => 'success', 'message' => 'Beneficiary added successfully.', 'beneficiary_id' => $newId], 201);
}

if ($method === 'PUT') {
    $beneficiaryId = (int)($input['beneficiary_id'] ?? 0);
    if (!$beneficiaryId) {
        respond(['status' => 'error', 'message' => 'beneficiary_id is required.'], 422);
    }
    $errors = $beneficiaryService->validateBeneficiaryInput($input, true, $beneficiaryId);
    if ($errors) {
        respond(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }
    $beneficiaryService->updateBeneficiary($beneficiaryId, $input);
    respond(['status' => 'success', 'message' => 'Beneficiary updated successfully.']);
}

if ($method === 'DELETE') {
    $beneficiaryId = (int)($_GET['id'] ?? $input['beneficiary_id'] ?? 0);
    if (!$beneficiaryId) {
        respond(['status' => 'error', 'message' => 'beneficiary_id is required.'], 422);
    }
    $newStatus = ($_GET['status'] ?? $input['status'] ?? 'Archived') === 'Active' ? 'Active' : 'Archived';
    $beneficiaryService->setStatus($beneficiaryId, $newStatus);
    respond(['status' => 'success', 'message' => "Beneficiary marked as {$newStatus}."]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
