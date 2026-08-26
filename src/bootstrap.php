<?php

if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Database Config
$configPath = __DIR__ . '/../config/database.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

// Load Repositories
require_once __DIR__ . '/Repositories/UserRepository.php';
require_once __DIR__ . '/Repositories/PermissionRepository.php';
require_once __DIR__ . '/Repositories/ResidentRepository.php';
require_once __DIR__ . '/Repositories/HouseholdRepository.php';
require_once __DIR__ . '/Repositories/ResidentDocumentRepository.php';
require_once __DIR__ . '/Repositories/HousingUnitRepository.php';
require_once __DIR__ . '/Repositories/HousingBeneficiaryRepository.php';
require_once __DIR__ . '/Repositories/BeneficiaryDocumentRepository.php';
require_once __DIR__ . '/Repositories/HousingOccupancyRepository.php';
require_once __DIR__ . '/Repositories/HousingRelocationRepository.php';
require_once __DIR__ . '/Repositories/DevelopmentPlanRepository.php';
require_once __DIR__ . '/Repositories/UrbanProjectRepository.php';
require_once __DIR__ . '/Repositories/InfrastructureRecordRepository.php';
require_once __DIR__ . '/Repositories/PlanningDocumentRepository.php';
require_once __DIR__ . '/Repositories/ZoningUseRegulationRepository.php';
require_once __DIR__ . '/Repositories/ZoningClearanceRepository.php';
require_once __DIR__ . '/Repositories/PermitApplicationRepository.php';
require_once __DIR__ . '/Repositories/PermitPlanDocumentRepository.php';
require_once __DIR__ . '/Repositories/FieldSurveyFormRepository.php';
require_once __DIR__ . '/Repositories/FieldSurveyAssignmentRepository.php';
require_once __DIR__ . '/Repositories/FieldSurveyResultRepository.php';
require_once __DIR__ . '/Repositories/FieldSurveyPhotoRepository.php';
require_once __DIR__ . '/Repositories/AnalyticsRepository.php';
require_once __DIR__ . '/Repositories/ActivityLogRepository.php';
require_once __DIR__ . '/Repositories/BarangayRepository.php';
require_once __DIR__ . '/Repositories/SubdivisionRepository.php';
require_once __DIR__ . '/Repositories/HousingProjectRepository.php';
require_once __DIR__ . '/Repositories/BuildingRepository.php';
require_once __DIR__ . '/Repositories/CitizenAccountRepository.php';

// Load Services
require_once __DIR__ . '/Services/AuthService.php';
require_once __DIR__ . '/Services/UserService.php';
require_once __DIR__ . '/Services/PermissionService.php';
require_once __DIR__ . '/Services/HeaderService.php';
require_once __DIR__ . '/Services/ResidentService.php';
require_once __DIR__ . '/Services/HouseholdService.php';
require_once __DIR__ . '/Services/HousingService.php';
require_once __DIR__ . '/Services/BeneficiaryService.php';
require_once __DIR__ . '/Services/HousingOccupancyService.php';
require_once __DIR__ . '/Services/HousingRelocationService.php';
require_once __DIR__ . '/Services/DevelopmentPlanService.php';
require_once __DIR__ . '/Services/UrbanProjectService.php';
require_once __DIR__ . '/Services/ZoningConformityService.php';
require_once __DIR__ . '/Services/ZoningClearanceService.php';
require_once __DIR__ . '/Services/PermitApplicationService.php';
require_once __DIR__ . '/Services/InfrastructureRecordService.php';
require_once __DIR__ . '/Services/FieldSurveyFormService.php';
require_once __DIR__ . '/Services/FieldSurveyAssignmentService.php';
require_once __DIR__ . '/Services/FieldSurveyResultService.php';
require_once __DIR__ . '/Services/GeminiService.php';
require_once __DIR__ . '/Services/ActivityLogService.php';
require_once __DIR__ . '/Services/BarangayService.php';
require_once __DIR__ . '/Services/SubdivisionService.php';
require_once __DIR__ . '/Services/HousingProjectService.php';
require_once __DIR__ . '/Services/BuildingService.php';
require_once __DIR__ . '/Services/CitizenAccountService.php';
require_once __DIR__ . '/Services/FileUploadService.php';

// Load Middleware
require_once __DIR__ . '/Middleware/SessionTimeout.php';
require_once __DIR__ . '/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/Middleware/PermissionMiddleware.php';

// Initialize Core & Middleware
$authService = new \App\Services\AuthService();

// Support dynamic basePath if defined before requiring bootstrap.php
$currentBasePath = $basePath ?? '../';
$sessionTimeout = new \App\Middleware\SessionTimeout(1800, $currentBasePath);
$sessionTimeout->handle();

