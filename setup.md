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

Ask a teammate (Scrum Master) for real reCAPTCHA/SMTP/Gemini credentials and remote API access — this project has no local auth backend of its own; login and RBAC proxy to the shared production system.

## 6. Apply database migrations

There is no migration tool. SQL files live in `database/phaseN_*.sql` and are applied manually, in phase order, via PHP CLI:

```bash
/c/xampp/php/php.exe -r "require 'config/database.php'; \$db->getPdo()->exec(file_get_contents('database/phase3_resident_management.sql'));"
```

Repeat for each `database/phaseN_*.sql` file in ascending phase order (check `database/` for the current full list — new phases are added as modules are built).

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

## Notes

- No JS/CSS build step: Tailwind v4 loads via CDN (`type="text/tailwindcss"` inline block in `header.php`), and JS bridges under `assets/js/<module>/` load as plain `<script>` tags cache-busted by `ASSET_VERSION` in `assets/js/app.js`.
- There is no automated test framework; conventions for ad hoc verification scripts are in `CLAUDE.md`.
- See `CLAUDE.md` for architecture (remote-proxy vs local-DB API endpoints, RBAC, module scaffolding recipe) and `docs/module-guide.md` for what each module does.
