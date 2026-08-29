# CIVENTRAL Urban Planning — Technical System Documentation

Scope: this document covers the **Urban Planning capstone modules** in this repository (`C:\xampp\htdocs\Civentral-UrbanPlanning`) — Resident Management, Housing Management, Field Survey, Urban Planning/Zoning/Permits, Barangay/GIS Mapping, Reports, Activity Log, AI Assistant, and the Citizen Mobile backend. The auth/dashboard shell and shared RBAC backend (`civentral.tech`) are owned by the Scrum Master and are documented here only to the extent this project integrates with them. Sections (or sub-points within a section) that describe infrastructure this project does not have — a CI/CD pipeline, a formal APM stack, a published license — say so explicitly rather than being omitted, per the working convention in `CLAUDE.md`.

Companion docs: `setup.md` (dev environment setup), `docs/module-guide.md` (functional walkthrough of each module), `docs/activity-log.md` (chronological build log — the primary source for the troubleshooting history in A.12), `docs/mobile-app-plan.md` (Civentral-CitizenMobile plan).

---

## A.1 System Architecture

CIVENTRAL Urban Planning is a **server-rendered PHP web application with no framework**, served by Apache (via XAMPP) directly from disk — there is no build step and no application server process beyond Apache/PHP-FPM's own request lifecycle.

**Architectural layers** (top to bottom):

1. **Presentation** — `pages/<area>/*.php`. Each page sets `$basePath`, includes `includes/header.php` (loads Tailwind v4 via CDN, Font Awesome, session/header data) and `includes/sidebar.php` (RBAC-gated nav), renders server-side HTML, then includes `includes/footer.php`.
2. **Client-side bridge layer** — `assets/js/<module>/{api,table,modal,filters,events,app}.js`. Each module is a self-contained "bridge" that self-gates on the presence of its page's DOM anchor. `assets/js/app.js` defines `window.loadCiventralScript()`, an order-preserving dynamic `<script>` loader that appends `?v=ASSET_VERSION` for cache-busting.
3. **API layer** — `api/employee/*.php`, `api/citizen/*.php`, `api/citizen-app/*.php`. Splits into two distinct backend patterns (see A.10).
4. **Domain/service layer** — `src/Services/*.php` (28 classes) — validation, whitelisting, and cross-module business rules.
5. **Data-access layer** — `src/Repositories/*.php` (32 classes) — one repository per table/entity, built on PDO.
6. **Database** — local MySQL (via XAMPP), schema in `database/*.sql` (descriptively named, apply order documented in `setup.md`).
7. **External systems** — the shared production system at `civentral.tech` (auth/RBAC/users/roles), Google Gemini API, SMTP (Gmail), Google reCAPTCHA, OpenStreetMap Overpass API.

