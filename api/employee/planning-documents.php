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
$uploadDir = __DIR__ . '/../../uploads/planning/';
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'docx'];
$allowedMimes = [
    'application/pdf', 'image/jpeg', 'image/png',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if ($method === 'GET') {
    $planId = (int)($_GET['plan_id'] ?? 0);
    if (!$planId) {
        respond(['status' => 'error', 'message' => 'plan_id is required.'], 422);
    }
    respond(['status' => 'success', 'data' => $planningDocumentRepo->forPlan($planId)]);
}

if ($method === 'POST') {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $documentType = trim($_POST['document_type'] ?? '');

    if (!$planId || !$documentType) {
        respond(['status' => 'error', 'message' => 'plan_id and document_type are required.'], 422);
    }
    if (!$developmentPlanRepo->find($planId)) {
        respond(['status' => 'error', 'message' => 'Development plan not found.'], 404);
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
        respond(['status' => 'error', 'message' => 'Only PDF, JPG, PNG, XLSX, and DOCX files are allowed.'], 422);
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        respond(['status' => 'error', 'message' => 'Failed to store the uploaded file.'], 500);
    }

    $documentId = $planningDocumentRepo->create([
        'plan_id' => $planId,
        'document_type' => $documentType,
        'file_name' => basename($file['name']),
        'file_path' => 'uploads/planning/' . $storedName,
        'file_size' => $file['size'],
    ]);

    respond(['status' => 'success', 'message' => 'Document uploaded successfully.', 'document_id' => $documentId], 201);
}

if ($method === 'DELETE') {
    $documentId = (int)($_GET['id'] ?? 0);
    if (!$documentId) {
        respond(['status' => 'error', 'message' => 'Document id is required.'], 422);
    }

    $document = $planningDocumentRepo->find($documentId);
    if (!$document) {
        respond(['status' => 'error', 'message' => 'Document not found.'], 404);
    }

    $planningDocumentRepo->delete($documentId);

    $absolutePath = __DIR__ . '/../../' . $document['file_path'];
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }

    respond(['status' => 'success', 'message' => 'Document deleted successfully.']);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
