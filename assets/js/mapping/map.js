let barangayMap = null;
let boundariesLayer = null;
let housingMarkersLayer = null;
let barangayIndex = new Map(); // psgc_code -> { barangay_id, name, ... }
let boundaryLayerByPsgc = new Map(); // psgc_code -> leaflet layer, for search zoom-to

const BOUNDARY_DEFAULT_STYLE = { color: '#176B87', weight: 1.5, fillColor: '#86B6F6', fillOpacity: 0.15 };
const BOUNDARY_HOVER_STYLE = { color: '#176B87', weight: 2.5, fillColor: '#86B6F6', fillOpacity: 0.35 };
const BOUNDARY_SELECTED_STYLE = { color: '#0f172a', weight: 3, fillColor: '#0f172a', fillOpacity: 0.25 };

let selectedLayer = null;

function initBarangayMap() {
  barangayMap = L.map('barangayMap', { scrollWheelZoom: true }).setView([14.6569, 120.9830], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(barangayMap);

  boundariesLayer = L.layerGroup().addTo(barangayMap);
  housingMarkersLayer = L.layerGroup().addTo(barangayMap);
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
  document.getElementById('barangayInfoEmpty').classList.add('hidden');
  document.getElementById('barangayInfoPanel').classList.remove('hidden');
  document.getElementById('infoBarangayName').innerText = name;
}

function renderBarangayInfo(detail) {
  document.getElementById('infoBarangayName').innerText = detail.name;
  document.getElementById('infoBarangayPsgc').innerText = `PSGC ${detail.psgc_code}`;
  document.getElementById('infoHousingUnits').innerText = detail.housing_units ?? 0;
  document.getElementById('infoOccupiedUnits').innerText = detail.occupied_units ?? 0;
  document.getElementById('infoTotalApplications').innerText = detail.total_applications ?? 0;
  document.getElementById('infoApprovedApplications').innerText = detail.approved_applications ?? 0;
  document.getElementById('infoPendingApplications').innerText = detail.pending_applications ?? 0;
}

function zoomToBarangay(psgc) {
  const layer = boundaryLayerByPsgc.get(psgc);
  if (layer) {
    barangayMap.fitBounds(layer.getBounds(), { padding: [20, 20], maxZoom: 16 });
  }
  selectBarangayByPsgc(psgc);
}
