<?php
require_once __DIR__ . '/../../config/citizen_app_bootstrap.php';
citizenAppCors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respondCitizenApp(['status' => 'error', 'message' => 'Method Not Allowed.'], 405);
}

$current = $citizenAccountService->currentCitizen();
if (!$current) {
    respondCitizenApp(['status' => 'error', 'message' => 'Not logged in.'], 401);
}

// "Housing Programs" is derived reference content, not a real database table --
// one entry per housing beneficiary category, using the real scoring weights
// from BeneficiaryService so this never drifts from the actual eligibility logic.
$descriptions = [
    'Senior Citizen' => 'Applicants aged 60 and above receive priority consideration under RA 7279 vulnerable-sector provisions.',
    'PWD' => 'Applicants with a documented disability receive priority consideration under RA 7279 vulnerable-sector provisions.',
    'Calamity Victim' => 'Households displaced by a declared calamity or disaster.',
    'Informal Settler' => 'Households currently residing in informal or unauthorized settlements.',
    'Government Employee' => 'Applicants currently employed in government service.',
    'Other' => 'Applicants who do not fall under a specific vulnerability category.',
];

// Same 6-value list as housing_beneficiary_documents.document_type -- the schema
// doesn't differentiate required documents by category, so every program lists
// the same checklist.
$requiredDocuments = [
    'Valid ID', 'Proof of Income', 'Barangay Certificate',
    'Certificate of Indigency', 'Proof of Residency', 'Other',
];

$programs = [];
foreach ($beneficiaryService->getEligibilityCategories() as $category => $weight) {
    $programs[] = [
        'category' => $category,
        'eligibility_weight' => $weight,
        'description' => $descriptions[$category] ?? '',
        'required_documents' => $requiredDocuments,
    ];
}

respondCitizenApp(['status' => 'success', 'data' => $programs]);
