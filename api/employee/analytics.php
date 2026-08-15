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

\App\Middleware\PermissionMiddleware::requireResource('program analytics', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    respond(['status' => 'success', 'data' => [
        'summary' => $analyticsRepo->summary(),
        'kpis' => $analyticsRepo->kpis(),
        'charts' => [
            'housing_occupancy' => $analyticsRepo->housingOccupancyChart(),
            'urban_project_status' => $analyticsRepo->urbanProjectStatusChart(),
            'survey_condition' => $analyticsRepo->surveyConditionChart(),
        ],
        'recent_activity' => $analyticsRepo->recentActivity(8),
    ]]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
