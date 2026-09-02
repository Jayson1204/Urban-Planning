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

// User/Staff Report: sourced from the same remote user directory the User Management
// page already proxies through (api/employee/users.php), since there is no local users
// table with the full production roster. The remote endpoint has no filter/pagination
// params of its own (same constraint the User Management page already lives with), so
// this fetches the full list once and does filtering/pagination in PHP -- the browser
// still only ever receives one page of rows. Each remote row already embeds a
// login_history array (see assets/js/usermanagement/account-status/api.js), which is
// used here for "last login" without an extra remote call. Never forwards password/token
// fields -- only the specific columns listed below are mapped through.
function fetchUserReportRows($filters)
{
    require_once __DIR__ . '/../../config/proxy.php';
    $apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';
    $res = proxyRequest(rtrim($apiBaseUrl, '/') . '/users.php', 'GET', null);

    if (empty($res['body']) || $res['code'] !== 200 || !isset($res['body']['data'])) {
        return null;
    }

    $rows = [];
    foreach ($res['body']['data'] as $u) {
        $roleObj = $u['roles'] ?? [];
        $posObj = $u['positions'] ?? [];
        $deptObj = $posObj['departments'] ?? [];

        $lastLogin = null;
        if (!empty($u['login_history']) && is_array($u['login_history'])) {
            $lastEntry = end($u['login_history']);
            $lastLogin = $lastEntry['login_time'] ?? null;
        }

        $rows[] = [
            'employee_id' => $u['employee_id'] ?? ('USR-' . ($u['user_id'] ?? '')),
            'name' => trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')),
            'email' => $u['email'] ?? '',
            'role' => $roleObj['role_name'] ?? 'Unassigned',
            'department' => $deptObj['department_name'] ?? '',
            'status' => $u['status'] ?? 'Active',
            'created_at' => $u['created_at'] ?? '',
            'last_login' => $lastLogin,
        ];
    }

    $search = strtolower(trim($filters['search'] ?? ''));
    $role = trim($filters['role'] ?? '');
    $status = trim($filters['status'] ?? '');
    $dateFrom = trim($filters['date_from'] ?? '');
    $dateTo = trim($filters['date_to'] ?? '');

    return array_values(array_filter($rows, function ($r) use ($search, $role, $status, $dateFrom, $dateTo) {
        if ($search !== '' && strpos(strtolower($r['name'] . ' ' . $r['email']), $search) === false) return false;
        if ($role !== '' && $r['role'] !== $role) return false;
        if ($status !== '' && $r['status'] !== $status) return false;
        if ($dateFrom !== '' && substr($r['created_at'], 0, 10) < $dateFrom) return false;
        if ($dateTo !== '' && substr($r['created_at'], 0, 10) > $dateTo) return false;
        return true;
    }));
}

// Display names and per-metric labels for the Overall Reports hub's consolidated summary
// export -- one lightweight stats() call per report type, never a full row listing.
$OVERALL_REPORT_LABELS = [
    'resident' => 'Resident / Beneficiary Report',
    'housing' => 'Housing Report',
    'application' => 'Housing Application Report',
    'project' => 'Urban Planning Project Report',
    'survey' => 'Field Survey Report',
    'user' => 'User / Staff Report',
    'activity' => 'Activity / Transaction Report',
];
$OVERALL_METRIC_LABELS = [
    'resident' => ['total' => 'Total Residents', 'active' => 'Active', 'archived' => 'Archived', 'households_covered' => 'Households Covered'],
    'housing' => ['total' => 'Total Housing Units', 'vacant' => 'Vacant', 'occupied' => 'Occupied', 'archived' => 'Archived'],
    'application' => ['total' => 'Total Applications', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'],
    'project' => ['total' => 'Total Projects', 'planned' => 'Planned', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'archived' => 'Archived'],
    'survey' => ['total' => 'Total Survey Results', 'active' => 'Active', 'archived' => 'Archived'],
    'user' => ['total' => 'Total Users', 'active' => 'Active', 'inactive' => 'Inactive', 'admin' => 'Admin Roles'],
    'activity' => ['total' => 'Total Entries', 'today' => 'Today', 'creates' => 'Creates', 'updates' => 'Updates', 'archives' => 'Archives / Deletes'],
];

function computeOverallSummary($residentRepo, $housingUnitRepo, $housingBeneficiaryRepo, $urbanProjectRepo, $fieldSurveyResultRepo, $activityLogRepo)
{
    $userRows = fetchUserReportRows([]);
    $userStats = null;
    if ($userRows !== null) {
        $total = count($userRows);
        $active = count(array_filter($userRows, fn($r) => strtolower($r['status']) === 'active'));
        $admin = count(array_filter($userRows, fn($r) => stripos($r['role'], 'admin') !== false));
        $userStats = ['total' => $total, 'active' => $active, 'inactive' => $total - $active, 'admin' => $admin];
    }

    return [
        'resident' => $residentRepo->stats(),
        'housing' => $housingUnitRepo->stats(),
        'application' => $housingBeneficiaryRepo->reportStats([]),
        'project' => $urbanProjectRepo->stats(),
        'survey' => $fieldSurveyResultRepo->stats(),
        'user' => $userStats,
        'activity' => $activityLogRepo->stats(),
    ];
}

$type = trim($_GET['type'] ?? '');

// Overall Reports hub: a consolidated cross-report summary, not a listing of its own.
// Every figure is a real stats()/reportStats() count already used by that report's own
// page -- nothing here is fetched or computed differently for the hub.
if ($type === 'overall') {
    $summary = computeOverallSummary($residentRepo, $housingUnitRepo, $housingBeneficiaryRepo, $urbanProjectRepo, $fieldSurveyResultRepo, $activityLogRepo);

    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="overall-reports-summary-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Overall Reports Summary']);
        fputcsv($out, ['Generated On', date('F j, Y g:i A')]);
        fputcsv($out, []);
        fputcsv($out, ['Report', 'Metric', 'Value']);
        foreach ($OVERALL_REPORT_LABELS as $key => $label) {
            if ($key === 'user' && $summary['user'] === null) {
                fputcsv($out, [$label, 'Status', 'Unavailable - could not reach the shared user directory']);
                continue;
            }
            foreach ($OVERALL_METRIC_LABELS[$key] as $metricKey => $metricLabel) {
                fputcsv($out, [$label, $metricLabel, $summary[$key][$metricKey] ?? 0]);
            }
        }
        fclose($out);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'GET') {
        respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
    }

    respond(['status' => 'success', 'data' => [
        'summary' => $summary,
        'labels' => $OVERALL_REPORT_LABELS,
        'metric_labels' => $OVERALL_METRIC_LABELS,
        'generated_at' => date('c'),
    ]]);
}

