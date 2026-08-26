# Thesis Scope Gap Analysis — What's Left to Implement

Comparison of `docs/BSIT_Group 147_Chapter_1-2.docx` Section 1.3.1 ("In Scope") against the current codebase, done 2026-08-15. Use this as the working checklist to close the gap between what the manuscript claims and what the system actually does before defense.

Each item follows the existing module recipe from `CLAUDE.md`: `database/phaseN_*.sql` -> Repository -> Service -> register in `bootstrap.php` -> `api/employee/x.php` -> `pages/<area>/x.php` -> JS bridge -> sidebar entry -> Module Management + Permission Builder (production).

## Status summary

| Doc section | Status | Effort to close gap |
|---|---|---|
| A. Zoning Clearance System | Done (Module Mgmt entry pending) | None |
| B. Housing Beneficiary Registry | Done (report export deferred) | None |
| C. Subdivision and Building Review | Done (browser walkthrough pending) | None |
| D. Occupancy Monitoring Tool | Wrong scope built | Large — separate from existing "Housing Occupancy" |
| E. Infrastructure Project Coordination | Done | None |
| F. AI Planning Assistant | Partial | Medium — RAG layer on top of existing chat |
| G. Platform-wide capabilities | GIS: web spatial layer done 2026-08-15 (mobile deferred) | Small — remaining G items |

## A. Zoning Clearance System — done

Built 2026-08-15 as an MVP scoped down from the full doc description (`database/phase15_zoning_clearances.sql`, `ZoningUseRegulationRepository`/`ZoningClearanceRepository`, `ZoningConformityService`/`ZoningClearanceService`, `api/employee/zoning-clearances.php`, `pages/urban-planning/zoning-clearances.php` + `zoning-clearance-certificate.php`, `assets/js/zoning-clearances/`):
- [x] Online application intake with a resident-applicant picker (reused from Housing Beneficiaries), system-generated `ZC-YYYY-NNNNNN` reference numbers
- [x] Rule-based conformity pre-screening — seeded 8-zone x 7-use regulation matrix, checked live in the intake form (`?action=preview`) and again server-side on save, citing the violated criteria + ordinance reference note
- [x] Multi-level approval workflow — append-only `zoning_clearance_reviews` audit log (reviewer, free-text role, action, remarks) driven by a status-transition endpoint that requires remarks on every change
- [x] Printable Certificate of Zoning Compliance (with verification code) / Notice of Non-Conformity (citing findings), same `reports-print.css` pattern as the Reports module
- [x] Fee assessment (base + per-sqm rate by use category, documented constants) + payment status tracking (no live gateway, matches doc 1.3.2 out-of-scope)
- [x] Sidebar entry under Urban Planning, rides the existing `urban planning` production resource grant (same as Development Plans)
- [ ] Two scope simplifications carried over from the plan: zone classification is self-declared (no GIS/parcel layer yet — see section G), and the regulation matrix has no admin CRUD UI yet (edit via SQL/DB tools)
- [ ] Module Management + Permission Builder entry in production is still a manual step for the team to do (a dedicated "Zoning Clearance" resource, if finer-grained permissions than the shared "urban planning" grant are wanted later)
- [ ] End-to-end verified via a scratch CLI test (conformity rules, reference-number uniqueness, fee math, full lifecycle) and a live browser walkthrough (submit → live preview → approve → printable certificate)

## B. Housing Beneficiary Registry — done (except reporting)

Closed 2026-08-15 (`database/phase14_beneficiary_scoring.sql`, `BeneficiaryService`, `HousingBeneficiaryRepository`, and the beneficiaries page/JS bridge):
- [x] Duplicate detection — blocks a new/re-opened application when the resident already has an open application, or when the resident's household already has another open application (one award per household)
- [x] Eligibility scoring — server-computed 0-100 score (income, family size, category/vulnerability, household tenure), weights as documented class constants on `BeneficiaryService`; recomputed on every create/update
- [x] Waitlist / prioritization ordering — "Priority (Waitlist)" sort option orders by `eligibility_score DESC`
- [x] Amortization status field (`Not Started`/`Current`/`Delinquent`/`Completed`) — tracking only, no collection logic, matches the doc's "Definition of Terms"
- [ ] DHSUD/local housing board report export — deferred to the `pages/reports` module rather than duplicating that pattern here
- [ ] Follow-up: expose the scoring weights/threshold through an admin UI instead of hardcoded constants, if there's time before defense

