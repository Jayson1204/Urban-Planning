<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

if (!$authService->isLoggedIn()) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

\App\Middleware\PermissionMiddleware::requireResource('reports', $basePath);

function respond(array $payload, int $statusCode = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$reportTypes = [
    'resident' => [
        'repo' => $residentRepo,
        'filters' => ['search', 'barangay', 'status', 'date_from', 'date_to'],
        'csv_header' => ['Resident ID', 'Name', 'Barangay', 'Household #', 'Contact Number', 'Email', 'Status', 'Registered On'],
        'csv_row' => function ($r) {
            return [
                $r['resident_id'],
                trim(implode(' ', array_filter([$r['first_name'], $r['middle_name'], $r['last_name'], $r['suffix']]))),
                $r['barangay'] ?? $r['household_barangay'] ?? '',
                $r['household_number'] ?? '',
                $r['contact_number'] ?? '',
                $r['email'] ?? '',
                $r['status'],
                $r['created_at'],
            ];
        },
    ],
    'housing' => [
        'repo' => $housingUnitRepo,
        'filters' => ['search', 'barangay', 'occupancy_status', 'status', 'date_from', 'date_to'],
        'csv_header' => ['Unit Code', 'Project', 'Barangay', 'Unit Type', 'Occupancy Status', 'Record Status', 'Created On'],
        'csv_row' => function ($r) {
            return [
                $r['unit_code'],
                $r['project_name'] ?? '',
                $r['barangay'] ?? '',
                $r['unit_type'] ?? '',
                $r['occupancy_status'],
                $r['status'],
                $r['created_at'],
            ];
        },
    ],
    'project' => [
        'repo' => $urbanProjectRepo,
        'filters' => ['search', 'barangay', 'project_type', 'project_status', 'status', 'date_from', 'date_to'],
        'csv_header' => ['Project Code', 'Title', 'Barangay', 'Type', 'Lifecycle Status', 'Record Status', 'Budget', 'Created On'],
        'csv_row' => function ($r) {
            return [
                $r['project_code'],
                $r['project_title'],
                $r['barangay'] ?? '',
                $r['project_type'] ?? '',
                $r['project_status'],
                $r['status'],
                $r['budget'] ?? '',
                $r['created_at'],
            ];
        },
    ],
    'survey' => [
        'repo' => $fieldSurveyResultRepo,
        'filters' => ['search', 'condition_rating', 'status', 'date_from', 'date_to'],
        'csv_header' => ['Form', 'Subject', 'Survey Date', 'Condition Rating', 'Record Status', 'Recorded On'],
        'csv_row' => function ($r) {
            return [
                trim(($r['form_code'] ?? '') . ' - ' . ($r['form_title'] ?? '')),
                $r['subject_name'] ?? '',
                $r['survey_date'] ?? '',
                $r['condition_rating'] ?? '',
                $r['status'],
                $r['created_at'],
            ];
        },
    ],
];

$type = trim($_GET['type'] ?? '');
if (!isset($reportTypes[$type])) {
    respond(['status' => 'error', 'message' => 'Unknown or missing report type.'], 422);
}

$config = $reportTypes[$type];
$repo = $config['repo'];

$filters = [];
foreach ($config['filters'] as $field) {
    $filters[$field] = trim($_GET[$field] ?? '');
}

if (($_GET['export'] ?? '') === 'csv') {
    $rows = $repo->paginate($filters, 1, 100000)['data'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '-report-' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, $config['csv_header']);
    foreach ($rows as $row) {
        fputcsv($out, ($config['csv_row'])($row));
    }
    fclose($out);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'GET') {
    respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$page = (int)($_GET['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? 10);

respond([
    'status' => 'success',
    'data' => [
        'stats' => $repo->stats(),
        'listing' => $repo->paginate($filters, $page, $perPage),
    ],
]);
