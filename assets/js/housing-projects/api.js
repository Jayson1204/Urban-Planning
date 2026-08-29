let hpData = [];
let hpPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };

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

async function loadHpBarangayOptions() {
  try {
    const response = await fetch('../../api/employee/barangays.php');
    const result = await response.json();
    const options = result.status === 'success' ? result.data : [];
    const select = document.getElementById('hpBarangayId');
    if (select) {
      select.innerHTML = '<option value="">Select barangay...</option>' +
        options.map(b => `<option value="${b.barangay_id}">${escapeHtml(b.name)}</option>`).join('');
    }
  } catch (err) {
    console.error('Error loading barangay list:', err);
  }
}

async function fetchHousingProjectsList(page = 1) {
  const search = document.getElementById('hpSearchInput').value.trim();
  const projectStatus = document.getElementById('hpProjectStatusFilter').value;
  const status = document.getElementById('hpStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: hpPagination.per_page });
  if (search) params.set('search', search);
  if (projectStatus) params.set('project_status', projectStatus);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/housing-projects.php?${params.toString()}`);
    const result = await response.json();
    if (result.status === 'success') {
      hpData = result.data || [];
      hpPagination = { page: result.page, per_page: result.per_page, total: result.total, total_pages: result.total_pages };
      renderHousingProjects();
      renderHpPagination();
    } else {
      showToast(result.message || 'Error loading housing projects.');
    }
  } catch (err) {
    console.error('Error fetching housing projects:', err);
    showToast('Network error while loading housing projects.');
  }
}

async function fetchHousingProjectDetail(id) {
  try {
    const response = await fetch(`../../api/employee/housing-projects.php?id=${id}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching housing project detail:', err);
    return null;
  }
}

let isSavingHp = false;

async function handleSaveHousingProject(e) {
  e.preventDefault();

  if (isSavingHp) return;
  isSavingHp = true;
  const saveBtn = document.getElementById('hpSaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  try {
    const idRef = document.getElementById('hpIdRef').value;
    const boundaryText = document.getElementById('hpBoundary').value.trim();

    if (boundaryText) {
      try {
        JSON.parse(boundaryText);
      } catch (err) {
        showToast('Boundary GeoJSON is not valid JSON.');
        return;
      }
    }

    const payload = {
      name: document.getElementById('hpName').value.trim(),
      barangay_id: document.getElementById('hpBarangayId').value || null,
      barangay: document.getElementById('hpBarangayId').selectedOptions[0]?.text || null,
      developer: document.getElementById('hpDeveloper').value.trim(),
      units: document.getElementById('hpUnits').value || null,
      project_status: document.getElementById('hpProjectStatus').value || null,
      source: document.getElementById('hpSource').value.trim(),
      latitude: document.getElementById('hpLatitude').value,
      longitude: document.getElementById('hpLongitude').value,
      boundary_geojson: boundaryText || null,
      description: document.getElementById('hpDescription').value.trim(),
    };

    const isEdit = idRef !== '';
    if (isEdit) payload.housing_project_id = parseInt(idRef);

    const response = await fetch('../../api/employee/housing-projects.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Housing project saved successfully.');
      closeModal('hpModal');
      await fetchHousingProjectsList(hpPagination.page);
    } else {
      showToast(result.message || 'Failed to save housing project.');
    }
  } catch (err) {
    console.error('Error saving housing project:', err);
    showToast('Failed to save housing project.');
  } finally {
    isSavingHp = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleToggleHpStatus(id, newStatus) {
  try {
    const response = await fetch(`../../api/employee/housing-projects.php?id=${id}&status=${newStatus}`, { method: 'DELETE' });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchHousingProjectsList(hpPagination.page);
    } else {
      showToast(result.message || 'Failed to update project status.');
    }
  } catch (err) {
    console.error('Error updating housing project status:', err);
    showToast('Failed to update project status.');
  }
}
