async function fetchBarangayBoundaries() {
  const response = await fetch('../../assets/geojson/caloocan-barangays.geojson');
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
