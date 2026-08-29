# Mobile App Plan

**Updated 2026-08-19 — scope pivot.** Per explicit instruction: the Web application (this repo) is finished. All active development now targets the **citizen/applicant-facing mobile app only** — the field-surveyor mobile app described in the second half of this document is parked, not in progress. Do not rebuild, redesign, or modify finished Web modules; touch this repo only to add the minimal new citizen-facing API surface the mobile app needs.

The mobile app's own code lives in a sibling repo, **`C:\xampp\htdocs\Civentral-CitizenMobile`** (Expo/React Native, not git-initialized yet — user manages git themselves). That repo's `CLAUDE.md` is the live architecture reference for the client; this document is the backend-side plan — what already exists to reuse, what's genuinely missing, and the build order to close the gap against the fuller feature list now requested.

---

## 1. Analysis of the existing system (as of 2026-08-19)

### 1. Existing backend/API
PHP, no framework, served by XAMPP Apache. Two API families:
- `api/employee/*.php` — staff-only, session-gated (`$authService->isLoggedIn()` + `PermissionMiddleware::requireResource(...)`), `require src/bootstrap.php`. This is where **all** housing-project, subdivision, building, barangay/GIS, zoning, and permit endpoints currently live — every one of them is staff-only today, none are reachable from a citizen session.
- `api/citizen-app/*.php` — the citizen mobile app's own family, `require config/citizen_app_bootstrap.php` (a lighter bootstrap, not `src/bootstrap.php` — no `SessionTimeout`/`AuthMiddleware`, which assume a staff login). Six endpoints exist today: `register.php`, `login.php`, `logout.php`, `profile.php` (GET only), `beneficiary-documents.php` (GET/POST), `set-password.php`.
- A third, unrelated family, `api/citizen/*.php`, proxies to production `civentral.tech` for the **Citizen Account Directory** staff module — not used by the mobile app, do not confuse the two.

### 2. Existing authentication
Local, not production-proxied. `citizen_accounts` table (`database/citizen_accounts.sql`), email+password, `password_verify`/`password_hash`, session key `$_SESSION['citizen_account_id']` (separate session namespace from staff `$_SESSION['user_id']`). No JWT/token anywhere in the codebase. No OTP — deliberate v1 decision per `Civentral-CitizenMobile/CLAUDE.md`, even though SMTP already exists (`config/mailer.php`) and is used for the one email flow that does exist (staff-invite set-password link, `CitizenAccountService::sendSetPasswordEmail()`).
- **Forgot password is not implemented.** The only password-reset-shaped flow is `completeSetPassword()`/`set-password.php`, which is for a **staff-created** resident's first-time password set (token minted at account creation), not a self-service "I forgot my password" flow for an already-active account. This needs to be built (see plan below).
- CORS (`citizen_app_bootstrap.php::citizenAppCors()`) currently allow-lists only `localhost`/`127.0.0.1` origins — fine for the Expo web target, but will need the LAN/deployed origin added when testing from a device or emulator.

### 3. Existing MySQL database
One shared schema, same DB the Web app uses — `config/database.php`, `.env` (`DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASSWORD`). No separate mobile database, no direct DB access from the mobile app (it only ever calls `api/citizen-app/*.php`, which is the correct architecture — do not change this).

### 4. Existing applicant/user tables
`citizen_accounts` (login identity: email, password_hash, status) is linked **1:1** to `residents` (`resident_id` FK, `uniq_citizen_resident`). There is no separate "applicant" entity — a `residents` row **is** the applicant identity, and `households` is the optional household link. Registration (`CitizenAccountService::register()`) creates both the account and the resident (+ household, if `New`) together, immediately, no staff review — or links to an existing staff-entered resident by matching email. This is already fully reusable; no schema change needed for basic profile identity.