## C. Subdivision and Building Review — done (full scope)

Built 2026-08-19 as full scope (Subdivision Plan + Building Permit, not scoped down to permits-only) per explicit user decision (`database/phase17_subdivision_building_review.sql`, `PermitApplicationRepository`/`Service`, `PermitPlanDocumentRepository`, `api/employee/permit-applications.php` + `permit-plan-documents.php`, `pages/urban-planning/permit-applications.php` + `permit-certificate.php`, `assets/js/permit-applications/`):
- [x] Permit applications, plan document versions, review comments, resubmission history — `permit_applications` / `permit_plan_documents` (versioned, superseding) / `permit_application_reviews` (append-only audit + threaded discipline comments) / `resubmission_round` tracking with a dedicated resubmit action
- [x] Modeled as one `PermitApplicationRepository`/`Service` pair with an `application_type` discriminator (Subdivision Plan / Building Permit), not two separate repo/service pairs — deliberate deviation from this doc's literal naming suggestion in favor of its own "model as one application with N discipline-review sub-records" guidance
- [x] Plan document versioning + comment threading + resubmission tracking
- [x] Multi-discipline review routing (Architectural, Structural, Sanitary, Electrical, Fire Safety) with a consolidated evaluation summary — `permit_discipline_reviews` (one sub-record per discipline per application) + auto-computed `consolidated_result` (informational, mirrors Zoning Clearance's `conformity_result` pattern)
- [x] Permit issuance + conditions of approval — gated on application Approved **and** all five discipline reviews Approved; zone/parcel linkage is self-declared (`barangay`/`street_address`) same as Zoning Clearance, since no parcel/GIS layer exists yet (see section G)
- [x] Single `api/employee/permit-applications.php` (not two files) + `api/employee/permit-plan-documents.php`, pages, JS bridge, sidebar entry — deliberate deviation from this doc's literal two-endpoint-file suggestion in favor of the codebase's established one-file-per-resource convention
- [x] Verified with a standalone PHP CLI test against the real local DB (44 assertions: fee math, discipline auto-creation/routing, consolidated-result recompute, resubmission round + reset, issuance gate, reference-number uniqueness, plan document versioning/superseding, stats/pagination) — all passed
- [x] No dedicated Module Management / Permission Builder entry needed — confirmed with the user this rides the shared `urban planning` resource grant, same precedent as Zoning Clearance and Barangay Mapping (both explicitly skipped a dedicated resource for the same reason). `PermissionMiddleware::requireResource()` is fallback-permissive by design (any logged-in user passes if no resource matches), so this only affects sidebar visibility and the Create/Edit buttons, both already correctly gated on `urban planning`.
- [ ] Live browser walkthrough not done this session (Claude-in-Chrome extension wasn't connected) — worth a manual click-through before the defense
- [ ] Barangay-map integration (surfacing applications on `pages/urban-planning/mapping.php`) explicitly deferred per user instruction

## D. Occupancy Monitoring Tool — wrong scope built

`HousingOccupancyService` (move-in/move-out of a beneficiary's assigned unit) is a different feature that happens to share the word "occupancy." It does not cover what the doc describes. Two options:
- [ ] **Option 1 (matches doc):** build a real Certificate-of-Occupancy module — CO application, inspection scheduling/issuance, detection of occupancy-without-certificate and use deviation from approved permit, risk-based inspection prioritization, Notice of Violation issuance and resolution tracking. This depends on the Building Review module (C) existing first, since a CO is issued against an approved building permit.
- [ ] **Option 2 (matches reality):** rewrite Section 1.3.1(D) in the manuscript to describe what `HousingOccupancyService` + the new Field Survey module actually do (unit occupancy tracking + condition surveys with photo capture), and drop the CO/violation-detection language.

Recommend Option 2 unless there's runway to build Option 1 for real — don't let the document keep promising a compliance/enforcement tool the system doesn't have.

The new (uncommitted) Field Survey module already covers "mobile-responsive inspection forms with photo capture, and offline-tolerant submission" from the doc — that part is legitimately done regardless of which option is picked.

## E. Infrastructure Project Coordination — done

`UrbanProjectRepository`/`Service` + `InfrastructureRecordRepository`/`Service` (phase9/phase10) cover registry, milestone tracking, and documents. No action needed.

## F. AI Planning Assistant — partial

`GeminiService` is a working chatbot grounded in a live analytics snapshot (resident/housing/project/survey counts). The doc promises more:
- [ ] Retrieval-augmented generation over the LGU's Zoning Ordinance / CLUP / national codes, with citations to the source provision — requires ingesting ordinance text into a searchable store (even a simple keyword/section-lookup table would satisfy "citations to the source provision" without a full vector DB)
- [ ] Application dossier / inspection history summarization for reviewers
- [ ] English/Filipino toggle for citizen-facing explanations
- [ ] Draft generation of routine notices and evaluation remarks (depends on Zoning Clearance / Building Review existing, since there's nothing to draft notices about yet)

Suggest sequencing this after A and C exist, since several of these features (dossier summaries, draft notices) have nothing to operate on until those modules have data.

## G. Platform-wide capabilities — mostly done

- [x] RBAC — production system, already wired per `CLAUDE.md`
- [x] Audit logging — `ActivityLogRepository`/`Service` (phase13, new)
- [x] Executive dashboard / cross-module KPIs — `AnalyticsRepository` (new)
- [x] Notifications — `NotificationService`
- [x] Interactive web map — built 2026-08-15 (`database/phase16_barangay_mapping.sql`, `BarangayRepository`/`BarangayService`, `api/employee/barangays.php`, `pages/urban-planning/mapping.php`, `assets/js/mapping/`): Leaflet boundary map of all 188 Caloocan barangays (PSA/PSGC-sourced GeoJSON, not hand-drawn) with search, click/hover, a barangay info panel (housing units/occupancy/applications, joined by name — no FK exists on `barangay` columns), and a housing-project marker layer (`housing_units` gained optional `latitude`/`longitude`). Mobile mapping and AI-map integration (chat -> highlight barangays) are still open — no React Native app exists in this repo yet, and `GeminiService` isn't a query/tool-calling architecture, so building either now would be speculative ahead of need, per this doc's own guidance.
- [ ] Shared parcel-centric spatial data model — the barangay-level layer above is not parcel-centric. Zoning Clearance (A) still self-declares its zone (no parcel/zoning-district polygon layer yet); Building Review (C) will need this when it's built. A natural follow-up once C exists: add a `zoning_districts` (or parcel) polygon layer and a real point-in-polygon zone lookup, replacing the self-declared zone field.
- [ ] RA 11032 processing-time compliance monitoring — needs per-application timestamps against statutory processing-time limits; depends on A/C existing
- [ ] RA 10173 data privacy safeguards (consent capture, field-level access restriction, retention rules) — not evident in current schema; needs a consent-capture field on citizen-facing intake forms and a retention-policy note, at minimum

## Suggested build order

1. ~~B (close the gap)~~ — done 2026-08-15
2. ~~G — spatial layer (web)~~ — done 2026-08-15, barangay-level only (see section G for the parcel-level follow-up C will need)
3. ~~A — Zoning Clearance System~~ — done 2026-08-15 (built ahead of the spatial layer; zone classification is self-declared for now, see section G)
4. ~~C — Subdivision and Building Review~~ — done 2026-08-19, full scope (not scoped down)
5. **D** — pick Option 1 or 2 above now that A/C exist
6. **F — RAG layer** — last, since it's most valuable once A/C/D have real records to summarize/cite
7. **G — remaining items** (RA 11032 timers, RA 10173 consent capture) — incremental, can land alongside A/C

Run each new module by the team before starting, per the working-style note in `CLAUDE.md` — build one at a time and pause for approval.
