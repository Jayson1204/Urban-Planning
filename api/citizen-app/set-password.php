<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$token = trim($input['token'] ?? '');
$password = trim($input['password'] ?? '');

if (!$token || !$password) {
    respondCitizenApp(['status' => 'error', 'message' => 'Token and password are required.'], 422);
}

$result = $citizenAccountService->completeSetPassword($token, $password);
if (isset($result['error'])) {
    respondCitizenApp(['status' => 'error', 'message' => $result['error']], 422);
}

respondCitizenApp(['status' => 'success', 'message' => 'Password set. You can now log in on the mobile app.']);