### 5. Existing housing APIs
`housing_projects` (`database/housing_projects_seed.sql` schema + seed) — name, barangay, lat/lng, `units`, `project_status` (`Existing`/`Ongoing`/`Proposed`/`Completed`), developer, description, source citation. Served today only by `api/employee/housing-projects.php` (list/detail/`?action=map`), staff-only. `housing_units` (`database/housing_management.sql`) has `occupancy_status` (`Vacant`/`Occupied`/`Reserved`/`Under Maintenance`) but **no dedicated staff or citizen API file exists for it standalone** — it's read through `HousingUnitRepository` inside the Beneficiary flow. **There is no "Housing Programs" table or concept distinct from housing projects/beneficiary categories** — see the gap note in section 9 (Housing Programs) of the plan below.

### 6. Existing housing application APIs
`housing_beneficiaries` (`database/housing_beneficiaries.sql`) — `beneficiary_status` ENUM(`Applicant`,`Qualified`,`Awarded`,`Disqualified`,`Cancelled`), `category` ENUM(6 values), `monthly_income`, `family_size`, `remarks`. Full CRUD + eligibility scoring + duplicate-application detection lives in `BeneficiaryService`/`HousingBeneficiaryRepository`, exposed to staff via `api/employee/housing-beneficiaries.php`. **The citizen side can only read/upload documents against an application that already exists** — `HousingBeneficiaryRepository::forResident()` is called internally by `api/citizen-app/beneficiary-documents.php` to resolve which `beneficiary_id`s belong to the logged-in citizen, but there is no citizen-facing endpoint to (a) list those applications directly, or (b) create a new application at all. Today a citizen cannot "start an application" from the mobile app — a staff member has to create the `housing_beneficiaries` row first. This is the single biggest functional gap against the requested feature list (item 5).

### 7. Existing document APIs
`housing_beneficiary_documents` (`database/housing_beneficiary_documents.sql`) — `document_type` ENUM(`Valid ID`,`Proof of Income`,`Barangay Certificate`,`Certificate of Indigency`,`Proof of Residency`,`Other`), `review_status` ENUM(`Pending`,`Verified`,`Rejected`), `review_notes`, `reviewed_by_name`, `reviewed_at`, `submitted_by` ENUM(`Citizen`,`Staff`). Citizen upload is already live: `api/citizen-app/beneficiary-documents.php` (GET list, POST upload via `FileUploadService::handleUpload()` — 5MB, pdf/jpg/png, shared validation with the staff endpoint, no duplicated logic). Review (PATCH) stays staff-only, correctly not exposed to the citizen endpoint.

### 8. Existing notification functionality
`NotificationService` exists but is **entirely staff-shaped** — `notifications.recipient_user_id` is a hard FK to `users`, and `resolveRecipients()` only ever resolves superadmins or staff by role/table. There is no citizen notification path in the schema or code at all. Building a parallel citizen notification pipe is possible but heavier than needed for v1; recommend deriving an in-app "status feed" from existing timestamped fields (`beneficiary_status`/`updated_at`, `review_status`/`reviewed_at`) instead of forking `NotificationService` — see plan below.

### 9. Existing GIS/map data
Built 2026-08-15/19, staff-only today (`api/employee/barangays.php`, `subdivisions.php`, `buildings.php`, `housing-projects.php?action=map`), all gated by `requireResource('urban planning')`:
- `barangays` (phase16) — 188 Caloocan barangays, PSGC-sourced boundary GeoJSON, served from `assets/geojson/caloocan-barangays.geojson` (74KB, small enough to reuse as-is for mobile).
- `subdivisions`, `housing_projects`, `buildings` (phase18) — each carries `barangay_id` FK, lat/lng or footprint GeoJSON; `buildings` additionally has bbox DECIMAL columns for a plain-SQL viewport pre-filter (no MySQL spatial types anywhere in this codebase — deliberate, matches the rest of the schema).
- None of this is reachable from `api/citizen-app/*` today. A read-only citizen wrapper is new work, not a rewrite — the underlying repositories (`BarangayRepository`, `SubdivisionRepository`, `HousingProjectRepository`, `BuildingRepository`) are already read-method-complete.

