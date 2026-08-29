let subdivisionData = [];
let subdivisionPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let subdivisionBarangayOptions = [];

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

async function loadSubdivisionBarangayOptions() {
  try {
    const response = await fetch('../../api/employee/barangays.php');
    const result = await response.json();
    subdivisionBarangayOptions = result.status === 'success' ? result.data : [];
    const select = document.getElementById('subdivisionBarangayId');
    if (select) {
      select.innerHTML = '<option value="">Select barangay...</option>' +
        subdivisionBarangayOptions.map(b => `<option value="${b.barangay_id}">${escapeHtml(b.name)}</option>`).join('');
    }
  } catch (err) {
    console.error('Error loading barangay list:', err);
  }
}

async function fetchSubdivisionsList(page = 1) {
  const search = document.getElementById('subdivisionSearchInput').value.trim();
  const status = document.getElementById('subdivisionStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: subdivisionPagination.per_page });
  if (search) params.set('search', search);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/subdivisions.php?${params.toString()}`);
    const result = await response.json();
    if (result.status === 'success') {
      subdivisionData = result.data || [];
      subdivisionPagination = { page: result.page, per_page: result.per_page, total: result.total, total_pages: result.total_pages };
      renderSubdivisions();
      renderSubdivisionPagination();
    } else {
      showToast(result.message || 'Error loading subdivisions.');
    }
  } catch (err) {
    console.error('Error fetching subdivisions:', err);
    showToast('Network error while loading subdivisions.');
  }
}

async function fetchSubdivisionDetail(subdivisionId) {
  try {
    const response = await fetch(`../../api/employee/subdivisions.php?id=${subdivisionId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching subdivision detail:', err);
    return null;
  }
}

let isSavingSubdivision = false;

async function handleSaveSubdivision(e) {
  e.preventDefault();

  if (isSavingSubdivision) return;
  isSavingSubdivision = true;
  const saveBtn = document.getElementById('subdivisionSaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  try {
    const idRef = document.getElementById('subdivisionIdRef').value;
    const boundaryText = document.getElementById('subdivisionBoundary').value.trim();

    if (boundaryText) {
      try {
        JSON.parse(boundaryText);
      } catch (err) {
        showToast('Boundary GeoJSON is not valid JSON.');
        return;
      }
    }

    const payload = {
      name: document.getElementById('subdivisionName').value.trim(),
      barangay_id: document.getElementById('subdivisionBarangayId').value || null,
      barangay: document.getElementById('subdivisionBarangayId').selectedOptions[0]?.text || null,
      subdivision_type: document.getElementById('subdivisionType').value.trim(),
      subdivision_status: document.getElementById('subdivisionStatusText').value.trim(),
      source: document.getElementById('subdivisionSource').value.trim(),
      latitude: document.getElementById('subdivisionLatitude').value,
      longitude: document.getElementById('subdivisionLongitude').value,
      boundary_geojson: boundaryText || null,
      description: document.getElementById('subdivisionDescription').value.trim(),
    };

    const isEdit = idRef !== '';
    if (isEdit) payload.subdivision_id = parseInt(idRef);

    const response = await fetch('../../api/employee/subdivisions.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Subdivision saved successfully.');
      closeModal('subdivisionModal');
      await fetchSubdivisionsList(subdivisionPagination.page);
    } else {
      showToast(result.message || 'Failed to save subdivision.');
    }
  } catch (err) {
    console.error('Error saving subdivision:', err);
    showToast('Failed to save subdivision.');
  } finally {
    isSavingSubdivision = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleToggleSubdivisionStatus(subdivisionId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/subdivisions.php?id=${subdivisionId}&status=${newStatus}`, { method: 'DELETE' });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchSubdivisionsList(subdivisionPagination.page);
    } else {
      showToast(result.message || 'Failed to update subdivision status.');
    }
  } catch (err) {
    console.error('Error updating subdivision status:', err);
    showToast('Failed to update subdivision status.');
  }
}
