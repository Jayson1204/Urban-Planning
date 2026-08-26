<?php

namespace App\Services;

class FileUploadService
{
    // Validates and stores an uploaded file under uploads/{$subdir}/, returning
    // ['file_name' => original name, 'file_path' => relative path, 'file_size' => bytes]
    // or ['error' => message] on failure. Shared by staff and citizen upload endpoints
    // so the size/type rules and storage convention stay in one place.
    public function handleUpload($file, $subdir, $allowedExtensions, $allowedMimes, $maxFileSize)
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'No valid file uploaded.'];
        }
        if ($file['size'] > $maxFileSize) {
            return ['error' => 'File exceeds the size limit.'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($extension, $allowedExtensions, true) || !in_array($mime, $allowedMimes, true)) {
            return ['error' => 'Unsupported file type.'];
        }

        $uploadDir = rtrim(__DIR__ . '/../../uploads/' . $subdir, '/') . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $uploadDir . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['error' => 'Failed to store the uploaded file.'];
        }

        return [
            'file_name' => basename($file['name']),
            'file_path' => 'uploads/' . $subdir . '/' . $storedName,
            'file_size' => $file['size'],
        ];
    }
}