### 10. Existing zoning data
`zoning_use_regulations` (`database/zoning_clearances.sql`) is an **abstract classification × use-category regulation matrix** — `zone_classification` (e.g. "Residential"), `use_category`, `conformity` (`Permitted`/`Conditional`/`Prohibited`), `max_height_m`, `min_setback_m`, `max_far`, `max_lot_occupancy_pct`, `reference_note`. **It is not a spatial layer** — there is no polygon/zoning-district geometry, and no per-barangay or per-parcel zone assignment anywhere in the schema (confirmed in `docs/thesis-scope-gap-analysis.md` section G: "Shared parcel-centric spatial data model" is explicitly not built — zone classification is self-declared by the applicant/staff on each Zoning Clearance application, not looked up spatially). This means the requested "select a zone on the map → see its barangay" flow is **not buildable as literally specified** without first building a real zoning-district polygon layer (out of scope for the mobile app alone). The mobile "Zoning Information" feature should be scoped down to a **browsable reference table** of the regulation matrix (classification → permitted/conditional/prohibited uses, height/setback/FAR limits) — accurate to what the system actually knows, with barangay browsing done separately via the GIS barangay layer (item 9), not fused with zoning. Flagged as an open decision below.

### 11. Existing Gemini API/AI implementation
`src/Services/GeminiService.php` — configured via `GEMINI_API_KEY` (`.env`, server-side only, never sent to any client), model `gemini-flash-latest`, generic `call()`/`chat()`/`generateContent()` methods that are fully reusable. The **system instruction and grounding context are 100% staff-planning-shaped today** (`buildSystemInstruction()`/`buildContextSummary()` pull resident/housing/project/survey aggregate counts via `AnalyticsRepository` and address "planning staff"). There is no citizen persona, no housing-program/eligibility/document-checklist grounding, and no endpoint exposing it to `api/citizen-app/*`. Reuse the HTTP-call plumbing; write a new citizen-safe system instruction (see plan below) rather than reusing the staff one as-is.

---

## 2. Feature-by-feature gap summary

| # | Requested mobile feature | Backend status | Work needed |
|---|---|---|---|
| 1 | Authentication (login, register, forgot password, session mgmt) | Login/register/logout/session mostly done | **New**: forgot/reset-password flow; CORS origin for real-device testing |
| 2 | Applicant profile (view/edit, household, contact) | View done (`profile.php` GET) | **New**: edit (PUT/PATCH), reusing `ResidentService`/`HouseholdService` update methods |
| 3 | Housing Programs (list, details, requirements, eligibility) | No "program" entity exists | **Decision + new**: model as static/derived content from `BeneficiaryService::CATEGORIES` + the document-type checklist, not a new DB table (see below) |
| 4 | Housing Projects (list, details, location, availability) | Data + repo exist, staff-only API | **New**: read-only citizen wrapper endpoint |
| 5 | Housing Application (start, fill form, upload docs, submit, history) | Document upload only; no create/list-mine endpoint | **New**: citizen-facing create + "my applications" endpoints, reusing `BeneficiaryService` validation/scoring unchanged |
| 6 | Application Tracking (status + remarks + missing docs) | Status fields exist, vocabulary differs | **New**: status-mapping layer in the "my applications" endpoint (no schema change) |
| 7 | Notifications | No citizen notification path | **New (scoped down)**: derived status feed, not a forked `NotificationService` |
| 8 | Map/GIS (read-only, barangays/zoning/projects/units/subdivisions/buildings) | Data + repos exist, staff-only API | **New**: read-only citizen wrapper endpoint(s), same GeoJSON assets |
| 9 | Zoning Information (read-only) | Regulation matrix exists, not spatial | **New (scoped down)**: reference-table endpoint; "select a zone on the map" not buildable without a new spatial layer (flag to user) |
| 10 | AI Applicant Assistant (Gemini) | `GeminiService` plumbing reusable, persona is wrong | **New**: citizen system instruction + endpoint, hard-coded guardrail against eligibility/approval/legal decisions |

Nothing here requires touching a finished Web page, duplicating business logic, or giving the mobile app direct DB access — every new item is a thin `api/citizen-app/*.php` file that reuses an existing repository/service, matching the pattern `beneficiary-documents.php` already establishes.

---

## 3. Open decisions before building (ask the team, don't guess)

