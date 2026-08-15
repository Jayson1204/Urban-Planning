# CIVENTRAL Module Guide

Reference doc explaining what each module does and how they connect. Covers Resident Management, Housing Management, Field Survey, and Urban Planning, including every sub-module.

## 1. Resident Management

**Purpose:** The foundational people/address registry every other module references back to. If a module needs to know *who* someone is or *where* a household lives, it points here instead of storing its own copy.

- **Resident Directory** — one row per person (name, birth date, gender, civil status, contact info, barangay/street address, occupation). This is the canonical "who" record. Any resident can optionally belong to a household.
- **Household Management** — one row per dwelling/family unit (household number, barangay, street address, tenure type: Owned/Renting/Informal Settler/Other). Residents link to a household via `household_id`; a household's `household_type` (e.g., "Informal Settler") also feeds directly into Housing Beneficiary eligibility scoring.

**Flow:** Register the household first (or leave a resident unattached), then register residents against it. Every downstream module — Housing Beneficiaries, Zoning Clearances, Field Survey subjects — reuses this resident/household pool via a search-and-pick UI rather than re-entering identity data.

## 2. Housing Management

**Purpose:** Tracks the full lifecycle of a socialized-housing unit — from vacant inventory, to who's applying for it, to who's actually living in it, to when they move.

- **Housing Units** — the physical inventory (unit code, project name, type, address, floor area, bedrooms, amortization amount, occupancy status: Vacant/Occupied/Reserved/Under Maintenance).
- **Beneficiaries** (Housing Beneficiary Registry) — the applicant pipeline: a resident applies -> `Applicant` -> `Qualified` -> `Awarded` (linked to a specific unit + award date) or `Disqualified`/`Cancelled`. On every save it computes:
  - an **eligibility score** (0-100, weighted on income, family size, vulnerability category, and household tenure)
  - **duplicate-detection** blocking (one open application per resident, and per household)
  - Awarding a unit automatically flips that unit's occupancy status to Occupied.
- **Occupancy** — the actual move-in/move-out ledger. Separate from "Awarded" status on purpose: an occupancy record can span multiple stays and isn't limited to beneficiary-driven moves (someone can just be assigned a unit directly).
- **Relocation Records** — when a resident needs to move from one unit to another (Overcrowding, Safety Issue, Unit Upgrade, etc.). A relocation **automatically** closes the resident's old occupancy record and opens a new one on the destination unit — you don't touch Occupancy separately for a relocation.

**Flow:** Housing Units (inventory exists) -> Beneficiaries (someone applies and gets awarded a unit) -> Occupancy (they move in) -> Relocation Records (later, they move to a different unit if needed, which itself updates Occupancy again). Every step keeps the unit's `occupancy_status` in sync automatically.

## 3. Field Survey

**Purpose:** Structured data collection in the field — inspections, assessments, condition surveys — decoupled from who's doing the inspecting versus what's being inspected.

- **Survey Forms** — the *template*: what kind of survey is this (Household Assessment, Infrastructure Condition, Land Use Assessment, Socioeconomic Survey), and what kind of subject does it target (a Resident, a Household, or a generic Site/location)?
- **Survey Assignments** — the *task*: pick a form, pick a subject (a specific resident, a specific household, or a free-text site address/label if it's not tied to an existing record), assign it to a field staff member, set a due date. Status moves Pending -> In Progress -> Completed/Cancelled.
- **Survey Results** — the *findings*: one result per assignment (condition rating Excellent through Critical, population count, income bracket, findings/recommendations notes). Recording a result automatically marks the assignment Completed — there's no separate "mark as done" step.
- **Survey History** — a read-only merged timeline joining assignments + results per subject, computed on the fly rather than stored separately, so you can see a site or household's full inspection history in one place.

**Flow:** Design the form once -> assign it to whoever/wherever needs inspecting -> the field surveyor's submitted result closes out the assignment and feeds the history view. This module is also what the field-surveyor mobile app (planned separately, see `docs/mobile-app-plan.md`) will drive — the web side is where forms get authored and results get reviewed.

## 4. Urban Planning

**Purpose:** The city-level planning and regulatory layer — the actual permitting/compliance/coordination side of the system, as opposed to the household-level modules above.

- **Development Plans** — the highest-level planning documents (Comprehensive Land Use Plan, Zoning Plan, Infrastructure Plan, Development Framework), each with coverage area, lead department, budget, and a lifecycle (Draft -> Active -> Completed -> Archived). This is the umbrella other planning records can optionally hang off of.
- **Zoning Clearances** — the most process-heavy module:
  1. A resident applies for a project (zone classification + proposed use + project figures: lot area, height, setback, floor area ratio, lot occupancy).
  2. The system **automatically pre-screens conformity** against a seeded regulation matrix (which uses are Permitted/Conditional/Prohibited per zone, plus numeric limits) — live in the form, and again permanently on save, citing the specific violated limit and an ordinance reference.
  3. A reviewer routes it through stages (Submitted -> Under Review -> Returned for Revision -> Approved/Denied), and every stage change is logged permanently with reviewer, role, and required remarks — a real audit trail, not just a status flag.
  4. Approval generates a verification code and unlocks a printable **Certificate of Zoning Compliance**; denial for non-conformity unlocks a printable **Notice of Non-Conformity** citing the specific findings.
  5. Along the way it also computes a fee (base + per-sqm rate by use category) and tracks payment status.
- **Urban Projects** — city infrastructure/development projects (roads, drainage, public buildings, parks), optionally linked back to a Development Plan, tracked through Planned -> Ongoing -> Completed/Delayed/Cancelled with budget and contractor info.
- **Infrastructure Records** — the physical assets themselves (a specific road, bridge, drainage system, streetlight, public facility) with a condition status (Good/Needs Repair/Under Construction/Non-Functional), optionally linked back to the Urban Project that built or maintains it.

**Flow:** A Development Plan sets city-wide direction -> individual Urban Projects get scoped under it -> each project can produce or maintain specific Infrastructure Records. Separately (and independently), residents apply for Zoning Clearances against the same zone/regulation data whenever they want to build or use land in a way that needs LGU sign-off — that path doesn't depend on Plans/Projects at all, it just needs an applicant (resident) and the regulation matrix.

## How the four modules connect end-to-end

A typical resident's journey through the system:

1. **Resident Management** registers them and their household.
2. They apply through **Housing Management -> Beneficiaries**, get scored, awarded a unit, move in (**Occupancy**), and maybe relocate later.
3. Meanwhile, **Field Survey** can independently inspect their household or unit (condition assessments feed into planning decisions).
4. If they want to build or use their property in a new way, they go through **Urban Planning -> Zoning Clearances**, which checks against the same regulatory rules the LGU uses for its own **Development Plans / Urban Projects / Infrastructure**.

Every module reuses Resident Management as its identity source rather than duplicating applicant data, which is the main architectural thread tying all of this together.