// User/Staff Report is handled separately from the generic $reportTypes dispatch below
// since its data source is a remote proxy, not a local repository with paginate()/stats().
if ($type === 'user') {
    $filters = [
        'search' => trim($_GET['search'] ?? ''),
        'role' => trim($_GET['role'] ?? ''),
        'status' => trim($_GET['status'] ?? ''),
        'date_from' => trim($_GET['date_from'] ?? ''),
        'date_to' => trim($_GET['date_to'] ?? ''),
    ];

    $rows = fetchUserReportRows($filters);
    if ($rows === null) {
        respond(['status' => 'error', 'message' => 'Could not reach the shared user directory.'], 502);
    }

    if (($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="user-report-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Employee ID', 'Name', 'Email', 'Role', 'Department', 'Status', 'Registered On', 'Last Login']);
        foreach ($rows as $row) {
            fputcsv($out, [
                $row['employee_id'], $row['name'], $row['email'], $row['role'],
                $row['department'], $row['status'], $row['created_at'], $row['last_login'] ?? 'Never',
            ]);
        }
        fclose($out);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'GET') {
        respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
    }

    $total = count($rows);
    $active = count(array_filter($rows, fn($r) => strtolower($r['status']) === 'active'));
    $admin = count(array_filter($rows, fn($r) => stripos($r['role'], 'admin') !== false));

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, (int)($_GET['per_page'] ?? 10));
    $offset = ($page - 1) * $perPage;

    respond(['status' => 'success', 'data' => [
        'stats' => ['total' => $total, 'active' => $active, 'inactive' => $total - $active, 'admin' => $admin],
        'listing' => [
            'data' => array_slice($rows, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ],
    ]]);
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
    'application' => [
        'repo' => $housingBeneficiaryRepo,
        'filters' => ['search', 'barangay', 'beneficiary_status', 'category', 'status', 'date_from', 'date_to'],
        'csv_header' => ['Beneficiary ID', 'Applicant Name', 'Barangay', 'Category', 'Application Status', 'Application Date', 'Award Date', 'Housing Unit', 'Record Status'],
        'csv_row' => function ($r) {
            return [
                $r['beneficiary_id'],
                $r['resident_name'] ?? '',
                $r['resident_barangay'] ?? $r['household_barangay'] ?? '',
                $r['category'] ?? '',
                $r['beneficiary_status'],
                $r['application_date'] ?? '',
                $r['award_date'] ?? '',
                $r['unit_code'] ?? '',
                $r['status'],
            ];
        },
    ],
    'project' => [
        'repo' => $urbanProjectRepo,
        'filters' => ['search', 'barangay', 'project_type', 'project_status', 'status', 'date_from', 'date_to'],
        'csv_header' => ['Project Code', 'Title', 'Barangay', 'Type', 'Lifecycle Status', 'Record Status', 'Budget', 'Contractor', 'Start Date', 'Target Completion', 'Actual Completion', 'Created On'],
        'csv_row' => function ($r) {
            return [
                $r['project_code'],
                $r['project_title'],
                $r['barangay'] ?? '',
                $r['project_type'] ?? '',
                $r['project_status'],
                $r['status'],
                $r['budget'] ?? '',
                $r['contractor'] ?? '',
                $r['start_date'] ?? '',
                $r['target_completion_date'] ?? '',
                $r['actual_completion_date'] ?? '',
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
    'activity' => [
        'repo' => $activityLogRepo,
        'filters' => ['search', 'module', 'action', 'date_from', 'date_to'],
        'csv_header' => ['Date/Time', 'Actor', 'Module', 'Action', 'Target Table', 'Target Reference', 'Description'],
        'csv_row' => function ($r) {
            return [
                $r['created_at'],
                $r['actor_name'],
                $r['module'],
                $r['action'],
                $r['target_table'],
                $r['target_label'] ?? $r['target_id'] ?? '',
                $r['description'] ?? '',
            ];
        },
    ],
];

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

// housing_beneficiaries' stats() (used by the Beneficiaries management page) reports a
// fixed global count; the Housing Application Report instead needs its summary cards to
// respect whatever filters are currently applied, hence the dedicated reportStats() call.
$stats = ($type === 'application') ? $repo->reportStats($filters) : $repo->stats();

respond([
    'status' => 'success',
    'data' => [
        'stats' => $stats,
        'listing' => $repo->paginate($filters, $page, $perPage),
    ],
]);
