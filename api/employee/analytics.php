<?php
$basePath = '../../';
require_once __DIR__ . '/../../src/bootstrap.php';

if (!$authService->isLoggedIn()) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

\App\Middleware\PermissionMiddleware::requireResource('program analytics', $basePath);

function respond(array $payload, int $statusCode = 200): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

// Aggregates the shared production user directory (same remote endpoint the User
// Management page already proxies through) into role/registration-trend counts
// server-side, so the browser only ever receives totals -- never the raw staff list.
function aggregateUserAnalytics($dateFrom, $dateTo): array
{
    require_once __DIR__ . '/../../config/proxy.php';
    $apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';

    try {
        $usersRes = proxyRequest(rtrim($apiBaseUrl, '/') . '/users.php', 'GET', null);
    } catch (\Throwable $e) {
        return ['status' => 'unavailable', 'message' => 'Could not reach the shared user directory.'];
    }

    if (empty($usersRes['body']) || $usersRes['code'] !== 200 || !isset($usersRes['body']['data'])) {
        return ['status' => 'unavailable', 'message' => 'Could not reach the shared user directory.'];
    }

    $users = $usersRes['body']['data'];
    $total = count($users);
    $byRole = [];
    $staffAdminCount = 0;
    $otherCount = 0;
    $trend = [];

    $trendFrom = $dateFrom ?: date('Y-m-d', strtotime('-12 months'));
    $trendTo = $dateTo ?: date('Y-m-d');

    foreach ($users as $u) {
        $roleObj = $u['roles'] ?? [];
        $roleName = $roleObj['role_name'] ?? 'Unassigned';
        $rolePrefix = strtoupper($roleObj['role_prefix'] ?? '');
        $byRole[$roleName] = ($byRole[$roleName] ?? 0) + 1;

        $roleLower = strtolower($roleName);
        $isAdmin = in_array($rolePrefix, ['SA', 'SADM', 'ADM', 'DADM', 'ADMIN'], true)
            || strpos($roleLower, 'admin') !== false;
        if ($isAdmin) {
            $staffAdminCount++;
        } else {
            $otherCount++;
        }

        $createdAt = $u['created_at'] ?? null;
        if ($createdAt) {
            $day = substr($createdAt, 0, 10);
            if ($day >= $trendFrom && $day <= $trendTo) {
                $month = substr($createdAt, 0, 7);
                $trend[$month] = ($trend[$month] ?? 0) + 1;
            }
        }
    }

    ksort($trend);
    $trendRows = [];
    foreach ($trend as $month => $count) {
        $trendRows[] = ['month' => $month, 'total' => $count];
    }

    $byRoleRows = [];
    foreach ($byRole as $role => $count) {
        $byRoleRows[] = ['role' => $role, 'total' => $count];
    }
    usort($byRoleRows, function ($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    return [
        'status' => 'ok',
        'total_users' => $total,
        'staff_admin_count' => $staffAdminCount,
        'other_count' => $otherCount,
        'by_role' => $byRoleRows,
        'registration_trend' => $trendRows,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    respond(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$dateFrom = $dateFrom !== '' ? $dateFrom : null;
$dateTo = $dateTo !== '' ? $dateTo : null;

if (($_GET['export'] ?? '') === 'csv') {
    $rows = $analyticsRepo->locationComparison($dateFrom, $dateTo);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="overall-analytics-by-barangay-' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Barangay', 'Residents', 'Housing Units', 'Applications', 'Urban Projects', 'Total', '% of Citywide Total']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['barangay'],
            $row['residents'],
            $row['housing_units'],
            $row['applications'],
            $row['projects'],
            $row['total'],
            $row['percent'] . '%',
        ]);
    }
    fclose($out);
    exit;
}

respond(['status' => 'success', 'data' => [
    'summary' => $analyticsRepo->overallSummary($dateFrom, $dateTo),
    'housing' => [
        'by_status' => $analyticsRepo->housingApplicationsByStatus($dateFrom, $dateTo),
        'by_category' => $analyticsRepo->housingApplicationsByCategory($dateFrom, $dateTo),
        'applications_over_time' => $analyticsRepo->housingApplicationsOverTime($dateFrom, $dateTo),
        'applications_status_over_time' => $analyticsRepo->housingApplicationsStatusOverTime($dateFrom, $dateTo),
        'occupancy' => $analyticsRepo->housingOccupancyChart(),
        'by_barangay' => $analyticsRepo->housingUnitsByBarangay($dateFrom, $dateTo),
    ],
    'projects' => [
        'by_status' => $analyticsRepo->projectsByStatus($dateFrom, $dateTo),
        'by_type' => $analyticsRepo->projectsByType($dateFrom, $dateTo),
        'by_barangay' => $analyticsRepo->projectsByBarangay($dateFrom, $dateTo),
        'over_time' => $analyticsRepo->projectsOverTime($dateFrom, $dateTo),
    ],
    'users' => aggregateUserAnalytics($dateFrom, $dateTo),
    'residents' => [
        'by_barangay' => $analyticsRepo->residentsByBarangay($dateFrom, $dateTo),
        'over_time' => $analyticsRepo->residentsOverTime($dateFrom, $dateTo),
    ],
    'location' => $analyticsRepo->locationComparison($dateFrom, $dateTo),
    'recent_activity' => $analyticsRepo->recentActivity(8),
]]);
