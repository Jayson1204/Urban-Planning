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

if ($method !== 'GET') {
    respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

if (!empty($_GET['id'])) {
    $detail = $barangayService->detail((int)$_GET['id']);
    if (!$detail) {
        respond(['status' => 'error', 'message' => 'Barangay not found.'], 404);
    }
    respond(['status' => 'success', 'data' => $detail]);
}

if (($_GET['action'] ?? '') === 'housing-markers') {
    respond(['status' => 'success', 'data' => $barangayRepo->housingUnitMarkers()]);
}

$search = trim($_GET['search'] ?? '');
respond(['status' => 'success', 'data' => $barangayRepo->all($search)]);
