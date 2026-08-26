<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim($input['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respondCitizenApp(['status' => 'error', 'message' => 'A valid email is required.'], 422);
}

// Always succeeds with the same generic message, whether or not the email
// matched an account -- requestPasswordReset() never reveals account existence.
$citizenAccountService->requestPasswordReset($email);

respondCitizenApp(['status' => 'success', 'message' => 'If an account exists for that email, a password reset link has been sent.']);
