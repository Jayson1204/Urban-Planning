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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $prompt = trim($_GET['prompt'] ?? '') ?: 'In one sentence, say hello and confirm you are working.';

    $result = $geminiService->generateContent($prompt);

    if (!$result['success']) {
        respond(['status' => 'error', 'message' => $result['error'], 'raw' => $result['raw'] ?? null], 502);
    }

    respond(['status' => 'success', 'prompt' => $prompt, 'response' => $result['text']]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
