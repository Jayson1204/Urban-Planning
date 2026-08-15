# Web vs. Mobile Architecture Plan

Read this before scoping any new module or starting mobile app work. It defines how the Web application (this repo) and a future Mobile application relate, so that responsibilities never overlap or get duplicated.

**Note: there are two separate future mobile apps planned**, not one. This document covers the field-surveyor app below. A second, citizen-facing app (for self-service document uploads) was decided 2026-08-14 — see `docs/citizen-document-upload-mobile-api.md` for that one's integration reference. Don't conflate the two: different audience, different auth (staff session-cookie reuse here vs. the citizen `api/citizen/*` login/OTP flow there), different purpose.

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

## Current status (as of 2026-08-13)

The existing backend architecture already satisfies this model with no restructuring:

- Every Web module built so far (Resident/Household/Housing/Beneficiaries, Urban Planning, Field Survey, Analytics, AI Assistant, User/Role mgmt) already maps 1:1 to the Web-only list above.
- `api/employee/field-survey-assignments.php`, `field-survey-results.php`, `field-survey-photos.php`, `residents.php`, `households.php` are already generic REST endpoints with no web-page coupling — a future Mobile app consumes these directly, unchanged, for the field data flow above.
- Authorization is already 100% backend-enforced via `PermissionMiddleware`.

### Decisions locked in

1. **Mobile auth**: reuse the existing PHP session-cookie mechanism (no JWT/token exists in the codebase or is planned). A mobile HTTP client (e.g. Expo/React Native) maintains a cookie jar and authenticates through the same login/OTP flow as the Web app. Zero new backend auth code.
2. The sibling folder `C:\xampp\htdocs\Urban_Planning` (empty `mobile/`/`web/`/`api/`/`database/`/`docs/` scaffolding, second git remote `urban-planning` → `github.com/Jayson1204/Urban-Planning.git`) is **unrelated to this project** — ignore it.
3. **Known gap, intentionally not yet fixed**: `field_survey_assignments.assigned_to` (`database/phase11_field_survey_assignments.sql`) is free-text VARCHAR, not linked to any real employee account, and the assignments API has no "assignments for the logged-in user" filter. This blocks a real "View Assigned Survey" screen on mobile. Fix only when mobile implementation actually starts: add a nullable `assigned_to_user_id` column alongside the existing free-text field, plus a server-side `mine=1` filter param on `api/employee/field-survey-assignments.php`.

### What's not built yet

No mobile app repository or code exists anywhere. When mobile work starts, it is a new client project that consumes this repo's existing `api/employee/*.php` endpoints — it does not require changes to this repo beyond the deferred gap above (and any future mobile-specific read endpoints that turn out to be genuinely needed, evaluated at that time rather than speculatively).
