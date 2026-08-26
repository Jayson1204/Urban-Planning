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
$uploadDir = __DIR__ . '/../../uploads/permit-plan-documents/';
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'dwg'];
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'application/octet-stream', 'image/vnd.dwg'];
$maxFileSize = 10 * 1024 * 1024; // 10MB, plan sets run larger than typical supporting docs

if ($method === 'GET') {
    $applicationId = (int)($_GET['application_id'] ?? 0);
    if (!$applicationId) {
        respond(['status' => 'error', 'message' => 'application_id is required.'], 422);
    }
    respond(['status' => 'success', 'data' => $permitPlanDocumentRepo->forApplication($applicationId)]);
}

if ($method === 'POST') {
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $documentType = trim($_POST['document_type'] ?? '');

    if (!$applicationId || !$documentType) {
        respond(['status' => 'error', 'message' => 'application_id and document_type are required.'], 422);
    }
    $application = $permitApplicationRepo->find($applicationId);
    if (!$application) {
        respond(['status' => 'error', 'message' => 'Application not found.'], 404);
    }
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        respond(['status' => 'error', 'message' => 'No valid file uploaded.'], 422);
    }

    $file = $_FILES['file'];
    if ($file['size'] > $maxFileSize) {
        respond(['status' => 'error', 'message' => 'File exceeds the 10MB size limit.'], 422);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
        respond(['status' => 'error', 'message' => 'Only PDF, JPG, PNG, and DWG files are allowed.'], 422);
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        respond(['status' => 'error', 'message' => 'Failed to store the uploaded file.'], 500);
    }

    $previous = $permitPlanDocumentRepo->latestVersion($applicationId, $documentType);
    $versionNumber = $previous ? ((int)$previous['version_number'] + 1) : 1;
    if ($previous && $previous['document_status'] === 'Current') {
        $permitPlanDocumentRepo->markSuperseded($previous['document_id']);
    }

    // submitted_by is 'Staff' here because this endpoint is reached through the staff-only
    // Web app. A future citizen mobile app could call this same endpoint with
    // submitted_by=Applicant once that client exists, matching beneficiary-documents.php.
    $submittedBy = (trim($_POST['submitted_by'] ?? '') === 'Applicant') ? 'Applicant' : 'Staff';

    $documentId = $permitPlanDocumentRepo->create([
        'application_id' => $applicationId,
        'document_type' => $documentType,
        'version_number' => $versionNumber,
        'file_name' => basename($file['name']),
        'file_path' => 'uploads/permit-plan-documents/' . $storedName,
        'file_size' => $file['size'],
        'submitted_by' => $submittedBy,
        'resubmission_round' => (int)$application['resubmission_round'],
        'document_status' => 'Current',
    ]);

    $activityLogService->record('Urban Planning', 'Create', 'permit_plan_documents', $documentId, $documentType . ' v' . $versionNumber . ' (' . basename($file['name']) . ')', 'Uploaded a plan document for ' . ($application['reference_number'] ?? ('application #' . $applicationId)) . '.');
    respond(['status' => 'success', 'message' => 'Plan document uploaded successfully.', 'document_id' => $documentId, 'version_number' => $versionNumber], 201);
}

if ($method === 'DELETE') {
    $documentId = (int)($_GET['id'] ?? 0);
    if (!$documentId) {
        respond(['status' => 'error', 'message' => 'Document id is required.'], 422);
    }

    $document = $permitPlanDocumentRepo->find($documentId);
    if (!$document) {
        respond(['status' => 'error', 'message' => 'Document not found.'], 404);
    }

    $permitPlanDocumentRepo->delete($documentId);

    $absolutePath = __DIR__ . '/../../' . $document['file_path'];
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }

    $activityLogService->record('Urban Planning', 'Delete', 'permit_plan_documents', $documentId, $document['document_type'] . ' v' . $document['version_number'] . ' (' . $document['file_name'] . ')', 'Deleted a plan document.');
    respond(['status' => 'success', 'message' => 'Plan document deleted successfully.']);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
