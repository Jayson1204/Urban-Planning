# Citizen Self-Registration — Mobile Integration Plan

Read this before starting citizen self-registration on the mobile app. It's a design sketch only — **nothing described here is built yet**. Same family as `docs/mobile-app-plan.md` (field-surveyor app) and `docs/citizen-document-upload-mobile-api.md` (document uploads) — this is the third citizen-facing capability, on the same citizen mobile app as document uploads, not the field-surveyor app.

## What this feature is

A citizen logs into the mobile app (existing production citizen login/OTP) and submits their own name + address + household info to become a **Resident Directory** / **Household Management** record in this system — without a staff member having to type it in first.

## Why this can't just write straight into `residents`/`households`

Two problems, both serious enough to design around rather than skip:

1. **Trust.** Resident and household records feed housing beneficiary eligibility scoring, zoning clearance applicant identity, and field survey subjects — self-reported, unverified data flowing straight into those has real consequences (fraud risk on housing allocation, bad zoning applicant data). Every other write path into `residents` in this codebase today is staff-entered (Web) or surveyor-collected (planned field mobile app) — never citizen-direct.
2. **Duplicates.** Nothing links a production citizen account to a local `residents` row today (`docs/citizen-document-upload-mobile-api.md` flags this same gap for document uploads). Without a review step, a citizen could self-register while already having a staff-entered record from a barangay visit or a field survey, silently forking their data into two rows.

So this needs a **staging + staff-approval** step, the same pattern already used for beneficiary documents (`Pending`/`Verified`/`Rejected`) and zoning clearance reviews (remarks-required transitions) — not a direct write.

## Proposed design

### 1. New table: `resident_registration_requests`
Captures what the citizen submitted, untouched, until staff acts on it.

- `request_id` PK
- `citizen_email` — the production citizen account's email (the matching key, same limitation `docs/citizen-document-upload-mobile-api.md` already documents: no better identifier exists yet)
- Submitted profile fields, mirroring `residents`: `first_name`, `middle_name`, `last_name`, `suffix`, `birth_date`, `gender`, `civil_status`, `contact_number`, `barangay`, `street_address`, `occupation`
- Submitted household fields (optional — citizen may say "I'm joining an existing household" or "here's my household"): `household_choice` ENUM(`New`, `Existing`, `None`), `existing_household_id` (nullable FK, only if they searched and picked one), `new_household_number`/`new_household_barangay`/`new_household_street_address`/`new_household_type` (only if `New`)
- `review_status` ENUM(`Pending`, `Approved`, `Rejected`) DEFAULT `Pending`
- `review_notes`, `reviewed_by_name`, `reviewed_at`
- `created_resident_id` (nullable FK, filled in on approval — links the request to the real record it produced)
- `submitted_at`

### 2. Staff review queue (Web)
New sub-page, most naturally under **Resident Management** (its output *is* a Resident Directory entry) — e.g. `pages/resident/registration-requests.php`, gated the same way as the rest of Resident Management (`requireResource('resident management')`).

- List of Pending requests, each showing the submitted data plus a **duplicate-match hint**: server-side lookup by normalized `first_name`+`last_name`+`birth_date` against existing `residents`, surfaced as a warning banner ("Possible match: Resident #42, same name + birth date") — a hint for the reviewer, not an automatic block, since a genuine namesake or a legit second registration attempt both need a human call.
- **Approve** action: reuses `ResidentService::createResident()` / `HouseholdService::createHousehold()` unchanged — same validation, same fields, no forked logic. If `household_choice = Existing`, links to that household; if `New`, creates it first. Sets `created_resident_id` on the request row.
- **Reject** action: requires a reason (same "remarks required" convention as Zoning Clearance transitions), citizen sees it and can correct+resubmit.

### 3. New endpoints
- `api/citizen/resident-registration.php` (citizen-authenticated, mirrors the proxy pattern `docs/citizen-document-upload-mobile-api.md` describes for the document-upload endpoint — validate the relayed citizen session via `api/citizen/get-profile.php` through `config/proxy.php`):
  - `POST` — submit a new registration request (`Pending`)
  - `GET` — check the logged-in citizen's own request status
- `api/employee/resident-registration-requests.php` (staff-authenticated, `requireResource('resident management')`):
  - `GET` — list/filter requests (status, duplicate-hint flag)
  - `PUT` — approve (creates the real resident/household) or reject (requires `review_notes`)

### 4. Mobile UX (conceptual — actual app doesn't exist yet)
```
Citizen logs in (existing OTP flow)
  -> "Register as a resident" (or prompted if no matching record found)
  -> Fill personal info + address
  -> Search existing household OR describe a new one OR skip
  -> Submit -> status shows "Pending Review"
  -> Push/status check -> "Approved" (now visible in Web Resident Directory)
                        or "Rejected: {reason}" -> edit and resubmit
```

## Open decisions before building

1. **Where does the review queue live in the sidebar?** Recommend under Resident Management (output-owner), not Citizen Management (which is purely the production citizen-account proxy today, per `pages/citizen/citizen-directory.php`) — but worth confirming with the team since Citizen Management is the closer conceptual match audience-wise.
2. **Duplicate-match strictness** — hint only (recommended above) vs. hard-block on exact name+birthdate match. Hard-block is simpler but risks locking out a legitimate second family member with a similar name typo; hint-only needs a reviewer to actually look, which is more staff work.
3. **Does an existing household need to *confirm* a new member joining it**, or is staff approval suf­ficient? (Relevant if households can be self-formed rather than always staff-verified.)
4. **Notification on approval/rejection** — reuse `NotificationService`, or is a simple in-app status poll (like the beneficiary-document pattern) enough for v1?

## Related reading
- `docs/mobile-app-plan.md` — architecture rules this must follow (backend-enforced auth, no forked business logic, Mobile is a second client of the same API).
- `docs/citizen-document-upload-mobile-api.md` — the sibling citizen-app feature; same auth pattern, same "no citizen-to-resident link exists yet" gap this plan needs to close for both features (whoever builds one first should build the matching/linking piece so the second doesn't duplicate it).
