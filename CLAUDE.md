# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

CIVENTRAL Urban Planning is a PHP (no framework) web app served by XAMPP (Apache + MySQL) from `C:\xampp\htdocs\Civentral-UrbanPlanning`, reachable at `http://localhost/Civentral-UrbanPlanning/`. Frontend is server-rendered PHP + Tailwind v4 (browser CDN) + vanilla JS. It is a capstone that adds new modules on top of an existing production system; the auth/dashboard and the shared RBAC backend are owned by the Scrum Master.

## Commands

- **Run the app**: via XAMPP Apache/MySQL (no dev server). Ensure Apache + MySQL are started; browse to `http://localhost/Civentral-UrbanPlanning/`.
- **PHP CLI** (Git Bash): `/c/xampp/php/php.exe` (Windows path: `C:\xampp\php\php.exe`).
- **Lint a PHP file**: `php -l path/to/file.php`.
- **Apply a DB migration** (no migration tool — SQL files are run manually):
  `/c/xampp/php/php.exe -r "require 'config/database.php'; \$db->getPdo()->exec(file_get_contents('database/phaseN_x.sql'));"`
- **Install PHP deps**: `composer install` (only dependency is PHPMailer).
- **Tests**: there is no test framework. The convention is standalone PHP CLI scripts that `require config/database.php` plus the repository/service classes and assert behavior; run them with the php.exe above. Write these to the scratch/temp dir, not the repo.

## Architecture

### Two API backend patterns (most important distinction)
Every file in `api/employee/*` and `api/citizen/*` is one of two kinds:

1. **Remote-proxy endpoints** — most existing endpoints (`login.php`, `users.php`, `roles.php`, `permissions.php`, `resources.php`, `modules.php`, ...). They `require config/proxy.php` and forward the request via `proxyRequest()` to `https://civentral.tech/api/...` (override with `EXPO_PUBLIC_API_BASE_URL`), relaying a remote session cookie kept in `$_SESSION['remote_phpsessid']`. This is the **shared production system**; the local app has no tables for it. Some endpoints add local guard logic before proxying (e.g. `users.php` blocks non-superadmins from creating admin accounts). Because the remote list can lag (a freshly created record may not appear in the next GET), write flows must optimistically reflect the change client-side.

2. **Local-DB endpoints** — this project's own modules (`residents.php`, `households.php`, `resident-documents.php`, `housing-units.php`, `housing-beneficiaries.php`). They `require src/bootstrap.php` and use repositories/services against local MySQL. REST shape: `GET` list (paginated) / `?action=stats` / `?id=` detail; `POST` create; `PUT` update; `DELETE` = soft-archive. All reply via `respond([...])` JSON with a `status` field.

### bootstrap.php is the DI wiring
`src/bootstrap.php` starts the session + output buffering, loads `config/database.php` (`$db`), requires/instantiates every repository + service + middleware, runs `SessionTimeout`, and builds `$headerUser` through `HeaderService`. Local-DB API endpoints and PHP pages include it (pages set `$basePath` first). Register any new repo/service here.

### Auth and permissions
- Session-based: `AuthService->isLoggedIn()` checks `$_SESSION['user_id']`; `SessionTimeout` enforces a 1800s idle timeout in bootstrap.
- `$headerUser` carries `is_superadmin` / `is_global_access`, `granted_resources`, `granted_actions`, `role_id`. The production RBAC drives sidebar visibility and action buttons for existing modules.
- Local API endpoints gate with `PermissionMiddleware::requireResource('<resource name>')`. Note it is deliberately permissive (falls back to allowing any logged-in user) with a superadmin/global-access bypass.
- **All modules, including this project's own (Resident Management, Housing Management, Urban Planning), are gated through production RBAC** — there is no separate local permission table for this. Create the module (and its default resource) in **Module Management**, then grant it to a role in **Permission Builder** (master access, or individual VIEW/CREATE/EDIT/... actions). Sidebar sections check this the same way as every other resource, via `$hasResourceAccess([...keywords])` in `includes/sidebar.php` matching against `$headerUser['granted_resources']` — the keywords must overlap with the resource's name in production (e.g. a resource named "Resident Management" is matched by keywords `['resident management', 'resident directory', 'resident']`).

### Page composition
Pages under `pages/<area>/*.php` set `$basePath = '../../'`, then `include header.php; include sidebar.php;` at the top and `include footer.php` at the bottom. Tailwind v4 is loaded via the browser CDN with an inline `type="text/tailwindcss"` block in `header.php` that defines the `--color-brand-*` palette (no build step). `includes/sidebar.php` renders nav using the RBAC gating above.

### JS "bridge" loader and cache-busting (has caused real bugs)
- `assets/js/app.js` defines `window.loadCiventralScript(src, cb)` — an order-preserving (`async=false`) dynamic `<script>` injector that appends `?v=ASSET_VERSION` for cache-busting, and eagerly loads global bridges.
- Each feature is a **bridge**: `assets/js/<module>/app.js` loads `api.js`, `table.js`, `modal.js`, `filters.js`, `events.js` in order, then initializes only if the page's anchor element exists. Some bridges are registered globally in `app.js` and self-gate by checking for their DOM anchor.
- `app.js` is loaded from `header.php` with `?v=<filemtime>` so it always refreshes, and it stamps `ASSET_VERSION` onto every dynamically-loaded script. **After editing any bridge-loaded JS, bump `ASSET_VERSION` in `app.js`**, or browsers serve stale code.
- `window.civentralBasePath` (set in header.php) is the base path for all dynamic script loads.

### Adding a new module (mirror an existing one, e.g. housing)
`database/phaseN_*.sql` → `src/Repositories/XRepository.php` (find/paginate/stats/create/update/setStatus) → `src/Services/XService.php` (validate + whitelist-sanitize) → register both in `bootstrap.php` → `api/employee/x.php` (auth + `requireResource` + REST + `respond`) → `pages/<area>/x.php` view → `assets/js/<module>/` bridge registered in `app.js` → sidebar entry (gated with `$hasResourceAccess([...keywords])`) + `assets/js/dashboard.js` dropdown arrays → create the matching module/resource in **Module Management** (production) → grant it to roles in **Permission Builder**.

### Database
Local schema lives in `database/phaseN_*.sql`, applied manually. PDO runs with `EMULATE_PREPARES => false`: a named placeholder reused within one query throws `HY093`. Use a single placeholder (e.g. `CONCAT_WS(' ', a, b, c) LIKE :search`) rather than repeating `:search`.

### Environment
`.env` is loaded by `config/database.php`. Keys: `DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASSWORD` (local MySQL), `EXPO_PUBLIC_API_BASE_URL` (override remote proxy target), `RECAPTCHA_SITE_KEY/RECAPTCHA_SECRET_KEY`, `APP_ENV/APP_DEBUG`. See `.env.example`.

## Working style

- Run major changes by the user first; keep a to-do list. Build new modules one at a time and pause for approval.
- Log significant work in `/docs/activity-log.md`; do not auto-commit docs or activity logs; markdown filenames use kebab-case.
- Comments are single-line, single sentence, no emojis or special characters.
- Prefer no new external libraries; match the existing patterns/style rather than introducing new ones.
- Do not auto-commit or auto-push; keep commits focused and atomic.
- The proxied backend holds real citizen data — do not surface customer personal data or credentials in code, logs, or docs.
