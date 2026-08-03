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

\App\Middleware\PermissionMiddleware::requireResource('resident management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = __DIR__ . '/../../uploads/residents/';
$allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
$allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if ($method === 'GET') {
    $residentId = (int)($_GET['resident_id'] ?? 0);
    if (!$residentId) {
        respond(['status' => 'error', 'message' => 'resident_id is required.'], 422);
    }
    respond(['status' => 'success', 'data' => $residentDocumentRepo->forResident($residentId)]);
}

if ($method === 'POST') {
    $residentId = (int)($_POST['resident_id'] ?? 0);
    $documentType = trim($_POST['document_type'] ?? '');

    if (!$residentId || !$documentType) {
        respond(['status' => 'error', 'message' => 'resident_id and document_type are required.'], 422);
    }
    if (!$residentRepo->find($residentId)) {
        respond(['status' => 'error', 'message' => 'Resident not found.'], 404);
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

    $documentId = $residentDocumentRepo->create([
        'resident_id' => $residentId,
        'document_type' => $documentType,
        'file_name' => basename($file['name']),
        'file_path' => 'uploads/residents/' . $storedName,
        'file_size' => $file['size'],
    ]);

    respond(['status' => 'success', 'message' => 'Document uploaded successfully.', 'document_id' => $documentId], 201);
}

if ($method === 'DELETE') {
    $documentId = (int)($_GET['id'] ?? 0);
    if (!$documentId) {
        respond(['status' => 'error', 'message' => 'Document id is required.'], 422);
    }

    $document = $residentDocumentRepo->find($documentId);
    if (!$document) {
        respond(['status' => 'error', 'message' => 'Document not found.'], 404);
    }

    $residentDocumentRepo->delete($documentId);

    $absolutePath = __DIR__ . '/../../' . $document['file_path'];
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }

    respond(['status' => 'success', 'message' => 'Document deleted successfully.']);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
