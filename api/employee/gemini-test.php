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

// Dev-only diagnostic endpoint (connectivity check + usage summary), not part of the AI
// Planning Assistant module itself - restricted to superadmin/global-access so it can't be
// used by any logged-in employee as an unmonitored way to burn Gemini API credits.
if (empty($headerUser['is_superadmin']) && empty($headerUser['is_global_access'])) {
    respond(['status' => 'error', 'message' => 'Superadmin access required.'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (($_GET['action'] ?? '') === 'usage') {
        respond(['status' => 'success', 'usage' => $aiUsageLogRepo->summary()]);
    }

    $prompt = trim($_GET['prompt'] ?? '') ?: 'In one sentence, say hello and confirm you are working.';
    $complex = filter_var($_GET['complex'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $result = $geminiService->generateContent($prompt, $complex);

    if (!$result['success']) {
        respond(['status' => 'error', 'message' => $result['error'], 'raw' => $result['raw'] ?? null], 502);
    }

    respond(['status' => 'success', 'prompt' => $prompt, 'response' => $result['text']]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
