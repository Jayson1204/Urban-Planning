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

\App\Middleware\PermissionMiddleware::requireResource('housing management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if ($method === 'GET') {
    $beneficiaryId = (int)($_GET['beneficiary_id'] ?? 0);
    if (!$beneficiaryId) {
        respond(['status' => 'error', 'message' => 'beneficiary_id is required.'], 422);
    }
    respond(['status' => 'success', 'data' => $beneficiaryDocumentRepo->forBeneficiary($beneficiaryId)]);
}

if ($method === 'POST') {
    $beneficiaryId = (int)($_POST['beneficiary_id'] ?? 0);
    $documentType = trim($_POST['document_type'] ?? '');

    if (!$beneficiaryId || !$documentType) {
        respond(['status' => 'error', 'message' => 'beneficiary_id and document_type are required.'], 422);
    }
    if (!$housingBeneficiaryRepo->find($beneficiaryId)) {
        respond(['status' => 'error', 'message' => 'Beneficiary not found.'], 404);
    }
    $uploaded = $fileUploadService->handleUpload($_FILES['file'] ?? null, 'beneficiary-documents', $allowedExtensions, $allowedMimes, $maxFileSize);
    if (isset($uploaded['error'])) {
        respond(['status' => 'error', 'message' => $uploaded['error']], 422);
    }

    // submitted_by is 'Staff' here because this endpoint is reached through the staff-only
    // Web app. The citizen mobile app calls api/citizen/beneficiary-documents.php instead,
    // which sets submitted_by=Citizen.
    $submittedBy = (trim($_POST['submitted_by'] ?? '') === 'Citizen') ? 'Citizen' : 'Staff';

    $documentId = $beneficiaryDocumentRepo->create([
        'beneficiary_id' => $beneficiaryId,
        'document_type' => $documentType,
        'file_name' => $uploaded['file_name'],
        'file_path' => $uploaded['file_path'],
        'file_size' => $uploaded['file_size'],
        'submitted_by' => $submittedBy,
    ]);

    $activityLogService->record('Housing Management', 'Create', 'housing_beneficiary_documents', $documentId, $documentType . ' (' . basename($file['name']) . ')', 'Uploaded a beneficiary application document.');
    respond(['status' => 'success', 'message' => 'Document uploaded successfully.', 'document_id' => $documentId], 201);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'PATCH') {
    $documentId = (int)($input['document_id'] ?? 0);
    $reviewStatus = trim($input['review_status'] ?? '');

    if (!$documentId || !in_array($reviewStatus, ['Verified', 'Rejected'], true)) {
        respond(['status' => 'error', 'message' => 'document_id and a valid review_status (Verified or Rejected) are required.'], 422);
    }

    $document = $beneficiaryDocumentRepo->find($documentId);
    if (!$document) {
        respond(['status' => 'error', 'message' => 'Document not found.'], 404);
    }

    $reviewNotes = trim($input['review_notes'] ?? '');
    if ($reviewStatus === 'Rejected' && $reviewNotes === '') {
        respond(['status' => 'error', 'message' => 'A reason is required when rejecting a document.'], 422);
    }

    $reviewerName = $headerUser['full_name'] ?? 'Staff';
    $beneficiaryDocumentRepo->setReviewStatus($documentId, $reviewStatus, $reviewNotes ?: null, $reviewerName);

    $activityLogService->record('Housing Management', $reviewStatus === 'Verified' ? 'Approve' : 'Reject', 'housing_beneficiary_documents', $documentId, $document['document_type'] . ' (' . $document['file_name'] . ')', "Marked document as {$reviewStatus}." . ($reviewNotes ? " Reason: {$reviewNotes}" : ''));
    respond(['status' => 'success', 'message' => "Document marked as {$reviewStatus}."]);
}

if ($method === 'DELETE') {
    $documentId = (int)($_GET['id'] ?? 0);
    if (!$documentId) {
        respond(['status' => 'error', 'message' => 'Document id is required.'], 422);
    }

    $document = $beneficiaryDocumentRepo->find($documentId);
    if (!$document) {
        respond(['status' => 'error', 'message' => 'Document not found.'], 404);
    }

    $beneficiaryDocumentRepo->delete($documentId);

    $absolutePath = __DIR__ . '/../../' . $document['file_path'];
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }

    $activityLogService->record('Housing Management', 'Delete', 'housing_beneficiary_documents', $documentId, $document['document_type'] . ' (' . $document['file_name'] . ')', 'Deleted a beneficiary application document.');
    respond(['status' => 'success', 'message' => 'Document deleted successfully.']);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
