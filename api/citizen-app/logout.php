<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

unset($_SESSION['citizen_account_id']);
respondCitizenApp(['status' => 'success', 'message' => 'Logged out.']);
