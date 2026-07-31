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

// Load Services
require_once __DIR__ . '/Services/AuthService.php';
require_once __DIR__ . '/Services/UserService.php';
require_once __DIR__ . '/Services/PermissionService.php';
require_once __DIR__ . '/Services/HeaderService.php';

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

// Initialize Services
$userService = new \App\Services\UserService($userRepo);
$permService = new \App\Services\PermissionService($permRepo);

// Initialize Header Service (and build user)
$headerService = new \App\Services\HeaderService($userService, $permService, $authService);
$headerUser = $headerService->buildHeaderUser();
