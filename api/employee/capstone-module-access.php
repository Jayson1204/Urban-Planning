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

// Same authorization bar as editing roles on this page: superadmin/global-access,
// or an explicit EDIT grant (matches the client-side `canEdit` check in ui.js/modal.js).
$canManageModuleAccess = !empty($headerUser['is_superadmin'])
    || !empty($headerUser['is_global_access'])
    || in_array('EDIT', $headerUser['granted_actions'] ?? []);

if (!$canManageModuleAccess) {
    respond(['status' => 'error', 'message' => 'Forbidden: you do not have permission to manage module access.'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    respond([
        'status' => 'success',
        'modules' => \App\Repositories\CapstoneModulePermissionRepository::moduleDefinitions(),
        'assignments' => $capstoneModulePermRepo->getAllAssignments(),
    ]);
}

if ($method === 'PUT' || $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $roleId = (int)($input['role_id'] ?? 0);
    $modules = is_array($input['modules'] ?? null) ? $input['modules'] : [];

    if (!$roleId) {
        respond(['status' => 'error', 'message' => 'role_id is required.'], 422);
    }

    $saved = $capstoneModulePermRepo->setModulesForRole($roleId, $modules);
    respond(['status' => 'success', 'message' => 'Module access updated successfully.', 'modules' => $saved]);
}

respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
