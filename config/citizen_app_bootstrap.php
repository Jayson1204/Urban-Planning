<?php
// Shared setup for api/citizen-app/*.php -- the citizen mobile app's own local-DB
// backed endpoints. Deliberately not src/bootstrap.php: citizens are not staff
// sessions and must not go through SessionTimeout/AuthMiddleware, which assume a
// staff login. Session key is $_SESSION['citizen_account_id'].

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../src/Repositories/CitizenAccountRepository.php';
require_once __DIR__ . '/../src/Repositories/ResidentRepository.php';
require_once __DIR__ . '/../src/Repositories/HouseholdRepository.php';
require_once __DIR__ . '/../src/Repositories/ResidentDocumentRepository.php';
require_once __DIR__ . '/../src/Repositories/HousingBeneficiaryRepository.php';
require_once __DIR__ . '/../src/Repositories/BeneficiaryDocumentRepository.php';
require_once __DIR__ . '/../src/Repositories/HousingUnitRepository.php';
require_once __DIR__ . '/../src/Repositories/HousingProjectRepository.php';
require_once __DIR__ . '/../src/Services/ResidentService.php';
require_once __DIR__ . '/../src/Services/HouseholdService.php';
require_once __DIR__ . '/../src/Services/CitizenAccountService.php';
require_once __DIR__ . '/../src/Services/FileUploadService.php';
require_once __DIR__ . '/../src/Services/BeneficiaryService.php';

$citizenAccountRepo = new \App\Repositories\CitizenAccountRepository($db);
$residentRepo = new \App\Repositories\ResidentRepository($db);
$householdRepo = new \App\Repositories\HouseholdRepository($db);
$residentDocumentRepo = new \App\Repositories\ResidentDocumentRepository($db);
$housingBeneficiaryRepo = new \App\Repositories\HousingBeneficiaryRepository($db);
$beneficiaryDocumentRepo = new \App\Repositories\BeneficiaryDocumentRepository($db);
$housingUnitRepo = new \App\Repositories\HousingUnitRepository($db);
$housingProjectRepo = new \App\Repositories\HousingProjectRepository($db);
$residentService = new \App\Services\ResidentService($residentRepo, $householdRepo, $residentDocumentRepo);
$householdService = new \App\Services\HouseholdService($householdRepo);
$citizenAccountService = new \App\Services\CitizenAccountService($citizenAccountRepo, $residentRepo, $residentService, $householdService);
$fileUploadService = new \App\Services\FileUploadService();
$beneficiaryService = new \App\Services\BeneficiaryService($housingBeneficiaryRepo, $residentRepo, $housingUnitRepo, $householdRepo);

function respondCitizenApp(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function citizenAppCors(): void {
    $allowedOrigins = [
        'http://localhost', 'http://localhost:80', 'http://localhost:3000',
        'http://127.0.0.1', 'http://127.0.0.1:80',
    ];
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        $origin = $_SERVER['HTTP_ORIGIN'];
        // Private LAN ranges are allowed too, so a physical device or emulator on the
        // same network can reach this dev-only API family during testing.
        if (in_array($origin, $allowedOrigins, true) || preg_match('/^http:\/\/(localhost|127\.0\.0\.1|192\.168\.\d{1,3}\.\d{1,3}|10\.\d{1,3}\.\d{1,3}\.\d{1,3})(:\d+)?$/', $origin)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
        }
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
