let barangayMap = null;
let boundariesLayer = null;
let housingMarkersLayer = null;
let subdivisionsLayer = null;
let housingProjectsLayer = null;
let buildingsLayer = null;

let barangayIndex = new Map(); // psgc_code -> { barangay_id, name, ... }
let boundaryLayerByPsgc = new Map(); // psgc_code -> leaflet layer, for search zoom-to
let subdivisionIndex = new Map(); // subdivision_id -> row, for search
let subdivisionLayerById = new Map(); // subdivision_id -> leaflet layer
let housingProjectIndex = new Map(); // housing_project_id -> row, for search
let housingProjectLayerById = new Map(); // housing_project_id -> leaflet layer

const BOUNDARY_DEFAULT_STYLE = { color: '#176B87', weight: 1.5, fillColor: '#86B6F6', fillOpacity: 0.15 };
const BOUNDARY_HOVER_STYLE = { color: '#176B87', weight: 2.5, fillColor: '#86B6F6', fillOpacity: 0.35 };
const BOUNDARY_SELECTED_STYLE = { color: '#0f172a', weight: 3, fillColor: '#0f172a', fillOpacity: 0.25 };

const SUBDIVISION_COLOR = '#a855f7';
const SUBDIVISION_STYLE = { color: SUBDIVISION_COLOR, weight: 2, fillColor: SUBDIVISION_COLOR, fillOpacity: 0.15 };
const SUBDIVISION_SELECTED_STYLE = { color: '#7e22ce', weight: 3, fillColor: SUBDIVISION_COLOR, fillOpacity: 0.35 };

const HOUSING_PROJECT_COLOR = '#10b981';
const HOUSING_PROJECT_STYLE = { color: HOUSING_PROJECT_COLOR, weight: 2, fillColor: HOUSING_PROJECT_COLOR, fillOpacity: 0.15 };
const HOUSING_PROJECT_SELECTED_STYLE = { color: '#047857', weight: 3, fillColor: HOUSING_PROJECT_COLOR, fillOpacity: 0.35 };

const BUILDING_STYLE = { color: '#c2410c', weight: 1, fillColor: '#fb923c', fillOpacity: 0.4 };
const BUILDING_SELECTED_STYLE = { color: '#7c2d12', weight: 2, fillColor: '#fb923c', fillOpacity: 0.7 };

const ZOOM_SHOW_BUILDINGS = 15;

let selectedLayer = null; // selected barangay boundary
let selectedFeatureLayer = null; // selected subdivision/housing-project/building polygon or marker
let selectedFeatureRestoreStyle = null;
let buildingFetchDebounce = null;

