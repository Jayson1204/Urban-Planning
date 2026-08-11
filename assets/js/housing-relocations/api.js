// Global State
let relocationsData = [];
let relocationsPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedRelResidentId = null;
let selectedRelToUnitId = null;

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

async function fetchRelocations(page = 1) {
  const search = document.getElementById('relocationSearchInput').value.trim();
  const reason = document.getElementById('relocationReasonFilter').value;
  const status = document.getElementById('relocationStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: relocationsPagination.per_page });
  if (search) params.set('search', search);
  if (reason) params.set('reason', reason);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/housing-relocations.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      relocationsData = result.data || [];
      relocationsPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderRelocations();
      renderRelocationsPagination();
    } else {
      showToast(result.message || 'Error loading relocation records.');
    }
  } catch (err) {
    console.error('Error fetching relocation records:', err);
    showToast('Network error while loading relocation records.');
  }
}

async function fetchRelocationStats() {
  try {
    const response = await fetch('../../api/employee/housing-relocations.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalRelocations').innerText = s.total || 0;
      document.getElementById('statActiveRelocations').innerText = s.active || 0;
      document.getElementById('statTodayRelocations').innerText = s.today || 0;
      document.getElementById('statArchivedRelocations').innerText = s.archived || 0;
    }
  } catch (err) {
    console.error('Error fetching relocation stats:', err);
  }
}

async function handleSaveRelocation(e) {
  e.preventDefault();

  const payload = {
    resident_id: selectedRelResidentId ? parseInt(selectedRelResidentId) : null,
    from_unit_id: window.relCurrentUnitId ? parseInt(window.relCurrentUnitId) : null,
    to_unit_id: selectedRelToUnitId ? parseInt(selectedRelToUnitId) : null,
    relocation_date: document.getElementById('relDate').value,
    reason: document.getElementById('relReason').value,
    remarks: document.getElementById('relRemarks').value.trim(),
  };

  try {
    const response = await fetch('../../api/employee/housing-relocations.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Relocation recorded successfully.');
      closeModal('relocationModal');
      await fetchRelocations(relocationsPagination.page);
      await fetchRelocationStats();
    } else {
      showToast(result.message || 'Failed to record relocation.');
    }
  } catch (err) {
    console.error('Error saving relocation record:', err);
    showToast('Failed to record relocation.');
  }
}

async function handleToggleRelocationStatus(relocationId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/housing-relocations.php?id=${relocationId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchRelocations(relocationsPagination.page);
      await fetchRelocationStats();
    } else {
      showToast(result.message || 'Failed to update relocation record.');
    }
  } catch (err) {
    console.error('Error updating relocation record:', err);
    showToast('Failed to update relocation record.');
  }
}

async function fetchRelocationDetail(relocationId) {
  try {
    const response = await fetch(`../../api/employee/housing-relocations.php?id=${relocationId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching relocation detail:', err);
    return null;
  }
}

// Pickers reuse existing module endpoints (no new endpoints needed)
async function searchResidentsForRelPicker(term) {
  try {
    const response = await fetch(`../../api/employee/residents.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching residents:', err);
    return [];
  }
}

async function searchUnitsForRelPicker(term) {
  try {
    const response = await fetch(`../../api/employee/housing-units.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching units:', err);
    return [];
  }
}

async function fetchActiveOccupancyForResident(residentId) {
  try {
    const response = await fetch(`../../api/employee/housing-occupancy.php?action=active_for_resident&resident_id=${residentId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error resolving resident current unit:', err);
    return null;
  }
}
