<?php
// Applies database/*.sql in the exact order documented in setup.md, tracked in a
// schema_migrations table so re-running on every container start/redeploy is safe --
// several of these are ALTER TABLE statements, which aren't safe to replay blindly.
// dev_data_snapshot.sql is intentionally excluded: it's synthetic local dev/demo data,
// not something a production deploy should seed automatically.

require '/var/www/html/config/database.php';

$pdo = $db->getPdo();

$pdo->exec("CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `filename` VARCHAR(255) NOT NULL PRIMARY KEY,
    `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$order = [
    'capstone_module_permissions.sql',
    'resident_management.sql',
    'housing_management.sql',
    'housing_beneficiaries.sql',
    'local_module_permissions_rename.sql',
    'retire_local_module_permissions.sql',
    'urban_planning_development_plans.sql',
    'household_status_tracking.sql',
    'housing_occupancy_relocation.sql',
    'urban_projects_infrastructure_documents.sql',
    'field_survey_forms.sql',
    'field_survey_assignments.sql',
    'field_survey_results.sql',
    'field_survey_photos.sql',
    'housing_beneficiary_documents.sql',
    'activity_logs.sql',
    'housing_beneficiary_scoring.sql',
    'zoning_clearances.sql',
    'barangay_mapping.sql',
    'permit_applications.sql',
    'gis_layers.sql',
    'housing_projects_seed.sql',
    'citizen_accounts.sql',
    'citizen_account_login_lockout.sql',
];

$applied = $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

foreach ($order as $file) {
    if (in_array($file, $applied, true)) {
        echo "  skip (already applied): {$file}\n";
        continue;
    }

    $path = "/var/www/html/database/{$file}";
    if (!file_exists($path)) {
        echo "  WARNING: {$file} not found, skipping\n";
        continue;
    }

    echo "  applying: {$file}\n";
    $pdo->exec(file_get_contents($path));
    $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)')->execute([$file]);
}

echo "Migrations complete.\n";
