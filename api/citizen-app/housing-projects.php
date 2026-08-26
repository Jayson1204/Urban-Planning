<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$current = $citizenAccountService->currentCitizen();
if (!$current) {
    respondCitizenApp(['status' => 'error', 'message' => 'Not logged in.'], 401);
}

// Read-only wrapper around HousingProjectRepository -- no write verbs, and
// status is always forced to 'Active' regardless of client input so a citizen
// can never list or fetch an archived project, even by guessing an id.
if (!empty($_GET['id'])) {
    $project = $housingProjectRepo->find((int)$_GET['id']);
    if (!$project || $project['status'] !== 'Active') {
        respondCitizenApp(['status' => 'error', 'message' => 'Housing project not found.'], 404);
    }
    respondCitizenApp(['status' => 'success', 'data' => $project]);
}

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'barangay_id' => trim($_GET['barangay_id'] ?? ''),
    'project_status' => trim($_GET['project_status'] ?? ''),
    'status' => 'Active',
];
$page = (int)($_GET['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? 50);

respondCitizenApp(['status' => 'success'] + $housingProjectRepo->paginate($filters, $page, $perPage));
