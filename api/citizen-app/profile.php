<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

$current = $citizenAccountService->currentCitizen();
if (!$current) {
    respondCitizenApp(['status' => 'error', 'message' => 'Not logged in.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    respondCitizenApp(['status' => 'success', 'data' => [
        'email' => $current['account']['email'],
        'resident' => $current['resident'],
    ]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // Contact/personal fields only -- household reassignment and the login
    // email (citizen_accounts.email, separate from residents.email) are
    // deliberately not editable from this endpoint.
    $editable = [
        'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date',
        'gender', 'civil_status', 'contact_number', 'barangay', 'street_address', 'occupation',
    ];
    $filtered = array_intersect_key($input, array_flip($editable));

    $errors = $residentService->validateResidentInput($filtered, true);
    if ($errors) {
        respondCitizenApp(['status' => 'error', 'message' => implode(' ', $errors)], 422);
    }

    $residentId = $current['resident']['resident_id'];
    $residentService->updateResident($residentId, $filtered);
    $resident = $residentRepo->find($residentId);

    respondCitizenApp(['status' => 'success', 'message' => 'Profile updated.', 'data' => [
        'email' => $current['account']['email'],
        'resident' => $resident,
    ]]);
}

respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
