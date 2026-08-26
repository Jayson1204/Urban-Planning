async function fetchBarangayBoundaries() {
  const response = await fetch('../../assets/geojson/caloocan-barangays.geojson');
  if (!response.ok) {
    throw new Error(`Failed to load barangay boundaries (HTTP ${response.status}).`);
  }
  return response.json();
}

async function fetchBarangayList(search = '') {
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  const response = await fetch(`../../api/employee/barangays.php?${params.toString()}`);
  const result = await response.json();
  return result.status === 'success' ? result.data : [];
}

async function fetchBarangayDetail(barangayId) {
  const response = await fetch(`../../api/employee/barangays.php?id=${barangayId}`);
  const result = await response.json();
  return result.status === 'success' ? result.data : null;
}

async function fetchHousingProjectMarkers() {
  const response = await fetch('../../api/employee/barangays.php?action=housing-markers');
  const result = await response.json();
  return result.status === 'success' ? result.data : [];
}

async function fetchSubdivisionsForMap() {
  const response = await fetch('../../api/employee/subdivisions.php?action=map');
  const result = await response.json();
  return result.status === 'success' ? result.data : [];
}

async function fetchHousingProjectsForMap() {
  const response = await fetch('../../api/employee/housing-projects.php?action=map');
  const result = await response.json();
  return result.status === 'success' ? result.data : [];
}

async function fetchBuildingStats() {
  const response = await fetch('../../api/employee/buildings.php?action=stats');
  const result = await response.json();
  return result.status === 'success' ? result.data.total : 0;
}

async function fetchBuildingDetail(buildingId) {
  const response = await fetch(`../../api/employee/buildings.php?id=${buildingId}`);
  const result = await response.json();
  return result.status === 'success' ? result.data : null;
}

async function fetchBuildingsInBbox(bounds) {
  const bbox = [bounds.getWest(), bounds.getSouth(), bounds.getEast(), bounds.getNorth()].join(',');
  const response = await fetch(`../../api/employee/buildings.php?bbox=${bbox}`);
  const result = await response.json();
  return result.status === 'success' ? result : { features: [], truncated: false };
}