1. **"Housing Programs"** — the doc's Section 1.3.1 scope and this system's schema don't have a matching table. Recommend surfacing it as **derived reference content** (the 6 beneficiary categories + their scoring weight + the required-document checklist, rendered as "programs"), not a new `housing_programs` table, unless the team specifically wants LGU-authored program descriptions (name, narrative, external eligibility rules) that don't map to existing scoring data — that would need a small new table + staff admin UI, which is a larger ask than the rest of this plan.
2. **"Zoning Information"** — per section 10 above, scope down to a browsable regulation-matrix reference rather than a map-pin zone selector, unless the team wants to fund a real zoning-district polygon layer first (a bigger, separate GIS project, not mobile-specific).
3. **Notifications** — confirm a derived status-feed (no new table, no push) is acceptable for v1, versus wanting real push notifications (would need a device-token table + a push service, e.g. Expo push — new infrastructure not present anywhere in this codebase today).

---

## 4. Implementation plan (build one phase at a time, pause for approval — per `CLAUDE.md`)

Each phase is additive: new `api/citizen-app/*.php` files in this repo (backend) + matching screens/`src/api/*.ts` files in `Civentral-CitizenMobile` (client). No existing Web page, staff endpoint, or business-logic class is modified except where explicitly noted as "reuse."

**Phase 0 — already shipped.** Register, login, logout, profile (view), beneficiary document upload/list. (`Civentral-CitizenMobile` — auth flow + document upload screens.)

**Phase 1 — Auth completion.**
- `api/citizen-app/forgot-password.php` (request reset — email a token, reusing `config/mailer.php`) + extend `set-password.php` (or a new `reset-password.php`) to accept a reset token for an *active* account, not just a first-time invite.
- Confirm/extend `citizenAppCors()` allow-list for real-device/LAN testing.

**Phase 2 — Applicant profile edit.**
- `PUT`/`PATCH` on `api/citizen-app/profile.php`, reusing `ResidentService`'s existing update-validation, scoped to the logged-in citizen's own `resident_id` only (never trust a client-supplied ID).

**Phase 3 — Housing Programs & Housing Projects (read-only).**
- Resolve decision #1 above first.
- `api/citizen-app/housing-projects.php` — thin read-only wrapper around `HousingProjectRepository` (list/detail/map), no write verbs.

**Phase 4 — Housing Application (the biggest gap).**
- `api/citizen-app/housing-application.php`:
  - `POST` — create a `housing_beneficiaries` row via `BeneficiaryService::createBeneficiary()`, with `resident_id` resolved server-side from `citizenAccountService->currentCitizen()`, never client-supplied. Existing duplicate-detection ("one open application per resident/household") applies unchanged.
  - `GET` — list the logged-in citizen's own applications (wrap `HousingBeneficiaryRepository::forResident()`, already used internally by the documents endpoint, now exposed directly).
- Wire the existing `beneficiary-documents.php` upload flow to a freshly created application.

**Phase 5 — Application Tracking UI mapping.**
- In the Phase 4 `GET`, map `beneficiary_status` + each document's `review_status` into the requested display vocabulary (e.g. `Applicant` → "Submitted"/"Under Review", `Qualified` → "Document Verification passed", `Awarded` → "Approved", `Disqualified` → "Rejected", `Cancelled` → "Other"), and surface `remarks` (application-level) and `review_notes` (per-document) as-is. Confirm the exact status-label mapping with the team before building the client screen, since it's a product decision, not a technical one.

**Phase 6 — Notifications (scoped-down v1).**
- No new table. The Phase 4 `GET` (or a small dedicated `notifications.php`) returns a derived list from status-change timestamps already present (`housing_beneficiaries.updated_at`, `housing_beneficiary_documents.reviewed_at`) — "what changed since you last checked," polled by the client. Revisit real notifications only if the team wants push.

**Phase 7 — Map/GIS (read-only).**
- `api/citizen-app/map.php` (or one file per layer, matching the staff-side convention) wrapping `BarangayRepository`, `SubdivisionRepository`, `HousingProjectRepository`, `BuildingRepository` read methods — same GeoJSON shapes the staff map already renders, just without `PermissionMiddleware`/staff session gating. No create/edit/delete verbs exposed, ever.

