// Global State
let housingData = [];
let housingPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };

// Toast Popup
function showToast(message) {
  const toast = document.getElementById('toast');
  const toastMsg = document.getElementById('toastMsg');
  if (!toast || !toastMsg) return;

  toastMsg.innerText = message;
  toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
  toast.classList.add('opacity-100', 'translate-y-0');

  setTimeout(() => {
    toast.classList.remove('opacity-100', 'translate-y-0');
    toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
  }, 3200);
}

// Fetch paginated/filtered housing units from the server
async function fetchHousingUnits(page = 1) {
  const search = document.getElementById('housingSearchInput').value.trim();
  const barangay = document.getElementById('housingBarangayFilter').value.trim();
  const occupancy = document.getElementById('housingOccupancyFilter').value;
  const status = document.getElementById('housingStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: housingPagination.per_page });
  if (search) params.set('search', search);
  if (barangay) params.set('barangay', barangay);
  if (occupancy) params.set('occupancy_status', occupancy);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/housing-units.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      housingData = result.data || [];
      housingPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderHousingUnits();
      renderHousingPagination();
    } else {
      showToast(result.message || 'Error loading housing units.');
    }
  } catch (err) {
    console.error('Error fetching housing units:', err);
    showToast('Network error while loading housing units.');
  }
}

async function fetchHousingStats() {
  try {
    const response = await fetch('../../api/employee/housing-units.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalUnits').innerText = s.total || 0;
      document.getElementById('statVacantUnits').innerText = s.vacant || 0;
      document.getElementById('statOccupiedUnits').innerText = s.occupied || 0;
      document.getElementById('statArchivedUnits').innerText = s.archived || 0;
    }
  } catch (err) {
    console.error('Error fetching housing stats:', err);
  }
}

async function handleSaveHousingUnit(e) {
  e.preventDefault();

  const idRef = document.getElementById('housingIdRef').value;
  const payload = {
    unit_code: document.getElementById('housingUnitCode').value.trim(),
    project_name: document.getElementById('housingProjectName').value.trim(),
    unit_type: document.getElementById('housingUnitType').value,
    floor_area: document.getElementById('housingFloorArea').value || null,
    bedrooms: document.getElementById('housingBedrooms').value || null,
    occupancy_status: document.getElementById('housingOccupancyStatus').value,
    barangay: document.getElementById('housingBarangay').value.trim(),
    street_address: document.getElementById('housingStreetAddress').value.trim(),
    monthly_amortization: document.getElementById('housingAmortization').value || null,
    remarks: document.getElementById('housingRemarks').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.unit_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/housing-units.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Housing unit saved successfully.');
      closeModal('housingModal');
      await fetchHousingUnits(housingPagination.page);
      await fetchHousingStats();
    } else {
      showToast(result.message || 'Failed to save housing unit.');
    }
  } catch (err) {
    console.error('Error saving housing unit:', err);
    showToast('Failed to save housing unit.');
  }
}

async function handleToggleHousingStatus(unitId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/housing-units.php?id=${unitId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchHousingUnits(housingPagination.page);
      await fetchHousingStats();
    } else {
      showToast(result.message || 'Failed to update unit status.');
    }
  } catch (err) {
    console.error('Error updating housing unit status:', err);
    showToast('Failed to update unit status.');
  }
}

async function fetchHousingUnitDetail(unitId) {
  try {
    const response = await fetch(`../../api/employee/housing-units.php?id=${unitId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching housing unit detail:', err);
    return null;
  }
}
