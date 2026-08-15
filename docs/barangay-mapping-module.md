# Barangay Mapping Module

Reference doc for the interactive barangay map built 2026-08-15 (Phase 16), covering the tech stack, data source, schema, API, and frontend implementation. This is the "G — spatial layer" item from `docs/thesis-scope-gap-analysis.md`, scoped to **web only** — mobile and AI-map integration are documented below as explicitly deferred, not forgotten.

## What it is

An interactive Leaflet map of all 188 Caloocan barangays with boundary polygons, search, click-to-select, a per-barangay stats panel (housing units, occupancy, applications), and a housing-project marker layer. Staff-facing only, at **Urban Planning → Barangay Map** (`pages/urban-planning/mapping.php`).

## Tech stack used

| Layer | Choice | Why |
|---|---|---|
| Map rendering | [Leaflet 1.9.4](https://leafletjs.com/) via CDN (`unpkg.com`) | No build step, matches how Tailwind is already loaded in `header.php`; the codebase has no bundler. Loaded only on `mapping.php`, not globally, so other pages don't pay for it. |
| Base tiles | OpenStreetMap standard tile layer (`{s}.tile.openstreetmap.org`) | Free, no API key, matches the project's brief (OpenStreetMap as base map). |
| Boundary data | GeoJSON, static file served by Apache | One request for all 188 polygons (`assets/geojson/caloocan-barangays.geojson`, ~74KB) rather than per-barangay API calls. |
| Backend | PHP, no framework | Same Repository → Service → API pattern as every other local-DB module (see `CLAUDE.md`). |
| Database | MySQL via the existing PDO `Database` wrapper | One new table (`barangays`), two new columns on `housing_units`. |
| Frontend JS | Vanilla JS "bridge" pattern | Same `assets/js/<module>/{api,map,events,app}.js` convention as every other module, registered in `assets/js/app.js`'s loader. |

No new external libraries beyond Leaflet were introduced (per `CLAUDE.md`'s "prefer no new external libraries" guidance — Leaflet was explicitly requested and has no lighter no-build alternative for interactive polygon maps).

## GeoJSON boundary data

- **Source**: [`faeldon/philippines-json-maps`](https://github.com/faeldon/philippines-json-maps) (MIT-licensed), medium-resolution barangay/sub-municipality boundaries, PSA PSGC administrative data as of December 2023. File: `2023/geojson/municities/medres/bgysubmuns-municity-1380100000.0.001.json` (`1380100000` is Caloocan's PSGC city code).
- **Why medium- not low-resolution**: the low-res file has one barangay (Barangay 164) with a null/degenerate geometry after simplification; medium-res has valid `Polygon` geometry for all 188 features. Verified via a one-off PHP script (not checked in) that parsed both files and diffed geometry types.
- **Barangay count discrepancy**: the original request referenced 193 barangays. The source data — and the current official PSA PSGC count for Caloocan — is **188**. This was flagged rather than silently reconciled; do not "top up" to 193 without an authoritative source for the extra 5.
- **Processing**: each feature's properties were slimmed to just `psgc_code` and `name` (dropping `adm1_psgc`/`adm2_psgc`/`adm3_psgc`/`geo_level`/`len_crs`/`area_crs`/`len_km`/`area_km2`, which are redundant or unused), and a polygon centroid was computed per feature using the shoelace formula (area-weighted, not a plain vertex average) for the `barangays.centroid_lat`/`centroid_lng` seed columns.
- **Where it lives**: `assets/geojson/caloocan-barangays.geojson` — this project has no `/public` directory, so it follows the existing `assets/css`, `assets/js` convention rather than the originally-suggested `/public/geojson/` path.
- **Served as a static file**, not through a PHP endpoint — it's public geographic boundary data (not sensitive), and serving it statically is what makes it "one GeoJSON request" instead of a per-barangay API call.

## Database

`database/phase16_barangay_mapping.sql`:

```sql
CREATE TABLE barangays (
  barangay_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  psgc_code     VARCHAR(20) NOT NULL UNIQUE,
  name          VARCHAR(100) NOT NULL UNIQUE,
  district      VARCHAR(50) NULL,       -- source data has no legislative-district field; left NULL
  centroid_lat  DECIMAL(10,8) NULL,
  centroid_lng  DECIMAL(11,8) NULL,
  created_at, updated_at TIMESTAMP
);

ALTER TABLE housing_units
  ADD COLUMN latitude  DECIMAL(10,8) NULL AFTER street_address,
  ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude;
```

Design decisions worth knowing:

- **No `housing_projects` table.** `housing_units` is this codebase's existing "housing project" concept (`project_name`, `barangay`, `occupancy_status`, etc.), so coordinates were added there instead of introducing a parallel table, per the user's own scope decision.
- **No FK from `housing_units.barangay` to `barangays`.** The existing `barangay` columns across `residents`, `housing_units`, `urban_projects`, `infrastructure_records`, and `zoning_clearances` are all free-text `VARCHAR(100)`, predating this module. Retrofitting all of them to a `barangay_id` FK was out of scope for this pass (would touch five existing tables/modules). Stats are joined **by name** instead — see "Known limitations" below.
- **`barangays.psgc_code`/`name` are both `UNIQUE`** so the seed can't silently duplicate a barangay.

## Backend

- `src/Repositories/BarangayRepository.php` — `all($search)`, `find($id)`, `stats($barangayName)` (joins `housing_units` by name, and `housing_beneficiaries` through `housing_units.unit_id`), `housingUnitMarkers()` (one query for every unit with coordinates).
- `src/Services/BarangayService.php` — `detail($id)` merges `find()` + `stats()` into one payload for the API.
- Both registered in `src/bootstrap.php` (`$barangayRepo`, `$barangayService`), following the same require/instantiate pattern as every other repository/service.
- `api/employee/barangays.php` — GET-only REST endpoint:
  - `GET /api/employee/barangays.php?search=...` — list/search (name substring match)
  - `GET /api/employee/barangays.php?id=<barangay_id>` — detail + stats
  - `GET /api/employee/barangays.php?action=housing-markers` — all housing units that have coordinates
  - Gated by `PermissionMiddleware::requireResource('urban planning', ...)` — rides the same production RBAC resource Zoning Clearances uses, rather than creating a new Module Management entry for this pass.
- `src/Services/HousingService.php` — extended `sanitizeFields()`'s allow-list with `latitude`/`longitude`, and `validateHousingInput()` now range-checks them (-90..90 / -180..180) when present.

## Frontend

- `pages/urban-planning/mapping.php` — page shell (breadcrumb, stat cards, search bar, layer-toggle checkboxes, map container, info panel). Leaflet's CSS/JS `<link>`/`<script>` tags are declared directly in this page's body (after `header.php`/`sidebar.php` are included), not in `header.php`, so no other page loads Leaflet.
- `assets/js/mapping/api.js` — thin fetch wrappers (`fetchBarangayBoundaries`, `fetchBarangayList`, `fetchBarangayDetail`, `fetchHousingProjectMarkers`).
- `assets/js/mapping/map.js` — Leaflet setup: base map, boundary `L.geoJSON` layer (hover/click/tooltip styling), housing marker layer, `selectBarangayByPsgc()` (click handler → info panel), `zoomToBarangay()` (search result → `fitBounds`).
- `assets/js/mapping/events.js` — search-box autocomplete (client-side filter over the already-fetched barangay list, no per-keystroke API calls) and the two layer-toggle checkboxes.
- `assets/js/mapping/app.js` — bridge loader entry, registered in `assets/js/app.js`'s `loadCiventralScript` list. Self-gates on `document.getElementById('barangayMap')` existing, so it's a no-op on every other page.
- Sidebar: "Barangay Map" added inside the existing **Urban Planning** dropdown in `includes/sidebar.php` (`$urbanPlanningPages` array + one `<a>` link) — not a new top-level nav section, so `assets/js/dashboard.js`'s dropdown-ID arrays didn't need updating.
- The Housing Units form (`pages/housing/housing-units.php` + its `modal.js`/`api.js`) gained optional Latitude/Longitude fields so staff can actually populate marker coordinates — without this, the housing-project layer would have nothing to show.

## Layers implemented (and why the other two weren't)

The brief listed four example layers: Barangay Boundaries, Housing Projects, Housing Applications, Urban Planning Projects. Only the first two exist:

- **Barangay Boundaries** — the GeoJSON polygon layer.
- **Housing Projects** — `housing_units` rows with coordinates, as map markers.
- **Housing Applications** — not implemented. `housing_beneficiaries` has no coordinates of its own; it only reaches a barangay indirectly through `unit_id → housing_units.barangay`, and only once a unit is assigned (not while still "Applicant"/"Qualified"). No marker layer to build without fabricating point data.
- **Urban Planning Projects** — not implemented. `urban_projects` has a free-text `barangay` column but no `latitude`/`longitude`. Adding coordinates there is a natural next slice, mirroring exactly what was done for `housing_units`.

## Security

- Every endpoint requires a logged-in local staff session (`$authService->isLoggedIn()`) and `PermissionMiddleware::requireResource('urban planning', ...)` — no data is exposed to unauthenticated or unauthorized users.
- The static GeoJSON boundary file is **not** behind the PHP auth gate (it's served directly by Apache). This is intentional: it's public administrative boundary geometry (barangay shapes), not resident/citizen data, so gating it behind a PHP endpoint would only add latency without a real confidentiality benefit.
- No credentials, API keys, or `.env` values are referenced anywhere in this module.
- Lat/lng input on the Housing Units form is range-validated server-side in `HousingService::validateHousingInput()`; all queries use PDO prepared statements via the existing `Database` wrapper (no raw string interpolation of user input).

## Mobile integration — not started

No React Native app exists anywhere in this repository. `docs/mobile-app-plan.md` describes a planned **field-surveyor** mobile app (different audience, not built), and there's a separately-planned citizen mobile app (see `docs/citizen-self-registration-mobile-plan.md`) — neither currently has a codebase to extend. Before any mobile mapping work starts:

1. Confirm which of those two planned apps (or a third one) is meant to consume this — the brief's "view barangay boundaries, search, view housing project locations" reads like the field-surveyor app, since citizens likely don't need city-wide barangay administration data.
2. Reuse `api/employee/barangays.php` — do **not** stand up a second database or duplicate the boundary GeoJSON. The existing endpoint already returns clean JSON; a mobile client just needs a compatible auth mechanism (see `docs/mobile-app-plan.md`/`docs/citizen-document-upload-mobile-api.md` for how session auth currently doesn't extend to non-web clients — the same gap applies here).
3. Pick a React Native mapping library once the app itself exists (e.g. `react-native-maps` with a GeoJSON overlay, or `react-native-webview` embedding Leaflet) — no recommendation is baked in yet since there's no app to integrate into.

## AI integration — not started

`GeminiService` (`src/Services/GeminiService.php`) is a grounded chatbot today — it stuffs an `AnalyticsRepository` snapshot into the system instruction and answers questions about it. It is **not** a tool-calling/function-calling architecture, so it can't yet decide "call the barangays API, then highlight results on the map" on its own. Wiring "which barangays have the highest housing demand?" → map highlight would need:

1. Either a new structured query path in `GeminiService` (function calling against `BarangayRepository`/`AnalyticsRepository`), or a simpler hardcoded prompt template + a `barangays.php?action=...` aggregate endpoint the chat UI calls directly and then cross-references against the map's `boundaryLayerByPsgc` map to highlight results.
2. This was deliberately not built now — per the original brief, speculative AI functionality shouldn't be added ahead of the existing Gemini architecture supporting it, and `docs/thesis-scope-gap-analysis.md` sequences the RAG/AI layer (section F) after the modules that would give it something real to reason about.

## Known limitations / follow-ups

- **Name-based joins, not foreign keys.** Barangay stats join `housing_units.barangay = barangays.name` (case-sensitive exact match today). A typo or inconsistent casing in a `barangay` free-text field anywhere upstream will silently exclude that record from a barangay's stats. A future pass could add a `barangay_id` FK to `housing_units` (and eventually the other four tables that carry free-text `barangay`) and populate it via a one-time backfill matched against `barangays.name`.
- **Housing applications without an assigned unit aren't attributable to any barangay** (see "Layers implemented" above) — a structural limitation of the current schema, not a bug.
- **No parcel-level layer.** This module is barangay-level only. Zoning Clearance (module A) still self-declares its zone with no polygon-based lookup; Building Review (module C, not yet built) will need real parcel/zoning-district geometry. That's a distinct, larger follow-up — see `docs/thesis-scope-gap-analysis.md`, section G.
- **No Module Management/Permission Builder entry yet.** The map rides the existing `urban planning` resource grant. If finer-grained permissioning is wanted later (e.g. a role that can see Zoning Clearances but not the map), a dedicated "Barangay Mapping" resource would need to be created in production, same manual step every other module has documented.
