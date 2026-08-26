<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    respondCitizenApp(['status' => 'error', 'message' => 'Email and password are required.'], 422);
}

$account = $citizenAccountService->login($email, $password);
if (!$account) {
    respondCitizenApp(['status' => 'error', 'message' => 'Invalid email or password.'], 401);
}

$_SESSION['citizen_account_id'] = $account['citizen_account_id'];
respondCitizenApp(['status' => 'success', 'message' => 'Logged in.']);