// Initialize Repositories
// Note: $db is expected to be initialized by config/database.php
$userRepo = new \App\Repositories\UserRepository($db ?? null);
$permRepo = new \App\Repositories\PermissionRepository($db ?? null);
$residentRepo = new \App\Repositories\ResidentRepository($db ?? null);
$householdRepo = new \App\Repositories\HouseholdRepository($db ?? null);
$residentDocumentRepo = new \App\Repositories\ResidentDocumentRepository($db ?? null);
$housingUnitRepo = new \App\Repositories\HousingUnitRepository($db ?? null);
$housingBeneficiaryRepo = new \App\Repositories\HousingBeneficiaryRepository($db ?? null);
$beneficiaryDocumentRepo = new \App\Repositories\BeneficiaryDocumentRepository($db ?? null);
$housingOccupancyRepo = new \App\Repositories\HousingOccupancyRepository($db ?? null);
$housingRelocationRepo = new \App\Repositories\HousingRelocationRepository($db ?? null);
$developmentPlanRepo = new \App\Repositories\DevelopmentPlanRepository($db ?? null);
$urbanProjectRepo = new \App\Repositories\UrbanProjectRepository($db ?? null);
$infrastructureRecordRepo = new \App\Repositories\InfrastructureRecordRepository($db ?? null);
$planningDocumentRepo = new \App\Repositories\PlanningDocumentRepository($db ?? null);
$zoningUseRegulationRepo = new \App\Repositories\ZoningUseRegulationRepository($db ?? null);
$zoningClearanceRepo = new \App\Repositories\ZoningClearanceRepository($db ?? null);
$permitApplicationRepo = new \App\Repositories\PermitApplicationRepository($db ?? null);
$permitPlanDocumentRepo = new \App\Repositories\PermitPlanDocumentRepository($db ?? null);
$fieldSurveyFormRepo = new \App\Repositories\FieldSurveyFormRepository($db ?? null);
$fieldSurveyAssignmentRepo = new \App\Repositories\FieldSurveyAssignmentRepository($db ?? null);
$fieldSurveyResultRepo = new \App\Repositories\FieldSurveyResultRepository($db ?? null);
$fieldSurveyPhotoRepo = new \App\Repositories\FieldSurveyPhotoRepository($db ?? null);
$analyticsRepo = new \App\Repositories\AnalyticsRepository($db ?? null);
$activityLogRepo = new \App\Repositories\ActivityLogRepository($db ?? null);
$barangayRepo = new \App\Repositories\BarangayRepository($db ?? null);
$subdivisionRepo = new \App\Repositories\SubdivisionRepository($db ?? null);
$housingProjectRepo = new \App\Repositories\HousingProjectRepository($db ?? null);
$buildingRepo = new \App\Repositories\BuildingRepository($db ?? null);
$citizenAccountRepo = new \App\Repositories\CitizenAccountRepository($db ?? null);

// Initialize Services
$userService = new \App\Services\UserService($userRepo);
$permService = new \App\Services\PermissionService($permRepo);
$residentService = new \App\Services\ResidentService($residentRepo, $householdRepo, $residentDocumentRepo);
$householdService = new \App\Services\HouseholdService($householdRepo);
$housingService = new \App\Services\HousingService($housingUnitRepo);
$beneficiaryService = new \App\Services\BeneficiaryService($housingBeneficiaryRepo, $residentRepo, $housingUnitRepo, $householdRepo);
$housingOccupancyService = new \App\Services\HousingOccupancyService($housingOccupancyRepo, $residentRepo, $housingUnitRepo);
$housingRelocationService = new \App\Services\HousingRelocationService($housingRelocationRepo, $housingOccupancyRepo, $housingOccupancyService, $residentRepo, $housingUnitRepo);
$developmentPlanService = new \App\Services\DevelopmentPlanService($developmentPlanRepo);
$urbanProjectService = new \App\Services\UrbanProjectService($urbanProjectRepo);
$zoningConformityService = new \App\Services\ZoningConformityService($zoningUseRegulationRepo);
$zoningClearanceService = new \App\Services\ZoningClearanceService($zoningClearanceRepo, $residentRepo, $zoningConformityService);
$permitApplicationService = new \App\Services\PermitApplicationService($permitApplicationRepo, $residentRepo);
$infrastructureRecordService = new \App\Services\InfrastructureRecordService($infrastructureRecordRepo);
$fieldSurveyFormService = new \App\Services\FieldSurveyFormService($fieldSurveyFormRepo);
$fieldSurveyAssignmentService = new \App\Services\FieldSurveyAssignmentService($fieldSurveyAssignmentRepo, $fieldSurveyFormRepo, $residentRepo, $householdRepo);
$fieldSurveyResultService = new \App\Services\FieldSurveyResultService($fieldSurveyResultRepo, $fieldSurveyAssignmentRepo);
$geminiService = new \App\Services\GeminiService($analyticsRepo);
$activityLogService = new \App\Services\ActivityLogService($activityLogRepo);
$barangayService = new \App\Services\BarangayService($barangayRepo, $subdivisionRepo, $housingProjectRepo, $buildingRepo);
$subdivisionService = new \App\Services\SubdivisionService($subdivisionRepo);
$housingProjectService = new \App\Services\HousingProjectService($housingProjectRepo);
$buildingService = new \App\Services\BuildingService($buildingRepo);
$citizenAccountService = new \App\Services\CitizenAccountService($citizenAccountRepo, $residentRepo, $residentService, $householdService);
$fileUploadService = new \App\Services\FileUploadService();

// Initialize Header Service (and build user)
$headerService = new \App\Services\HeaderService($userService, $permService, $authService);
$headerUser = $headerService->buildHeaderUser();
