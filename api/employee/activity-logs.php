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

\App\Middleware\PermissionMiddleware::requireResource('activity logs', $basePath);

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

if (($_GET['action'] ?? '') === 'stats') {
    respond(['status' => 'success', 'data' => $activityLogRepo->stats()]);
}

if (($_GET['action'] ?? '') === 'modules') {
    respond(['status' => 'success', 'data' => $activityLogRepo->distinctModules()]);
}

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'module' => trim($_GET['module'] ?? ''),
    'action' => trim($_GET['action_filter'] ?? ''),
    'user_id' => (int)($_GET['user_id'] ?? 0),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
];
$page = (int)($_GET['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? 15);

respond(['status' => 'success'] + $activityLogRepo->paginate($filters, $page, $perPage));
