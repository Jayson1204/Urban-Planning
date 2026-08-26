<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

$current = $citizenAccountService->currentCitizen();
if (!$current) {
    respondCitizenApp(['status' => 'error', 'message' => 'Not logged in.'], 401);
}
$residentId = $current['resident']['resident_id'];

$beneficiaries = $housingBeneficiaryRepo->forResident($residentId);
$beneficiaryIds = array_column($beneficiaries, 'beneficiary_id');

$method = $_SERVER['REQUEST_METHOD'];
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if ($method === 'GET') {
    $documents = [];
    foreach ($beneficiaryIds as $beneficiaryId) {
        $documents = array_merge($documents, $beneficiaryDocumentRepo->forBeneficiary($beneficiaryId));
    }
    respondCitizenApp(['status' => 'success', 'data' => ['beneficiary_ids' => $beneficiaryIds, 'documents' => $documents]]);
}

if ($method === 'POST') {
    $beneficiaryId = (int)($_POST['beneficiary_id'] ?? 0);
    $documentType = trim($_POST['document_type'] ?? '');

    if (!$beneficiaryId || !$documentType) {
        respondCitizenApp(['status' => 'error', 'message' => 'beneficiary_id and document_type are required.'], 422);
    }
    if (!in_array($beneficiaryId, $beneficiaryIds, true)) {
        respondCitizenApp(['status' => 'error', 'message' => 'That application does not belong to your account.'], 403);
    }

    $uploaded = $fileUploadService->handleUpload($_FILES['file'] ?? null, 'beneficiary-documents', $allowedExtensions, $allowedMimes, $maxFileSize);
    if (isset($uploaded['error'])) {
        respondCitizenApp(['status' => 'error', 'message' => $uploaded['error']], 422);
    }

    $documentId = $beneficiaryDocumentRepo->create([
        'beneficiary_id' => $beneficiaryId,
        'document_type' => $documentType,
        'file_name' => $uploaded['file_name'],
        'file_path' => $uploaded['file_path'],
        'file_size' => $uploaded['file_size'],
        'submitted_by' => 'Citizen',
    ]);

    respondCitizenApp(['status' => 'success', 'message' => 'Document uploaded successfully.', 'document_id' => $documentId], 201);
}

respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
