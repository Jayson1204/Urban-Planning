# Citizen Document Upload — Mobile Integration Reference (SUPERSEDED — see note)

**Superseded 2026-08-19.** This doc's "the gap" section (no citizen-to-resident link, no citizen-authenticated endpoint) is now closed differently than proposed here: citizen accounts are local (`citizen_accounts`, linked 1:1 to `residents`), not production-proxied, and the real endpoint is `api/citizen-app/beneficiary-documents.php` (session-based, resolves `resident_id` directly from the logged-in account) — not the `api/citizen/beneficiary-documents.php` this doc sketches. The rest of this doc (the Web-side schema/repo/API/admin-UI description below) is still accurate. See `Civentral-CitizenMobile`'s `CLAUDE.md` for the current citizen-app design.

---

Read this before starting the citizen-facing mobile app's document upload feature. It documents what already exists on the Web/backend side (built 2026-08-14) and — just as important — what does **not** exist yet and must be decided/built before a mobile client can call this for real.

This is a **second, separate mobile app** from the field-surveyor one in `docs/mobile-app-plan.md`. Different audience (citizens/applicants, not staff), different purpose (self-service document submission, not field data collection). Do not conflate the two when scoping mobile work.

## What this feature is

A housing beneficiary applicant uploads supporting documents (Valid ID, Proof of Income, Barangay Certificate, Certificate of Indigency, Proof of Residency, Other). Staff review each document and mark it **Verified** or **Rejected** (with a reason). The applicant should be able to see the status and resubmit if rejected.

The admin review UI is already live: **Housing Management → Beneficiaries → click the ⓘ icon on a beneficiary → "Application Documents" section.**

## What's already built (Web side only)

- **Table**: `housing_beneficiary_documents` (`database/housing_beneficiary_documents.sql`)
  - `beneficiary_id` — FK to `housing_beneficiaries` (cascade delete)
  - `document_type` — ENUM: `Valid ID`, `Proof of Income`, `Barangay Certificate`, `Certificate of Indigency`, `Proof of Residency`, `Other`
  - `file_name`, `file_path`, `file_size`
  - `submitted_by` — ENUM `Citizen` / `Staff` (set by whoever calls the upload endpoint)
  - `review_status` — ENUM `Pending` (default) / `Verified` / `Rejected`
  - `review_notes`, `reviewed_by_name`, `reviewed_at`
  - `status` — ENUM `Active`/`Archived` (soft-delete convention, currently unused by the API — deletes are hard deletes)
  - `uploaded_at`
- **Repository**: `src/Repositories/BeneficiaryDocumentRepository.php` (`$beneficiaryDocumentRepo` in `bootstrap.php`)
- **API** (staff-only today — see "The gap" below): `api/employee/beneficiary-documents.php`
- **Admin UI**: `pages/housing/beneficiaries.php` (View modal) + `assets/js/housing-beneficiaries/documents.js`

## The gap: this is not callable by a citizen mobile client yet

`api/employee/beneficiary-documents.php` requires:
1. A logged-in **staff** session (`$authService->isLoggedIn()` checks the local employee session).
2. `PermissionMiddleware::requireResource('housing management', ...)` — a staff RBAC resource.

A citizen mobile app has neither. Citizens authenticate against **production** (`api/citizen/login.php` + `verify-otp.php`, proxied to `civentral.tech/api/citizen/*`), which is a completely different identity system from the local staff session used by every `api/employee/*.php` endpoint.

There is also **no link in the schema** between a production citizen account and a local `residents`/`housing_beneficiaries` record. `residents.email` is the only plausible matching key today, but nothing enforces or uses that match anywhere in the codebase.

**Before mobile work starts, decide and build:**
1. A citizen-facing endpoint (e.g. `api/citizen/beneficiary-documents.php`) that:
   - Verifies the relayed citizen session is valid (call `api/citizen/get-profile.php` through the existing proxy pattern in `config/proxy.php`, using `$_SESSION['remote_phpsessid']`).
   - Resolves which `beneficiary_id`/`resident_id` the logged-in citizen is allowed to upload against — almost certainly by matching the citizen profile's email against `residents.email`, then finding that resident's beneficiary application(s). This matching logic doesn't exist anywhere yet and needs to be written.
   - Rejects the upload if no matching resident/beneficiary application is found, with a clear error the mobile app can surface ("no application found for your account").
2. Reuse `BeneficiaryDocumentRepository` and the same upload validation (5MB limit, PDF/JPG/PNG only) from `api/employee/beneficiary-documents.php` — don't duplicate that logic, extract or share it.
3. Set `submitted_by = 'Citizen'` on documents created through this new endpoint (the column and admin UI already render this distinction — "Submitted by applicant" vs "Uploaded by staff").

Until that endpoint exists, staff upload on the applicant's behalf via the Web UI (walk-in / manual entry), so the review workflow is usable today.

## Endpoint contract (current, staff-side — mirror this shape for the citizen version)

Base: `api/employee/beneficiary-documents.php`

### `GET ?beneficiary_id={id}`
List documents for a beneficiary application.
```json
{
  "status": "success",
  "data": [
    {
      "document_id": 1,
      "beneficiary_id": 5,
      "document_type": "Valid ID",
      "file_name": "id.png",
      "file_path": "uploads/beneficiary-documents/<random>.png",
      "file_size": 68,
      "submitted_by": "Staff",
      "review_status": "Rejected",
      "review_notes": "Photo is blurry...",
      "reviewed_by_name": "Jayson Evangelista",
      "reviewed_at": "2026-08-14 21:58:47",
      "status": "Active",
      "uploaded_at": "2026-08-14 21:58:12"
    }
  ]
}
```
(The single-beneficiary `GET api/employee/housing-beneficiaries.php?id=` also embeds this same list under `data.documents`.)

### `POST` (multipart/form-data)
Fields: `beneficiary_id`, `document_type`, `file` (PDF/JPG/PNG, max 5MB), optionally `submitted_by` (`Citizen`/`Staff`, defaults to `Staff`).
Response: `{"status":"success","message":"...","document_id":N}` (HTTP 201).

### `PATCH` (JSON) — staff review only, not for the citizen endpoint
Body: `{"document_id": N, "review_status": "Verified"|"Rejected", "review_notes": "..."}` (`review_notes` required when rejecting).

### `DELETE ?id={document_id}`
Removes the document row and the stored file.

## Client-side status meanings (for the mobile UI)

| `review_status` | Meaning | Applicant-facing copy suggestion |
|---|---|---|
| `Pending` | Uploaded, not yet reviewed | "Under review" |
| `Verified` | Staff approved it | "Approved" |
| `Rejected` | Staff rejected it — see `review_notes` | "Rejected — {review_notes}. Please resubmit." |

## Related reading

- `docs/activity-log.md`, entry "2026-08-14 — New: Beneficiary Application Documents" — full build history and reasoning.
- `docs/mobile-app-plan.md` — the *other* (field-surveyor) mobile app's architecture decisions; the session-cookie auth pattern described there (decision #1) is the same mechanism `config/proxy.php` uses for citizens, just against `api/citizen/*` instead of staff login.