function initBarangayMap() {
  barangayMap = L.map('barangayMap', { scrollWheelZoom: true }).setView([14.6569, 120.9830], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(barangayMap);

  boundariesLayer = L.layerGroup().addTo(barangayMap);
  housingMarkersLayer = L.layerGroup().addTo(barangayMap);
  subdivisionsLayer = L.layerGroup().addTo(barangayMap);
  housingProjectsLayer = L.layerGroup().addTo(barangayMap);
  buildingsLayer = L.layerGroup();

  barangayMap.on('zoomend', scheduleBuildingFetch);
  barangayMap.on('moveend', scheduleBuildingFetch);
}

async function loadBoundaryLayer() {
  const geojson = await fetchBarangayBoundaries();

  const layer = L.geoJSON(geojson, {
    style: () => BOUNDARY_DEFAULT_STYLE,
    onEachFeature: (feature, leafletLayer) => {
      const psgc = feature.properties.psgc_code;
      boundaryLayerByPsgc.set(psgc, leafletLayer);

      leafletLayer.bindTooltip(feature.properties.name, { sticky: true, className: 'text-xs' });

      leafletLayer.on('mouseover', () => {
        if (leafletLayer !== selectedLayer) leafletLayer.setStyle(BOUNDARY_HOVER_STYLE);
      });
      leafletLayer.on('mouseout', () => {
        if (leafletLayer !== selectedLayer) leafletLayer.setStyle(BOUNDARY_DEFAULT_STYLE);
      });
      leafletLayer.on('click', () => selectBarangayByPsgc(psgc));
    },
  });

  layer.addTo(boundariesLayer);

  document.getElementById('statBarangayCount').innerText = geojson.features.length;

  const container = document.getElementById('barangayMap');
  const skeleton = container.querySelector('.skeleton-loader');
  if (skeleton) skeleton.remove();

  if (geojson.features.length) {
    barangayMap.fitBounds(layer.getBounds(), { padding: [10, 10] });
  }
}

async function loadBarangayIndex() {
  const list = await fetchBarangayList();
  barangayIndex = new Map(list.map(b => [b.psgc_code, b]));
}

async function loadHousingMarkers() {
  const markers = await fetchHousingProjectMarkers();

  markers.forEach(unit => {
    const marker = L.marker([parseFloat(unit.latitude), parseFloat(unit.longitude)]);
    marker.bindPopup(`
      <div class="text-xs space-y-1">
        <div class="font-bold text-slate-800">${escapeMapHtml(unit.project_name || unit.unit_code)}</div>
        <div class="text-slate-500">${escapeMapHtml(unit.barangay || 'Barangay not on file')}</div>
        <div class="text-slate-500">${escapeMapHtml(unit.unit_type)} &middot; ${escapeMapHtml(unit.occupancy_status)}</div>
        <a href="../housing/housing-units.php" class="text-brand-dark font-bold underline">View in Housing Units</a>
      </div>
    `);
    marker.addTo(housingMarkersLayer);
  });

  document.getElementById('statMappedUnits').innerText = markers.length;
}

function escapeMapHtml(value) {
  const div = document.createElement('div');
  div.innerText = value ?? '';
  return div.innerHTML;
}

async function selectBarangayByPsgc(psgc) {
  clearFeatureSelection();
  const layer = boundaryLayerByPsgc.get(psgc);
  if (selectedLayer) selectedLayer.setStyle(BOUNDARY_DEFAULT_STYLE);
  if (layer) {
    layer.setStyle(BOUNDARY_SELECTED_STYLE);
    selectedLayer = layer;
  }

  const entry = barangayIndex.get(psgc);
  if (!entry) return;

  document.getElementById('statSelectedBarangay').innerText = entry.name;
  renderBarangayInfoLoading(entry.name);

  const detail = await fetchBarangayDetail(entry.barangay_id);
  if (detail) renderBarangayInfo(detail);
}

function renderBarangayInfoLoading(name) {
  document.getElementById('featureInfoPanel').classList.add('hidden');
  document.getElementById('barangayInfoEmpty').classList.add('hidden');
  document.getElementById('barangayInfoPanel').classList.remove('hidden');
  document.getElementById('infoBarangayName').innerText = name;
}

function renderBarangayInfo(detail) {
  document.getElementById('infoBarangayName').innerText = detail.name;
  document.getElementById('infoBarangayPsgc').innerText = `PSGC ${detail.psgc_code}`;
  document.getElementById('infoHousingUnits').innerText = detail.housing_units ?? 0;
  document.getElementById('infoOccupiedUnits').innerText = detail.occupied_units ?? 0;
  document.getElementById('infoSubdivisionCount').innerText = detail.subdivision_count ?? 0;
  document.getElementById('infoHousingProjectCount').innerText = detail.housing_project_count ?? 0;
  document.getElementById('infoBuildingCount').innerText = detail.building_count ?? 0;
  document.getElementById('infoPendingApplications').innerText = detail.pending_applications ?? 0;
}

function zoomToBarangay(psgc) {
  const layer = boundaryLayerByPsgc.get(psgc);
  if (layer) {
    barangayMap.fitBounds(layer.getBounds(), { padding: [20, 20], maxZoom: 16 });
  }
  selectBarangayByPsgc(psgc);
}

// ---------- Generic feature selection (subdivisions / housing projects / buildings) ----------

function clearFeatureSelection() {
  if (selectedFeatureLayer && selectedFeatureRestoreStyle) {
    if (selectedFeatureLayer.setStyle) selectedFeatureLayer.setStyle(selectedFeatureRestoreStyle);
  }
  selectedFeatureLayer = null;
  selectedFeatureRestoreStyle = null;
}

function showFeatureInfoPanel(type, iconClass, name, rows) {
  document.getElementById('barangayInfoEmpty').classList.add('hidden');
  document.getElementById('barangayInfoPanel').classList.add('hidden');
  document.getElementById('featureInfoPanel').classList.remove('hidden');
  document.getElementById('featureInfoType').innerText = type;
  document.getElementById('featureInfoIcon').className = iconClass;
  document.getElementById('featureInfoName').innerText = name;
  document.getElementById('statSelectedBarangay').innerText = name;

  const rowsEl = document.getElementById('featureInfoRows');
  rowsEl.innerHTML = rows.map(([label, value]) => `
    <div class="bg-slate-50 rounded-xl p-3">
      <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">${label}</span>
      <span class="text-xs font-bold text-slate-800">${value ?? 'Data unavailable'}</span>
    </div>
  `).join('');
}

// ---------- Subdivisions ----------

async function loadSubdivisionsLayer() {
  const rows = await fetchSubdivisionsForMap();
  subdivisionIndex = new Map(rows.map(r => [String(r.subdivision_id), r]));

  rows.forEach(row => {
    let layer;
    if (row.boundary_geojson) {
      let geometry;
      try { geometry = JSON.parse(row.boundary_geojson); } catch (e) { geometry = null; }
      if (geometry) {
        layer = L.geoJSON(geometry, { style: () => SUBDIVISION_STYLE });
      }
    }
    if (!layer) {
      layer = L.circleMarker([parseFloat(row.latitude), parseFloat(row.longitude)], {
        radius: 7, color: SUBDIVISION_COLOR, weight: 2, fillColor: SUBDIVISION_COLOR, fillOpacity: 0.7,
      });
    }
    layer.on('click', () => selectSubdivision(row));
    layer.bindTooltip(row.name, { sticky: true, className: 'text-xs' });
    layer.addTo(subdivisionsLayer);
    subdivisionLayerById.set(String(row.subdivision_id), layer);
  });

  document.getElementById('statSubdivisionCount').innerText = rows.length;
}

function selectSubdivision(row) {
  clearFeatureSelection();
  const layer = subdivisionLayerById.get(String(row.subdivision_id));
  if (layer) {
    selectedFeatureLayer = layer;
    selectedFeatureRestoreStyle = layer instanceof L.CircleMarker
      ? { radius: 7, color: SUBDIVISION_COLOR, weight: 2, fillColor: SUBDIVISION_COLOR, fillOpacity: 0.7 }
      : SUBDIVISION_STYLE;
    layer.setStyle(layer instanceof L.CircleMarker
      ? { radius: 9, color: '#7e22ce', weight: 3, fillColor: SUBDIVISION_COLOR, fillOpacity: 0.9 }
      : SUBDIVISION_SELECTED_STYLE);
  }

  showFeatureInfoPanel('Subdivision', 'fa-solid fa-city text-purple-500', row.name, [
    ['Barangay', row.barangay_name || row.barangay],
    ['Type', row.subdivision_type],
    ['Development Status', row.subdivision_status],
    ['Source', row.source],
    ['Description', row.description],
  ]);
}

function zoomToSubdivision(id) {
  const row = subdivisionIndex.get(String(id));
  const layer = subdivisionLayerById.get(String(id));
  if (layer && layer.getBounds) {
    barangayMap.fitBounds(layer.getBounds(), { padding: [30, 30], maxZoom: 17 });
  } else if (row) {
    barangayMap.setView([parseFloat(row.latitude), parseFloat(row.longitude)], 17);
  }
  if (row) selectSubdivision(row);
}

// ---------- Housing Projects ----------

async function loadHousingProjectsLayer() {
  const rows = await fetchHousingProjectsForMap();
  housingProjectIndex = new Map(rows.map(r => [String(r.housing_project_id), r]));

  rows.forEach(row => {
    let layer;
    if (row.boundary_geojson) {
      let geometry;
      try { geometry = JSON.parse(row.boundary_geojson); } catch (e) { geometry = null; }
      if (geometry) {
        layer = L.geoJSON(geometry, { style: () => HOUSING_PROJECT_STYLE });
      }
    }
    if (!layer) {
      layer = L.circleMarker([parseFloat(row.latitude), parseFloat(row.longitude)], {
        radius: 7, color: HOUSING_PROJECT_COLOR, weight: 2, fillColor: HOUSING_PROJECT_COLOR, fillOpacity: 0.7,
      });
    }
    layer.on('click', () => selectHousingProject(row));
    layer.bindTooltip(row.name, { sticky: true, className: 'text-xs' });
    layer.addTo(housingProjectsLayer);
    housingProjectLayerById.set(String(row.housing_project_id), layer);
  });
}

function selectHousingProject(row) {
  clearFeatureSelection();
  const layer = housingProjectLayerById.get(String(row.housing_project_id));
  if (layer) {
    selectedFeatureLayer = layer;
    selectedFeatureRestoreStyle = layer instanceof L.CircleMarker
      ? { radius: 7, color: HOUSING_PROJECT_COLOR, weight: 2, fillColor: HOUSING_PROJECT_COLOR, fillOpacity: 0.7 }
      : HOUSING_PROJECT_STYLE;
    layer.setStyle(layer instanceof L.CircleMarker
      ? { radius: 9, color: '#047857', weight: 3, fillColor: HOUSING_PROJECT_COLOR, fillOpacity: 0.9 }
      : HOUSING_PROJECT_SELECTED_STYLE);
  }

  showFeatureInfoPanel('Housing Project', 'fa-solid fa-building-shield text-emerald-500', row.name, [
    ['Barangay', row.barangay_name || row.barangay],
    ['Units', row.units],
    ['Project Status', row.project_status],
    ['Developer / Agency', row.developer],
    ['Source', row.source],
    ['Description', row.description],
  ]);
}

function zoomToHousingProject(id) {
  const row = housingProjectIndex.get(String(id));
  const layer = housingProjectLayerById.get(String(id));
  if (layer && layer.getBounds) {
    barangayMap.fitBounds(layer.getBounds(), { padding: [30, 30], maxZoom: 17 });
  } else if (row) {
    barangayMap.setView([parseFloat(row.latitude), parseFloat(row.longitude)], 17);
  }
  if (row) selectHousingProject(row);
}

// ---------- Buildings (zoom + bbox gated) ----------

// Subdivisions (348 rows) and housing projects (a handful) are never zoom-gated —
// only checkbox-controlled, same as the boundaries/housing-units layers. Unlike
// buildings (75k+ rows), there's no performance/clutter reason to hide them at
// city-wide zoom, and gating them by zoom was hiding them entirely on page load
// (the initial view fits all 188 barangays, which zooms out well below any
// reasonable "residential" threshold).
function updateResidentialVisibility() {
  const subCheckbox = document.getElementById('layerSubdivisions');
  const hpCheckbox = document.getElementById('layerHousingProjects');

  if (subCheckbox.checked) {
    if (!barangayMap.hasLayer(subdivisionsLayer)) barangayMap.addLayer(subdivisionsLayer);
  } else if (barangayMap.hasLayer(subdivisionsLayer)) {
    barangayMap.removeLayer(subdivisionsLayer);
  }

  if (hpCheckbox.checked) {
    if (!barangayMap.hasLayer(housingProjectsLayer)) barangayMap.addLayer(housingProjectsLayer);
  } else if (barangayMap.hasLayer(housingProjectsLayer)) {
    barangayMap.removeLayer(housingProjectsLayer);
  }
}

function scheduleBuildingFetch() {
  clearTimeout(buildingFetchDebounce);
  buildingFetchDebounce = setTimeout(loadBuildingsForViewport, 400);
}

async function loadBuildingsForViewport() {
  const hint = document.getElementById('buildingZoomHint');
  const checkbox = document.getElementById('layerBuildings');

  if (!checkbox.checked) {
    buildingsLayer.clearLayers();
    if (barangayMap.hasLayer(buildingsLayer)) barangayMap.removeLayer(buildingsLayer);
    hint.classList.add('hidden');
    return;
  }

  if (barangayMap.getZoom() < ZOOM_SHOW_BUILDINGS) {
    buildingsLayer.clearLayers();
    if (barangayMap.hasLayer(buildingsLayer)) barangayMap.removeLayer(buildingsLayer);
    hint.classList.remove('hidden');
    hint.innerText = 'Zoom in to street level to see building footprints';
    return;
  }

  hint.classList.add('hidden');
  if (!barangayMap.hasLayer(buildingsLayer)) barangayMap.addLayer(buildingsLayer);

  const result = await fetchBuildingsInBbox(barangayMap.getBounds());
  buildingsLayer.clearLayers();

  (result.features || []).forEach(feature => {
    const layer = L.geoJSON(feature, {
      style: () => BUILDING_STYLE,
    });
    layer.on('click', () => selectBuilding(feature.properties.building_id));
    layer.addTo(buildingsLayer);
  });

  if (result.truncated) {
    hint.classList.remove('hidden');
    hint.innerText = `Showing ${result.features.length} of ${result.total_in_view} buildings in view — zoom in further for the rest`;
  }
}

async function selectBuilding(buildingId) {
  const detail = await fetchBuildingDetail(buildingId);
  if (!detail) return;

  showFeatureInfoPanel('Building', 'fa-solid fa-building text-orange-500', `Building #${detail.building_id}`, [
    ['Barangay', detail.barangay_name],
    ['Building Type', detail.building_type || 'Unknown'],
    ['Source', detail.source],
  ]);
}
