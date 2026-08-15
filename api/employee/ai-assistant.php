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

\App\Middleware\PermissionMiddleware::requireResource('ai planning assistant', $basePath);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $message = trim($input['message'] ?? '');
    $history = is_array($input['history'] ?? null) ? $input['history'] : [];

    if ($message === '') {
        respond(['status' => 'error', 'message' => 'A message is required.'], 422);
    }

    // Cap history length so the payload and prompt stay reasonable.
    $history = array_slice($history, -20);

    $result = $geminiService->chat($message, $history);

    if (!$result['success']) {
        respond(['status' => 'error', 'message' => $result['error']], 502);
    }

    respond(['status' => 'success', 'response' => $result['text']]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
