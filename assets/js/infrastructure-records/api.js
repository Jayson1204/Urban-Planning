// Global State
let infraData = [];
let infraPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedInfraProjectId = null;

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

async function fetchInfraRecords(page = 1) {
  const search = document.getElementById('infraSearchInput').value.trim();
  const type = document.getElementById('infraTypeFilter').value;
  const condition = document.getElementById('infraConditionFilter').value;
  const rowStatus = document.getElementById('infraRowStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: infraPagination.per_page });
  if (search) params.set('search', search);
  if (type) params.set('infrastructure_type', type);
  if (condition) params.set('condition_status', condition);
  if (rowStatus) params.set('status', rowStatus);

  try {
    const response = await fetch(`../../api/employee/infrastructure-records.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      infraData = result.data || [];
      infraPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderInfraRecords();
      renderInfraPagination();
    } else {
      showToast(result.message || 'Error loading infrastructure records.');
    }
  } catch (err) {
    console.error('Error fetching infrastructure records:', err);
    showToast('Network error while loading infrastructure records.');
  }
}

async function fetchInfraStats() {
  try {
    const response = await fetch('../../api/employee/infrastructure-records.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalInfra').innerText = s.total || 0;
      document.getElementById('statGoodInfra').innerText = s.good || 0;
      document.getElementById('statNeedsRepairInfra').innerText = s.needs_repair || 0;
      document.getElementById('statUnderConstructionInfra').innerText = s.under_construction || 0;
    }
  } catch (err) {
    console.error('Error fetching infrastructure stats:', err);
  }
}

async function handleSaveInfra(e) {
  e.preventDefault();

  const idRef = document.getElementById('infraIdRef').value;
  const payload = {
    project_id: selectedInfraProjectId ? parseInt(selectedInfraProjectId) : null,
    infrastructure_name: document.getElementById('infraName').value.trim(),
    infrastructure_type: document.getElementById('infraType').value,
    condition_status: document.getElementById('infraCondition').value,
    barangay: document.getElementById('infraBarangay').value.trim(),
    location_details: document.getElementById('infraLocationDetails').value.trim(),
    completion_date: document.getElementById('infraCompletionDate').value || null,
    remarks: document.getElementById('infraRemarks').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.record_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/infrastructure-records.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Infrastructure record saved successfully.');
      closeModal('infraModal');
      await fetchInfraRecords(infraPagination.page);
      await fetchInfraStats();
    } else {
      showToast(result.message || 'Failed to save infrastructure record.');
    }
  } catch (err) {
    console.error('Error saving infrastructure record:', err);
    showToast('Failed to save infrastructure record.');
  }
}

async function handleToggleInfraStatus(recordId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/infrastructure-records.php?id=${recordId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchInfraRecords(infraPagination.page);
      await fetchInfraStats();
    } else {
      showToast(result.message || 'Failed to update record status.');
    }
  } catch (err) {
    console.error('Error updating infrastructure record status:', err);
    showToast('Failed to update record status.');
  }
}

async function fetchInfraDetail(recordId) {
  try {
    const response = await fetch(`../../api/employee/infrastructure-records.php?id=${recordId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching infrastructure record detail:', err);
    return null;
  }
}

// Picker reuses the existing urban-projects endpoint (no new endpoint needed)
async function searchProjectsForPicker(term) {
  try {
    const response = await fetch(`../../api/employee/urban-projects.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching urban projects:', err);
    return [];
  }
}
