<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$errors = $citizenAccountService->validateRegistration($input);
if ($errors) {
    respondCitizenApp(['status' => 'error', 'message' => implode(' ', $errors)], 422);
}

$result = $citizenAccountService->register($input);
if (isset($result['error'])) {
    respondCitizenApp(['status' => 'error', 'message' => $result['error']], 422);
}

$_SESSION['citizen_account_id'] = $result['citizen_account_id'];

respondCitizenApp(['status' => 'success', 'message' => 'Account created.', 'resident_id' => $result['resident_id']], 201);