**Phase 8 — Zoning Information (read-only, scoped per decision #2).**
- `api/citizen-app/zoning-info.php` — read-only wrapper around `ZoningUseRegulationRepository`, rendered as a classification browser, not a map selector.

**Phase 9 — AI Applicant Assistant.**
- New system instruction in `GeminiService` (or a citizen-specific subclass/method) grounded in housing categories, document checklist, and the zoning regulation matrix — not internal analytics. New `api/citizen-app/ai-assistant.php` endpoint. Hard guardrail in the system prompt: never state a final eligibility/approval/zoning/legal decision, always defer to "an LGU staff member will confirm."

After each phase: verify with a scratch PHP CLI test against the real local DB (existing convention), confirm the citizen endpoint rejects cross-account access (one citizen can never read/write another citizen's `resident_id`/`beneficiary_id`), and log the work in `docs/activity-log.md`.

---

## 5. Mobile navigation (target)

```
Home
├── Housing Programs      (Phase 3)
├── Housing Projects      (Phase 3)
├── My Application         (Phase 4/5)
├── Map                    (Phase 7, read-only)
├── Notifications          (Phase 6)
├── AI Assistant           (Phase 9)
└── Profile                (Phase 0 view / Phase 2 edit)
```

---

## 6. Integration rules (unchanged from the original architecture decision)

```
Mobile Application (Civentral-CitizenMobile)
        |
        v
PHP Backend / REST API  (this repo: api/citizen-app/*.php)
        |
        v
   MySQL Database
```

- The mobile app never talks to MySQL directly — only through `api/citizen-app/*.php`.
- Reuse existing services/repositories; do not fork business logic (validation, scoring, duplicate-detection) that already exists on the staff side.
- All authorization stays server-side, scoped to the logged-in citizen's own `resident_id` — resolved from `$_SESSION['citizen_account_id']`, never accepted from client input.
- Do not build a staff dashboard, analytics, review/approval UI, or anything else that belongs to office staff into the mobile app — that stays on Web, per `Civentral-CitizenMobile/CLAUDE.md`'s own scope note (which will need updating alongside this plan once the team confirms the phases above).

---

---

# Parked — Field Surveyor Mobile App (on hold)

Everything below this line was the plan for a **second, different** mobile app — internal field staff collecting resident/household/housing survey data — before the 2026-08-19 scope pivot to citizen-only mobile development. It is kept for reference only; do not resume it without explicit instruction. Do not conflate it with the citizen app above: different audience, different auth, different repo (none exists yet for this one).

## System relationship

CIVENTRAL Urban Planning & Housing Management is **one integrated system** with two clients. Both clients talk to the same PHP backend/API, which talks to the same MySQL database. Neither client accesses MySQL directly.

```
Mobile Application
        |
        v
PHP Backend / REST API  (this repo: api/employee/*.php, api/citizen/*.php)
        |
        v
   MySQL Database
        ^
        |
PHP Web Application  (this repo: pages/*.php)
```

The Web application does not expose the database to the Mobile application either — both go through the same PHP API layer.

## Web application — full LGU management platform

Audience: office-based LGU personnel (administrators, planning officers, housing officers, department heads, data encoders, report analysts).

Owns:
- Dashboard and analytics
- Resident management
- Household management
- Housing management + housing beneficiaries
- Urban planning records and development projects
- Field survey management: creating survey forms, assigning them, **reviewing and validating submissions**
- User and role management (production RBAC)
- Reports and exports
- Documents and attachments
- Notifications
- Audit logs
- AI Planning Assistant (Gemini API)

This is where full CRUD, review/approval, analysis, and reporting live, because office personnel need to create, review, update, analyze, approve, and report on the system's data.

## Mobile application — field data collection only

Audience: field surveyors and other authorized field personnel.

Scope:
- Secure login
- Viewing assigned surveys/tasks (only the logged-in surveyor's own)
- Collecting resident information
- Collecting household information
- Conducting housing surveys
- Recording field observations
- Updating assigned records
- Capturing photos
- Uploading documents/photos
- Submitting completed surveys
- Viewing submission status
- Synchronizing collected data with the central system

**The mobile app must not duplicate the Web app's full functionality.** No dashboard, no analytics, no user/role management, no reports, no review/approval — those stay on Web. Field users only see what's relevant to their assigned field work.

## Field data flow

```
Field Surveyor -> Mobile App -> View Assigned Survey -> Collect Resident/Household/
Housing Info -> Capture Photos/Documents -> Submit Survey -> PHP REST API ->
Validate and Store -> MySQL -> Web Application -> Planning/Housing Officer Reviews Submission
```

## Web management flow

```
LGU Officer -> Web Application -> PHP Backend/API -> MySQL ->
Create / Read / Update / Review / Approve / Report
```

## AI Planning Assistant

Lives in the Web app because it assists planning/housing personnel with analysis and decision support, not field data collection.

- Calls Gemini through the PHP backend only (`src/Services/GeminiService.php`).
- `GEMINI_API_KEY` stays server-side (`.env`), never sent to any frontend, web or mobile.
- May analyze authorized data from MySQL (via `AnalyticsRepository`) and provide summaries, planning insights, housing situation analysis, trends, planning concerns, recommendations, report summaries, and answers to planning questions.
- Must never automatically modify or delete database records — read/analyze only.

This is already fully implemented this way (Phase 10) — no changes needed.

## Development rules

- Do not build a separate system for Mobile. It is a second client of the same backend, database, auth, and business rules.
- Reuse existing authentication, API endpoints, and business logic wherever a Mobile need overlaps a Web one — do not fork or duplicate logic.
- All authorization and validation stays server-side (`PermissionMiddleware::requireResource(...)`) so permissions can't be bypassed by a mobile client.
- Web gets full management functionality; Mobile stays narrowly focused on field operations and data collection.

## Current status (as of 2026-08-13, superseded by the 2026-08-19 pivot above)

The existing backend architecture already satisfies this model with no restructuring:

- Every Web module built so far (Resident/Household/Housing/Beneficiaries, Urban Planning, Field Survey, Analytics, AI Assistant, User/Role mgmt) already maps 1:1 to the Web-only list above.
- `api/employee/field-survey-assignments.php`, `field-survey-results.php`, `field-survey-photos.php`, `residents.php`, `households.php` are already generic REST endpoints with no web-page coupling — a future Mobile app consumes these directly, unchanged, for the field data flow above.
- Authorization is already 100% backend-enforced via `PermissionMiddleware`.

### Decisions locked in

1. **Mobile auth**: reuse the existing PHP session-cookie mechanism (no JWT/token exists in the codebase or is planned). A mobile HTTP client (e.g. Expo/React Native) maintains a cookie jar and authenticates through the same login/OTP flow as the Web app. Zero new backend auth code.
2. The sibling folder `C:\xampp\htdocs\Urban_Planning` (empty `mobile/`/`web/`/`api/`/`database/`/`docs/` scaffolding, second git remote `urban-planning` → `github.com/Jayson1204/Urban-Planning.git`) is **unrelated to this project** — ignore it.
3. **Known gap, intentionally not yet fixed**: `field_survey_assignments.assigned_to` (`database/field_survey_assignments.sql`) is free-text VARCHAR, not linked to any real employee account, and the assignments API has no "assignments for the logged-in user" filter. This blocks a real "View Assigned Survey" screen on mobile. Fix only when mobile implementation actually starts: add a nullable `assigned_to_user_id` column alongside the existing free-text field, plus a server-side `mine=1` filter param on `api/employee/field-survey-assignments.php`.

### What's not built yet

No mobile app repository or code exists anywhere for this field-surveyor app. If resumed, it is a new client project that consumes this repo's existing `api/employee/*.php` endpoints — it does not require changes to this repo beyond the deferred gap above (and any future mobile-specific read endpoints that turn out to be genuinely needed, evaluated at that time rather than speculatively).