**Dependency injection**: `src/bootstrap.php` is the composition root. It starts the session and output buffering, loads `config/database.php`, `require_once`s every repository and service (no autoloader — Composer's only registered package is PHPMailer), wires repositories into services by constructor injection, runs `SessionTimeout`, and builds `$headerUser` via `HeaderService`. Every local-DB page and API endpoint includes this one file.

**Two coexisting client applications** share this backend:
- This web app (staff/admin-facing, session-cookie auth against the shared production system).
- `Civentral-CitizenMobile` (Expo/React Native, a separate sibling repository) — a citizen-facing mobile client that talks to `api/citizen-app/*.php` using its own **local** session-cookie auth (`citizen_accounts` table), independent of the staff RBAC system.

**Reference diagrams** (checked into `diagram/`): `ERD_URBAN.png` / `ERD.drawio.png` (entity-relationship), `DFD-LEVEL0.png` / `DFD-LEVEL1.png` (data flow), `USECASE_URBAN.png` / `USECASECAP.png` (use case), `WORKFLOW.png` (process workflow), `Toolstack.jpg` / `TOOLSTACK.png` (technology stack).

## A.2 Information System Integration

This system integrates with one external production system and several third-party APIs, rather than with other internal enterprise systems:

- **Shared production system (`civentral.tech`)** — owns authentication, RBAC (users/roles/permissions/resources/modules), the Citizen Account Directory, notifications, and audit logs. Integration is via HTTP proxying (`config/proxy.php`): every remote-proxy endpoint relays the caller's request to `https://civentral.tech/api/employee/...` (overridable via `EXPO_PUBLIC_API_BASE_URL`) and keeps the remote session alive by storing/replaying `PHPSESSID` in `$_SESSION['remote_phpsessid']`. Because the remote list endpoint can lag behind a just-created remote record, write flows in this codebase optimistically reflect changes client-side rather than waiting on a fresh `GET`.
- **Civentral-CitizenMobile (Expo/React Native)** — consumes `api/citizen-app/*.php` over the LAN during development (`credentials: 'include'` fetches, matched by a permissive CORS rule for private LAN ranges in `config/citizen_app_bootstrap.php`).
- **Google Gemini API** — `src/Services/GeminiService.php` calls the `generateContent` REST endpoint via cURL (matching the existing proxy's cURL conventions rather than adding an HTTP client library). Answers are grounded by injecting a live `AnalyticsRepository` snapshot (KPIs, totals, chart breakdowns) into Gemini's `systemInstruction`, so the AI Planning Assistant never invents numbers.
- **SMTP (Gmail) via PHPMailer** — `config/mailer.php`'s `sendSystemEmail()` sends the citizen staff-invite, password-reset, and set-password emails.
- **Google reCAPTCHA v2** — embedded on the login page (`login.php`) against `RECAPTCHA_SITE_KEY`/`RECAPTCHA_SECRET_KEY`.
- **OpenStreetMap (Overpass API)** — one-off, non-recurring data import scripts (`scripts/import/`) that fetch and clip real building-footprint and named-subdivision data for Caloocan; not a live/runtime integration.

Cross-module integration *within* this codebase is done by direct service/repository composition (constructor injection in `bootstrap.php`), not by an event bus or message queue — e.g. `HousingRelocationService` is constructed with `HousingOccupancyService` so a relocation can automatically close/open occupancy records in one call.

## A.3 Application Design and Development

**Pattern**: a lightweight layered architecture (Repository → Service → API endpoint → Page → JS bridge), applied consistently across every module, without a framework. Classes live under the `App\Repositories`, `App\Services`, and `App\Middleware` namespaces but are loaded via explicit `require_once` in `bootstrap.php`, not Composer PSR-4 autoloading.

**Standard module scaffolding** (from `CLAUDE.md`, followed for every module built): `database/x.sql` → `Repository` (find/paginate/stats/create/update/setStatus) → `Service` (validate + whitelist-sanitize + business rules) → register both in `bootstrap.php` → `api/employee/x.php` (auth + `PermissionMiddleware::requireResource` + REST + `respond()`) → `pages/<area>/x.php` view → `assets/js/<module>/` bridge → sidebar entry → production Module Management + Permission Builder entries.

**Implemented modules** (grouped by domain, per `docs/module-guide.md`):
- **Resident Management** — Resident Directory, Household Management (the identity source every other module references).
- **Housing Management** — Housing Units, Beneficiary Registry (with eligibility scoring + duplicate detection), Occupancy, Relocation Records.
- **Field Survey** — Survey Forms, Survey Assignments, Survey Results, computed Survey History.
- **Urban Planning** — Development Plans, Zoning Clearances (automated conformity pre-screening + fee computation + printable certificate), Urban Projects, Infrastructure Records, Subdivision & Building Permit Review (discipline-based review workflow), Barangay/GIS Mapping (barangays, subdivisions, housing projects, building footprints).
- **Cross-cutting** — Program Analytics, local Activity Log, AI Planning Assistant (Gemini-backed chat), Reports (filterable/exportable/printable listings over the record-holding modules).
- **Citizen-facing backend** — local `citizen_accounts` (self-service registration linked 1:1 to a `resident`), document upload for housing beneficiary applications, forgot/reset password, profile edit, read-only Housing Programs/Housing Projects.

**Frontend conventions**: Tailwind v4 loaded via the browser CDN build (`type="text/tailwindcss"` inline block in `header.php`, brand palette as `--color-brand-*` custom properties) — no build step, no bundler. Vanilla JS, split per module into `api.js` (fetch calls), `table.js` (render), `modal.js` (create/edit/view), `filters.js`, `events.js`, and an `app.js` entry that self-registers with the global loader. Chart-heavy read-only pages (Dashboard, Analytics, Reports) load Chart.js directly instead of using the CRUD bridge system, since they have no create/edit/delete flows.

## A.4 Database Schema and Data Management

**Engine**: local MySQL (utf8mb4, `utf8mb4_unicode_ci`), accessed exclusively through PDO with `PDO::ATTR_EMULATE_PREPARES => false`. This has a real, documented gotcha: a named placeholder cannot be reused twice in one query (throws `HY093`) — the convention is a single placeholder shared via `CONCAT_WS(' ', a, b, c) LIKE :search` rather than repeating `:search`.

**No migration tool.** Schema changes are plain SQL files under `database/`, descriptively named (no phase numbering), applied manually via a one-line PHP CLI invocation (`config/database.php` + `PDO::exec(file_get_contents(...))`) in the exact order below (later files have foreign keys on tables earlier ones create) — the authoritative copy of this order lives in `setup.md`:

| Order | File | Adds |
|---|---|---|
| 1 | `capstone_module_permissions.sql` | capstone module/permission bootstrapping |
| 2 | `resident_management.sql` | `residents` |
| 3 | `housing_management.sql`, `housing_beneficiaries.sql` | `housing_units`, beneficiary registry |
| 4 | `local_module_permissions_rename.sql` | local module registry scaffolding |
| 5 | `retire_local_module_permissions.sql` | retires the local permission table in favor of production RBAC |
| 6 | `urban_planning_development_plans.sql` | `development_plans` |
| 7 | `household_status_tracking.sql` | `households` |
| 8 | `housing_occupancy_relocation.sql` | occupancy + relocation ledgers |
| 9 | `urban_projects_infrastructure_documents.sql` | `urban_projects`, `infrastructure_records`, planning documents |
| 10 | `field_survey_forms.sql`, `field_survey_assignments.sql`, `field_survey_results.sql`, `field_survey_photos.sql` | Field Survey module (4 tables, in this dependency order) |
| 11 | `housing_beneficiary_documents.sql` | `housing_beneficiary_documents` |
| 12 | `activity_logs.sql` | `activity_logs` |
| 13 | `housing_beneficiary_scoring.sql` | beneficiary eligibility scoring fields |
| 14 | `zoning_clearances.sql` | `zoning_clearances`, regulation matrix |
| 15 | `barangay_mapping.sql` | `barangays` (188 rows) + lat/lng on `housing_units` |
| 16 | `permit_applications.sql` | `permit_applications`, `permit_discipline_reviews`, `permit_application_reviews`, `permit_plan_documents` |
| 17 | `gis_layers.sql`, `housing_projects_seed.sql` | `subdivisions`, `housing_projects`, `buildings` (74,967 rows) |
| 18 | `citizen_accounts.sql` | `citizen_accounts` |
| 19 | `citizen_account_login_lockout.sql` | `failed_login_attempts`/`locked_until` on `citizen_accounts` (brute-force lockout) |

(`dev_data_snapshot.sql` is a checked-in local seed/dev-data dump, not a schema migration.)

**Data management conventions**:
- Local-DB API `DELETE` never hard-deletes — it flips a status field to an archived state (`setStatus`), matching the REST convention documented in `CLAUDE.md`.
- Append-only audit tables for legally-sensitive workflows: `permit_application_reviews` (permit review/comment log, `resubmission_round` on every row) and the equivalent stage-change log inside Zoning Clearances — both record reviewer, role, and required remarks per transition rather than overwriting a status field silently.
- Versioned documents: `permit_plan_documents` — re-uploading the same `document_type` bumps `version_number` and marks the prior row `Superseded` instead of deleting it.
- No ORM. Data access is either raw PDO in each `Repository`, or the small `Database` helper class (`select`/`insert`/`update`/`delete`) in `config/database.php` for simple cases.
- The local database has **no tables for the production system's own entities** (users, roles, permissions) — those are proxied live, never mirrored locally, beyond the transient `$_SESSION['remote_phpsessid']`.

## A.5 Network Configuration

- **Local development**: XAMPP's bundled Apache and MySQL, default ports (80 for HTTP, 3306 for MySQL), app reachable at `http://localhost/Civentral-UrbanPlanning/`. No reverse proxy, load balancer, or separate application-server process — Apache serves PHP directly from `htdocs`.
- **Outbound connections this app makes**:
  - `https://civentral.tech/api/employee/...` — shared production RBAC/auth backend (`config/proxy.php`, cURL, TLS **with peer verification disabled** — see A.7).
  - `https://generativelanguage.googleapis.com` (Gemini) — server-side only, key never sent to the frontend.
  - `smtp.gmail.com:587` (STARTTLS) — outgoing mail via PHPMailer.
  - `https://www.google.com/recaptcha/...` — reCAPTCHA verification.
  - Overpass API mirrors (`overpass-api.de`, `lz4.overpass-api.de`, `overpass.kumi.systems`) — one-off, not a runtime dependency of the running app.
- **CORS**: only the citizen-mobile-facing `api/citizen-app/*` family sets CORS headers, via `citizenAppCors()` in `config/citizen_app_bootstrap.php`. Its origin allow-list is extended to permit private LAN ranges (`192.168.x.x`, `10.x.x.x`) at any port specifically so the Expo dev client on a phone/emulator on the same LAN can reach the API during development; this is intentionally not gated behind an environment flag since the whole `api/citizen-app` family is local-DB/dev-only and never proxied to production.
- No other endpoint family sets CORS headers — the staff web app is same-origin (server-rendered pages calling their own API on the same host).

## A.6 Deployment and Infrastructure

There is **no automated deployment pipeline** — deployment is entirely manual, documented step-by-step in `setup.md`:

1. Clone/copy the repo into `C:\xampp\htdocs\Civentral-UrbanPlanning` (Apache serves directly from disk; the app cannot run anywhere else without matching this path convention).
2. Start Apache + MySQL from the XAMPP Control Panel.
3. `composer install` (only dependency: PHPMailer).
4. Create the local database (or let `config/database.php` auto-create it on first connect if the DB user has privileges).
5. Copy `.env.example` → `.env` and fill in real values (DB credentials, reCAPTCHA, SMTP, Gemini key, remote API base URL).
6. Apply every `database/*.sql` file in the order documented in `setup.md`, via the PHP CLI one-liner in `CLAUDE.md`/`setup.md`.
7. Browse to `http://localhost/Civentral-UrbanPlanning/`.

**Infrastructure**: a single-machine XAMPP stack (Windows) for local development; no containerization (no Dockerfile/`docker-compose.yml` in the repo), no cloud hosting configuration for this application. The production auth/RBAC backend it integrates with (`civentral.tech`) is infrastructure owned and operated by the Scrum Master, outside this repository's scope.

**Static assets**: no bundler or build step for JS/CSS. Cache invalidation is handled by query-string versioning — bridge-loaded JS via a manually-bumped `ASSET_VERSION` constant in `assets/js/app.js`, and directly-loaded scripts (`dashboard-analytics.js`, `reports.js`, `analytics.js`) via `filemtime()`-based versioning in the including PHP file.

**Mobile client**: `Civentral-CitizenMobile` is built and verified with standard Expo tooling (`npx expo export -p web`, `npx tsc --noEmit`, `npx expo lint`) but, per the project's activity log, has not yet been built for or submitted to an app store — it currently runs via the Expo dev client / web export only.

## A.7 Security Measures

- **Authentication**: session-based (`$_SESSION['user_id']` for staff, checked by `AuthService::isLoggedIn()`). Staff credential verification itself is delegated to the production system via the proxy; this repo does not store or verify staff passwords. Citizen accounts (`citizen_accounts.password_hash`) are hashed locally with PHP's `password_hash()`/`password_verify()` (bcrypt via `PASSWORD_DEFAULT`).
- **Session timeout**: a 1800-second (30-minute) idle timeout is enforced on every request through `src/Middleware/SessionTimeout.php`, run unconditionally in `bootstrap.php`; an expired session is redirected to `logout.php`.
- **Authorization (RBAC)**: `PermissionMiddleware::requireResource()` first requires an authenticated session, then bypasses all checks for superadmin/global-access accounts, then checks the caller's `granted_resources` (from the production system) against the requested resource with fuzzy string matching. By explicit design it is **fallback-permissive**: if a session has no `granted_resources` at all, or if no configured resource matches, any authenticated user is still allowed through. This is a documented, intentional trade-off (see `CLAUDE.md`) rather than an oversight — the practical effect is that a dedicated resource mainly governs sidebar visibility and Create/Edit button gating, not hard API-level denial, for modules that ride a shared/general resource grant.
- **Anti-automation on login**: Google reCAPTCHA v2 on the staff login form.
- **File upload hardening** (`FileUploadService`, used by resident documents, beneficiary documents, permit plan documents, field survey photos): server-side MIME/extension whitelist, a 5MB size cap, and randomly generated stored filenames (the original filename is never trusted as a path). `uploads/.htaccess` additionally disables the PHP engine and every common CGI/script handler inside the upload directories, so even a successfully-uploaded malicious file can never be executed by Apache regardless of extension spoofing.
- **Secrets management**: `.env` (DB credentials, SMTP credentials, reCAPTCHA secret, Gemini API key) is gitignored and never committed; `.gitignore` also blanket-excludes certificate/key file extensions (`*.pem`, `*.key`, `*.p12`, `*.crt`, `id_rsa*`) and any `secrets*.php`/`config/secrets*.php`. Uploaded files (which may contain resident PII) are excluded from version control entirely except for `.gitkeep`/`.htaccess` placeholders.
- **SQL injection mitigation**: 100% PDO prepared statements with parameter binding across all repositories; no raw string interpolation of user input into SQL was found in the local-DB layer.
- **Email enumeration protection**: `POST /api/citizen-app/forgot-password.php` always returns the same generic success response regardless of whether the email matched an account, deliberately different from `register.php`, which does reveal a duplicate-email conflict (an accepted UX trade-off for that specific flow, per the project's activity log).
- **Known gaps, stated plainly rather than glossed over**:
  - `config/proxy.php` calls the production API with `CURLOPT_SSL_VERIFYPEER => false`, disabling TLS certificate verification on that outbound connection.
  - No CSRF token mechanism exists anywhere in the codebase (state-changing requests rely on same-origin session cookies alone).
  - No rate-limiting/throttling layer was found on any local-DB or citizen-app endpoint beyond reCAPTCHA on the staff login form.

## A.8 Testing and Quality Assurance

There is **no automated test framework or CI test suite** in this repository (no PHPUnit, no test runner configured). The established convention instead, per `CLAUDE.md` and consistently followed in `docs/activity-log.md`, is:

- **Standalone PHP CLI verification scripts**, written to the scratch/temp directory (not committed), that `require config/database.php` plus the specific repository/service classes under test and run scenario-based assertions against the real local database, cleaning up test data afterward. Examples from the activity log: 44 assertions for the Permit Application workflow (fee math, discipline transitions, issuance gating), 21 for the GIS import layers, 25 for Analytics KPI math, 6 for the Activity Log service.
- **`php -l` linting** on every changed/new PHP file before considering a change complete.
- **Live manual browser walkthroughs** for every shipped module — logged per phase in the activity log (e.g. full CRUD + cross-module rule verification for the Field Survey module, a real end-to-end citizen registration → approval → resident-creation walkthrough).
- For the sibling mobile client: `npx tsc --noEmit`, `npx expo lint`, and `npx expo export -p web` are run as the pre-verification gate on every change, since that project also has no automated test suite.

**Known QA gap, tracked honestly rather than hidden**: several modules (Subdivision & Building Review, the Phase 18 GIS layers, several citizen-mobile phases) were verified only via CLI scripts and HTTP `curl` round trips, with the live-browser walkthrough explicitly flagged as still outstanding in the activity log — a recurring open item worth closing before relying on those flows in a live demo/defense.

## A.9 System Monitoring and Maintenance

There is no dedicated application-performance-monitoring (APM), log-aggregation, or alerting stack. Observability is limited to:

- **PHP/Apache logs**: uncaught errors and explicit `error_log()` calls (e.g. `Mailer Error` in `config/mailer.php`) land in PHP's error log; Apache's access log has been used ad hoc during development to confirm request outcomes (e.g. verifying CSV export byte counts by status code).
- **Local Activity Log module** (`activity_logs` table, `ActivityLogService::record()`) — an application-level audit trail instrumented into the create/update/archive/delete paths of all 15 local-DB write endpoints. `record()` is wrapped in try/catch so a logging failure can never break the write it's attached to. Exposed to staff via a filterable, read-only page (module/action/user/date/search).
- **Program Analytics module** (`AnalyticsRepository`) — cross-module KPI aggregation (housing occupancy rate, survey completion rate, urban project completion rate) surfaced both on the main Dashboard and a dedicated Analytics page; functions as an operational health view even though it wasn't built as infrastructure monitoring.
- `APP_DEBUG` (`.env`) toggles debug-level output.

There are no scheduled/cron maintenance jobs. The one-off OSM data-import scripts under `scripts/import/` are run manually and are idempotent (delete-then-reimport by `source='OpenStreetMap'`) rather than scheduled.

## A.10 APIs and Integration Points

Every endpoint under `api/employee/*` and `api/citizen/*` is one of two patterns (see `CLAUDE.md`):

**1. Remote-proxy endpoints** — `require config/proxy.php`, forward to `civentral.tech`, relay the remote session cookie. Covers: `login.php`, `users.php`, `roles.php`, `permissions.php`, `resources.php`, `modules.php`, `departments.php`, `access-control.php`, `actions.php`, `change-password.php`, `get-profile.php`, `profile.php`, `login-history.php`, `notifications.php`, `audit-logs.php`, `resend-otp.php`, `verify-otp.php`. `api/citizen/*` (`login.php`, `register.php`, `check-account.php`, `get-accounts.php`, `get-directory.php`, `get-profile.php`, `change-password.php`, `resend-otp.php`, `verify-otp.php`, `update-status.php`) is the equivalent proxy for the production Citizen Account Directory — unrelated to this project's own citizen accounts.

**2. Local-DB endpoints** — `require src/bootstrap.php`, operate on local MySQL via repositories/services. REST shape: `GET` list (paginated) / `?action=stats` / `?id=` detail; `POST` create; `PUT` update; `DELETE` soft-archive; every response goes through `respond([...])` with a `status` field. Covers: `residents.php`, `households.php`, `resident-documents.php`, `housing-units.php`, `housing-beneficiaries.php`, `beneficiary-documents.php`, `housing-occupancy.php`, `housing-relocations.php`, `development-plans.php`, `urban-projects.php`, `infrastructure-records.php`, `planning-documents.php`, `zoning-clearances.php`, `permit-applications.php`, `permit-plan-documents.php`, `field-survey-forms.php`, `field-survey-assignments.php`, `field-survey-results.php`, `field-survey-photos.php`, `barangays.php`, `subdivisions.php`, `housing-projects.php`, `buildings.php`, `activity-logs.php`, `analytics.php`, `ai-assistant.php`, `reports.php`.

**3. Citizen-app endpoints** (`api/citizen-app/*`) — a third family, specific to this project's own **local** citizen identity (`citizen_accounts`, distinct from the production Citizen Account Directory above). Deliberately bypass `src/bootstrap.php` (citizens aren't staff sessions), loading only `config/citizen_app_bootstrap.php` + the specific repos/services needed: `register.php`, `login.php`, `logout.php`, `profile.php` (`GET`/`PUT`), `beneficiary-documents.php`, `set-password.php`, `forgot-password.php`, `housing-projects.php`, `housing-programs.php`. Session-cookie based, CORS-enabled for LAN mobile testing.

**External API integrations**: Google Gemini `generateContent`/multi-turn chat (`GeminiService`), OpenStreetMap Overpass API (one-off import tooling only, not a live endpoint dependency).

## A.11 User Documentation

Existing documentation lives under `docs/` and `setup.md`, all kebab-case Markdown per project convention:

- `setup.md` — developer environment setup (prerequisites, cloning, DB setup, `.env` configuration, migrations, verification steps).
- `docs/module-guide.md` — a functional guide to what each module does and how the four capstone module groups connect end-to-end (written for a developer/reviewer audience, not end users).
- `docs/activity-log.md` — a chronological engineering log of every phase built, including rationale, bugs found/fixed, and open follow-ups. Primarily a project-history record, not user-facing.
- `docs/mobile-app-plan.md`, `docs/barangay-mapping-module.md`, `docs/thesis-scope-gap-analysis.md` — planning/scoping references.
- `docs/citizen-self-registration-mobile-plan.md`, `docs/citizen-document-upload-mobile-api.md` — marked superseded in place (kept for history after the citizen-accounts design reversal) rather than deleted.

**Not yet built**: a dedicated end-user manual (e.g., step-by-step guides for filing a Zoning Clearance, reviewing a Permit Application, or using the Barangay Map) does not exist in this repository. In-app affordances (status badges, review timelines, printable certificates with structured findings, quick-action buttons on the AI Assistant) substitute for some of that need today, but no standalone user-facing documentation deliverable has been produced.

## A.12 Technical Issues and Troubleshooting

Recurring issues encountered during development, and their resolutions, drawn from `docs/activity-log.md`:

| Issue | Cause | Resolution |
|---|---|---|
| Browser serves stale bridge JS after an edit | `ASSET_VERSION` in `assets/js/app.js` not bumped | Bump `ASSET_VERSION` after every bridge-loaded JS change |
| `PDOException: SQLSTATE[HY093]` | A named placeholder (e.g. `:search`) reused more than once in one query under `EMULATE_PREPARES => false` | Use a single placeholder shared via `CONCAT_WS(' ', a, b, c) LIKE :search` |
| Privilege escalation in Permission Builder — revoking a module for a role silently left other unrelated modules (including Main Controls) granted | `fetchPermissionsData()` had a block that auto-injected every action, for every role, for any module/resource still flagged `is_custom` (an unreconciled client-side ID) | Removed the auto-grant block; new/unsynced modules now start with zero granted permissions per role |
| App-wide fatal error on every page | `bootstrap.php` instantiated `BeneficiaryService` with 3 constructor args after its signature grew to 4 (`$householdRepo`), from an in-progress unrelated change | Passed `$householdRepo` through at the call site |
| Overpass API imports stalled with 429s / 504s across mirrors | Free public Overpass API rate limits heavily under sustained use and has mirror-wide outage windows | Query per-barangay rather than citywide (also needed to stay under a 1GB PHP memory limit); re-run only the barangays left at zero coverage after an outage |
| Metro bundler 500s on a module that exists on disk (`Civentral-CitizenMobile`) | Metro's resolver/watchman cache predates a newly-installed package | `expo start --web --clear` (a plain reload/restart is insufficient after installing a new dependency mid-session) |
| React Native runtime deprecation warning for shadow props | Legacy `shadowColor`/`shadowOffset`/`shadowOpacity`/`shadowRadius`/`elevation` are deprecated on the RN version in use (New Architecture) | Switched to the single `boxShadow` style prop |
| Expo Router route not resolving as expected | A plain directory's `index.tsx` registers as `/path/index`, not `/path` — the route-group flattening only applies to `(group)` folders | Use a flat `path.tsx` sibling file for the list route, keep only dynamic segments (`[id].tsx`) inside the directory |
| Conditional-hook-call lint failure not caught by `tsc` | `useState` called after an early-return guard | `expo lint` (not `tsc --noEmit` alone) is required to catch React hooks-rules violations |

**Standing open items**, tracked transparently in the activity log rather than assumed resolved: several GIS/permit-workflow modules and citizen-mobile phases still need a live authenticated browser walkthrough before being fully trusted (Claude-in-Chrome extension wasn't connected in those sessions); the citizen password-reset/staff-invite email flow has been sent successfully but inbox delivery was never visually confirmed.

## A.13 Version Control and Source Code Repository

- **VCS**: Git. Default/primary branch is `main`.
- **Remotes**: two configured —
  - `origin` → `https://github.com/Suruizzzzzz/civentral-education.git` (the shared team/capstone repository this project was merged into, including the pre-existing auth/dashboard/audit-trail modules built by teammates).
  - `urban-planning` → `https://github.com/Jayson1204/Urban-Planning.git` (a personal remote scoped to this contributor's module work).
- **Workflow**: development proceeds directly against `main` in this working copy (no local feature branches present at the time of writing) with focused, phase-scoped commits — recent history shows one commit per module/feature batch (e.g. "Add Subdivisions, Buildings, Housing Projects, Permit Applications modules, GIS mapping layers, citizen accounts/app API, and OSM import scripts"), consistent with the project's own working-style guidance to build one module at a time and keep commits atomic.
- **`.gitignore` hygiene**: excludes `.env`/`.env.*` (keeping `.env.example`), all common credential/certificate file types, `/vendor` and `/node_modules`, log files, database dumps/backups (while explicitly keeping `database/` schema migrations), and the contents of `/uploads/**` (which may contain resident PII) while preserving `.gitkeep`/`.htaccess` placeholders.
- The citizen mobile client (`Civentral-CitizenMobile`) is a **separate sibling repository**, not a subtree/submodule of this one; per the activity log it has its own independent Git history that the user manages themselves.

## A.14 DevOps and CI/CD

No CI/CD pipeline exists for this project: there is no `.github/workflows` directory, no other CI configuration file, and no automated build, test, or release pipeline. Every step described in A.6 (dependency install, migrations, verification) is performed manually by the developer. `composer.json` defines no scripts beyond a single `require` entry (PHPMailer) — there is no automated build/package step to trigger in the first place, since there is no compiled/bundled output for either the PHP backend or the CDN-loaded frontend assets.

## A.15 Licensing and Open Source Libraries

No `LICENSE` file exists in this repository at present, so the project's own licensing terms are undeclared.

**Backend dependency** (via Composer, `composer.json`/`composer.lock`):
- **PHPMailer** `^7.1` — LGPL-2.1 — the sole PHP package dependency, used for outgoing SMTP mail.

**Frontend libraries** (loaded via CDN `<script>`/`<link>` tags, not installed locally — no bundler, no local copy checked in):
- **Tailwind CSS v4** (browser build, `cdn.jsdelivr.net`) — MIT.
- **Font Awesome 6.4.0** (`cdnjs.cloudflare.com`) — Font Awesome Free license.
- **Chart.js** (`cdn.jsdelivr.net`) — MIT — used on Dashboard, Analytics, and Reports.
- **Leaflet 1.9.4** (`unpkg.com`, with Subresource Integrity hashes) — BSD-2-Clause — used only on the Barangay/GIS Mapping page.
- **Google reCAPTCHA v2** (`google.com/recaptcha`) — Google's own terms of service.

**Geospatial data sources and their licenses**:
- Caloocan barangay boundary polygons (188 rows) — sourced from `faeldon/philippines-json-maps` (MIT-licensed), itself based on PSA/PSGC December 2023 boundaries.
- Building footprints (74,967 rows) and named subdivisions/neighbourhoods (348 rows) — © OpenStreetMap contributors, fetched via the public Overpass API, licensed ODbL.
- Housing project seed records (5 rows) — real NHA project data compiled from public web sources, cited per-record rather than a bulk dataset license.

**Sibling mobile repository** (`Civentral-CitizenMobile`, out of this repo's own dependency tree but part of the same system): Expo/React Native, TypeScript, `expo-router`, `expo-document-picker`, `@expo/vector-icons` — each under its own respective open-source license, not audited as part of this document.

## A.16 Performance Metrics and Monitoring

No formal performance-monitoring, APM, or load-testing tooling is in place, and no load/benchmark testing has been conducted on this system. Performance is instead addressed through specific design decisions visible in the code:

- **Pagination everywhere**: every local-DB list endpoint exposes a standard `paginate($filters, $page, $perPage)` on its repository, bounding query and payload size by default. Reports' CSV export is a deliberate, documented exception — it bypasses pagination to stream every matching row via `fputcsv`, trading response size for export completeness.
- **Bounded/zoom-gated GIS queries**: `BuildingRepository`/`BuildingService` cap any bounding-box query at 2000 rows and clamp the box to Caloocan's extent, since the underlying table holds 74,967 rows. The map UI (`assets/js/mapping/`) only loads the Subdivisions/Housing Projects layers above zoom level 13, and only fetches Buildings (via debounced bbox queries on `moveend`/`zoomend`) at zoom level 15 or higher, keeping the Leaflet map responsive against a large dataset without a server-side spatial index (MySQL spatial types are deliberately not used — plain indexed DECIMAL bbox columns are pre-filtered before Leaflet renders the exact polygon client-side).
- **No caching layer**: no Redis/Memcached or query-result cache exists anywhere in the stack. The `ASSET_VERSION`/`filemtime()` query-string versioning on static JS assets exists purely for cache-*correctness* (forcing a refresh after an edit), not as a performance optimization.
- **AI Assistant payload bounding**: `api/employee/ai-assistant.php` caps conversation history sent to Gemini at the last 20 turns to keep prompt size/latency reasonable.
