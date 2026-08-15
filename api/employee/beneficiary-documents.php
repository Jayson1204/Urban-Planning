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
$uploadDir = __DIR__ . '/../../uploads/beneficiary-documents/';
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
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        respond(['status' => 'error', 'message' => 'No valid file uploaded.'], 422);
    }

    $file = $_FILES['file'];
    if ($file['size'] > $maxFileSize) {
        respond(['status' => 'error', 'message' => 'File exceeds the 5MB size limit.'], 422);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
        respond(['status' => 'error', 'message' => 'Only PDF, JPG, and PNG files are allowed.'], 422);
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        respond(['status' => 'error', 'message' => 'Failed to store the uploaded file.'], 500);
    }

    // submitted_by is 'Staff' here because this endpoint is reached through the staff-only
    // Web app. The future citizen mobile app will call this same endpoint directly with
    // submitted_by=Citizen once that client exists.
    $submittedBy = (trim($_POST['submitted_by'] ?? '') === 'Citizen') ? 'Citizen' : 'Staff';

    $documentId = $beneficiaryDocumentRepo->create([
        'beneficiary_id' => $beneficiaryId,
        'document_type' => $documentType,
        'file_name' => basename($file['name']),
        'file_path' => 'uploads/beneficiary-documents/' . $storedName,
        'file_size' => $file['size'],
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
