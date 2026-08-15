// Global State
let clearancesData = [];
let clearancesPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedZcResidentId = null;

// Toast Popup (shared markup/IDs with other bridges on this page)
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

async function fetchClearances(page = 1) {
  const search = document.getElementById('zcSearchInput').value.trim();
  const status = document.getElementById('zcStatusFilter').value;
  const conformity = document.getElementById('zcConformityFilter').value;
  const recordStatus = document.getElementById('zcRecordStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: clearancesPagination.per_page });
  if (search) params.set('search', search);
  if (status) params.set('clearance_status', status);
  if (conformity) params.set('conformity_result', conformity);
  if (recordStatus) params.set('status', recordStatus);

  try {
    const response = await fetch(`../../api/employee/zoning-clearances.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      clearancesData = result.data || [];
      clearancesPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderClearances();
      renderZcPagination();
    } else {
      showToast(result.message || 'Error loading zoning clearances.');
    }
  } catch (err) {
    console.error('Error fetching zoning clearances:', err);
    showToast('Network error while loading zoning clearances.');
  }
}

async function fetchClearanceStats() {
  try {
    const response = await fetch('../../api/employee/zoning-clearances.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalZc').innerText = s.total || 0;
      document.getElementById('statPendingZc').innerText = s.pending || 0;
      document.getElementById('statApprovedZc').innerText = s.approved || 0;
      document.getElementById('statDeniedZc').innerText = s.denied || 0;
    }
  } catch (err) {
    console.error('Error fetching zoning clearance stats:', err);
  }
}

async function fetchClearanceDetail(clearanceId) {
  try {
    const response = await fetch(`../../api/employee/zoning-clearances.php?id=${clearanceId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching zoning clearance detail:', err);
    return null;
  }
}

async function handleSaveClearance(e) {
  e.preventDefault();

  const idRef = document.getElementById('zcIdRef').value;
  const payload = {
    resident_id: selectedZcResidentId ? parseInt(selectedZcResidentId) : null,
    zone_classification: document.getElementById('zcZoneClassification').value,
    use_category: document.getElementById('zcUseCategory').value,
    project_description: document.getElementById('zcProjectDescription').value.trim(),
    barangay: document.getElementById('zcBarangay').value.trim(),
    street_address: document.getElementById('zcStreetAddress').value.trim(),
    lot_area_sqm: document.getElementById('zcLotArea').value || null,
    proposed_height_m: document.getElementById('zcHeight').value || null,
    proposed_setback_m: document.getElementById('zcSetback').value || null,
    proposed_far: document.getElementById('zcFar').value || null,
    proposed_lot_occupancy_pct: document.getElementById('zcLotOccupancy').value || null,
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.clearance_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/zoning-clearances.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Zoning clearance saved successfully.');
      closeModal('zcModal');
      await fetchClearances(clearancesPagination.page);
      await fetchClearanceStats();
    } else {
      showToast(result.message || 'Failed to save zoning clearance.');
    }
  } catch (err) {
    console.error('Error saving zoning clearance:', err);
    showToast('Failed to save zoning clearance.');
  }
}

async function handleTransitionClearance(e) {
  e.preventDefault();

  const payload = {
    clearance_id: parseInt(document.getElementById('zcTransitionId').value),
    clearance_status: document.getElementById('zcTransitionStatus').value,
    reviewer_role: document.getElementById('zcTransitionRole').value.trim(),
    remarks: document.getElementById('zcTransitionRemarks').value.trim(),
  };

  try {
    const response = await fetch('../../api/employee/zoning-clearances.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Clearance status updated.');
      await openViewClearanceModal(payload.clearance_id);
      await fetchClearances(clearancesPagination.page);
      await fetchClearanceStats();
    } else {
      showToast(result.message || 'Failed to update clearance status.');
    }
  } catch (err) {
    console.error('Error updating clearance status:', err);
    showToast('Failed to update clearance status.');
  }
}

async function handleToggleClearanceStatus(clearanceId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/zoning-clearances.php?id=${clearanceId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchClearances(clearancesPagination.page);
      await fetchClearanceStats();
    } else {
      showToast(result.message || 'Failed to update zoning clearance status.');
    }
  } catch (err) {
    console.error('Error updating zoning clearance status:', err);
    showToast('Failed to update zoning clearance status.');
  }
}

async function fetchConformityPreview(params) {
  try {
    const query = new URLSearchParams({ action: 'preview', ...params });
    const response = await fetch(`../../api/employee/zoning-clearances.php?${query.toString()}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching conformity preview:', err);
    return null;
  }
}

// Resident picker reuses the residents endpoint (no new endpoint needed).
async function searchResidentsForPicker(term) {
  try {
    const response = await fetch(`../../api/employee/residents.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching residents:', err);
    return [];
  }
}
