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

\App\Middleware\PermissionMiddleware::requireResource('field survey management', $basePath);

$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = __DIR__ . '/../../uploads/field-surveys/';
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if ($method === 'GET') {
    $resultId = (int)($_GET['result_id'] ?? 0);
    if (!$resultId) {
        respond(['status' => 'error', 'message' => 'result_id is required.'], 422);
    }
    respond(['status' => 'success', 'data' => $fieldSurveyPhotoRepo->forResult($resultId)]);
}

if ($method === 'POST') {
    $resultId = (int)($_POST['result_id'] ?? 0);

    if (!$resultId) {
        respond(['status' => 'error', 'message' => 'result_id is required.'], 422);
    }
    if (!$fieldSurveyResultRepo->find($resultId)) {
        respond(['status' => 'error', 'message' => 'Survey result not found.'], 404);
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
        respond(['status' => 'error', 'message' => 'Only JPG, PNG, and WEBP images are allowed.'], 422);
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        respond(['status' => 'error', 'message' => 'Failed to store the uploaded file.'], 500);
    }

    $photoId = $fieldSurveyPhotoRepo->create([
        'result_id' => $resultId,
        'caption' => trim($_POST['caption'] ?? ''),
        'file_name' => basename($file['name']),
        'file_path' => 'uploads/field-surveys/' . $storedName,
        'file_size' => $file['size'],
    ]);

    respond(['status' => 'success', 'message' => 'Photo uploaded successfully.', 'photo_id' => $photoId], 201);
}

if ($method === 'DELETE') {
    $photoId = (int)($_GET['id'] ?? 0);
    if (!$photoId) {
        respond(['status' => 'error', 'message' => 'Photo id is required.'], 422);
    }

    $photo = $fieldSurveyPhotoRepo->find($photoId);
    if (!$photo) {
        respond(['status' => 'error', 'message' => 'Photo not found.'], 404);
    }

    $fieldSurveyPhotoRepo->delete($photoId);

    $absolutePath = __DIR__ . '/../../' . $photo['file_path'];
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }

    respond(['status' => 'success', 'message' => 'Photo deleted successfully.']);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
