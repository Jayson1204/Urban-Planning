# CIVENTRAL Urban Planning — Setup

Local development setup for this capstone project (PHP + XAMPP, no framework, no dev server).

## Prerequisites

- **XAMPP** (Apache + MySQL + PHP). Project assumes the default XAMPP install path `C:\xampp`.
- **Composer** (only dependency is PHPMailer).
- **Git**.

## 1. Clone into the XAMPP htdocs folder

The app is served directly from disk by Apache, so it must live under `htdocs`:

```
C:\xampp\htdocs\Civentral-UrbanPlanning
```

If cloning fresh:

```bash
cd /c/xampp/htdocs
git clone <repo-url> Civentral-UrbanPlanning
```

## 2. Start Apache and MySQL

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

## 3. Install PHP dependencies

```bash
cd /c/xampp/htdocs/Civentral-UrbanPlanning
composer install
```

## 4. Create the local database

Using phpMyAdmin (`http://localhost/phpmyadmin`) or the MySQL CLI, create an empty database, e.g. `civentral_urbanplanning`. `config/database.php` will also auto-create the database on first connection if it doesn't exist yet, as long as the configured user has privileges to do so.

## 5. Configure environment variables

Copy the example file and fill in real values:

```bash
cp .env.example .env
```

`.env` is gitignored — never commit it. Keys used by `config/database.php` and the app:

| Key | Purpose |
|---|---|
| `APP_ENV`, `APP_DEBUG` | environment flag / debug output toggle |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | local MySQL connection (XAMPP default is `root` with no password) |
| `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` | reCAPTCHA on login/forms |
| `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` | outgoing mail via PHPMailer |
| `EXPO_PUBLIC_API_BASE_URL` | base URL the local proxy endpoints (`api/*` files using `config/proxy.php`) forward to; defaults to the production API at `https://civentral.tech/api/employee` |
| `GEMINI_API_KEY` | server-side only, used by the AI Planning Assistant module — never expose to frontend JS |
| `GEMINI_MODEL`, `GEMINI_MODEL_COMPLEX` | model IDs for the default (cheap/fast) and complex-request tiers; see `src/Services/GeminiService.php` |

Ask a teammate (Scrum Master) for real reCAPTCHA/SMTP/Gemini credentials and remote API access — this project has no local auth backend of its own; login and RBAC proxy to the shared production system.

## 6. Apply database migrations

There is no migration tool. SQL files live in `database/*.sql` (descriptively named, not numbered) and must be applied manually **in this exact order** — later files have foreign keys on tables earlier ones create:

```bash
/c/xampp/php/php.exe -r "require 'config/database.php'; \$db->getPdo()->exec(file_get_contents('database/resident_management.sql'));"
```

Repeat for each file below, substituting the filename, in this order:

1. `capstone_module_permissions.sql`
2. `resident_management.sql`
3. `housing_management.sql`
4. `housing_beneficiaries.sql`
5. `local_module_permissions_rename.sql`
6. `retire_local_module_permissions.sql`
7. `urban_planning_development_plans.sql`
8. `household_status_tracking.sql`
9. `housing_occupancy_relocation.sql`
10. `urban_projects_infrastructure_documents.sql`
11. `field_survey_forms.sql`
12. `field_survey_assignments.sql`
13. `field_survey_results.sql`
14. `field_survey_photos.sql`
15. `housing_beneficiary_documents.sql`
16. `activity_logs.sql`
17. `housing_beneficiary_scoring.sql`
18. `zoning_clearances.sql`
19. `barangay_mapping.sql`
20. `permit_applications.sql`
21. `gis_layers.sql`
22. `housing_projects_seed.sql`
23. `citizen_accounts.sql`
24. `citizen_account_login_lockout.sql`
25. `ai_usage_logs.sql`

New migration files are appended to this list (in whatever order their own foreign keys require) as new modules are built — update this list when you add one.

## 7. Run the app

With Apache running, browse to:

```
http://localhost/Civentral-UrbanPlanning/
```

Log in with an account provisioned in the shared production system (auth/RBAC is owned by the Scrum Master, not this local database).

## 8. Verify

- `php -l path/to/file.php` — lint a single PHP file.
- Confirm Apache/MySQL are green in the XAMPP Control Panel.
- Confirm `.env` has valid `DB_*` values and the target database exists (or can be auto-created).
- Load a local-DB module page (e.g. Resident Management) to confirm the DB connection and migrations succeeded.
- Attempt login to confirm the remote proxy (`EXPO_PUBLIC_API_BASE_URL`) is reachable.

## 9. Deploying (Docker / Dokploy)

This is the only deployment path this app has — there's no other hosting setup. A `Dockerfile` builds a PHP 8.2 + Apache image; `docker/entrypoint.sh` waits for the database then runs `docker/run-migrations.php`, which applies every `database/*.sql` file in the order from section 6 above, tracked in a `schema_migrations` table so redeploys don't replay `ALTER TABLE` statements. `dev_data_snapshot.sql` is deliberately excluded from this automatic run (synthetic dev data, not meant for a real deploy) — apply it manually via Dokploy's terminal if you want seeded demo data.

**In Dokploy**, the project needs two services:
1. A **MySQL database** service (Dokploy-managed) — note its internal hostname, port, user, password, and database name for the env vars below.
2. An **Application** service pointing at this repo, build type Dockerfile.

**Environment variables** to set on the Application service (mirrors `.env.example`):

| Key | Value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_BASE_URL` | the deployed domain, e.g. `https://your-app.traefik.me` |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | from the Dokploy MySQL service |
| `EXPO_PUBLIC_API_BASE_URL` | `https://civentral.tech/api/employee` (production RBAC/auth, owned by the Scrum Master) |
| `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` | ask the Scrum Master |
| `SMTP_*` | ask the Scrum Master (rotate first if reusing a credential that's ever been committed anywhere) |
| `GEMINI_API_KEY` | your own Gemini API key — server-side only |

**Volume**: mount a persistent volume at `/var/www/html/uploads` on the Application service, or every uploaded resident/beneficiary document is lost on the next redeploy (containers are ephemeral; the code writes there via `FileUploadService`).

**Domain**: Dokploy auto-generates a `*.traefik.me`-style HTTPS domain with no DNS setup needed if you don't attach a custom one — fine for initial deployment.

## Notes

- No JS/CSS build step: Tailwind v4 loads via CDN (`type="text/tailwindcss"` inline block in `header.php`), and JS bridges under `assets/js/<module>/` load as plain `<script>` tags cache-busted by `ASSET_VERSION` in `assets/js/app.js`.
- There is no automated test framework; conventions for ad hoc verification scripts are in `CLAUDE.md`.
- See `CLAUDE.md` for architecture (remote-proxy vs local-DB API endpoints, RBAC, module scaffolding recipe) and `docs/module-guide.md` for what each module does.
