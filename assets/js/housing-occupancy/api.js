// Global State
let occupancyData = [];
let occupancyPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedOccResidentId = null;
let selectedOccUnitId = null;

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

async function fetchOccupancy(page = 1) {
  const search = document.getElementById('occupancySearchInput').value.trim();
  const status = document.getElementById('occupancyStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: occupancyPagination.per_page });
  if (search) params.set('search', search);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/housing-occupancy.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      occupancyData = result.data || [];
      occupancyPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderOccupancy();
      renderOccupancyPagination();
    } else {
      showToast(result.message || 'Error loading occupancy records.');
    }
  } catch (err) {
    console.error('Error fetching occupancy records:', err);
    showToast('Network error while loading occupancy records.');
  }
}

async function fetchOccupancyStats() {
  try {
    const response = await fetch('../../api/employee/housing-occupancy.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalOccupancy').innerText = s.total || 0;
      document.getElementById('statActiveOccupancy').innerText = s.active || 0;
      document.getElementById('statEndedOccupancy').innerText = s.ended || 0;
      document.getElementById('statUnitsOccupied').innerText = s.units_occupied || 0;
    }
  } catch (err) {
    console.error('Error fetching occupancy stats:', err);
  }
}

let isSavingOccupancy = false;

async function handleSaveOccupancy(e) {
  e.preventDefault();

  if (isSavingOccupancy) return;
  isSavingOccupancy = true;
  const saveBtn = document.getElementById('occupancySaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  const payload = {
    resident_id: selectedOccResidentId ? parseInt(selectedOccResidentId) : null,
    unit_id: selectedOccUnitId ? parseInt(selectedOccUnitId) : null,
    move_in_date: document.getElementById('occMoveInDate').value,
    remarks: document.getElementById('occRemarks').value.trim(),
  };

  try {
    const response = await fetch('../../api/employee/housing-occupancy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Occupancy recorded successfully.');
      closeModal('occupancyModal');
      await fetchOccupancy(occupancyPagination.page);
      await fetchOccupancyStats();
    } else {
      showToast(result.message || 'Failed to record occupancy.');
    }
  } catch (err) {
    console.error('Error saving occupancy record:', err);
    showToast('Failed to record occupancy.');
  } finally {
    isSavingOccupancy = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleVacateOccupancy(occupancyId) {
  try {
    const response = await fetch(`../../api/employee/housing-occupancy.php?id=${occupancyId}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchOccupancy(occupancyPagination.page);
      await fetchOccupancyStats();
    } else {
      showToast(result.message || 'Failed to record move-out.');
    }
  } catch (err) {
    console.error('Error vacating occupancy record:', err);
    showToast('Failed to record move-out.');
  }
}

// Pickers reuse existing module endpoints (no new endpoints needed)
async function searchResidentsForOccPicker(term) {
  try {
    const response = await fetch(`../../api/employee/residents.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching residents:', err);
    return [];
  }
}

async function searchUnitsForOccPicker(term) {
  try {
    const response = await fetch(`../../api/employee/housing-units.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching units:', err);
    return [];
  }
}
