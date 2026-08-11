// Global State
let householdsData = [];
let householdsPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };

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

async function fetchHouseholds(page = 1) {
  const search = document.getElementById('householdSearchInput').value.trim();
  const householdType = document.getElementById('householdTypeFilter').value;
  const status = document.getElementById('householdStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: householdsPagination.per_page });
  if (search) params.set('list_search', search);
  if (householdType) params.set('household_type', householdType);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/households.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      householdsData = result.data || [];
      householdsPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderHouseholds();
      renderHouseholdsPagination();
    } else {
      showToast(result.message || 'Error loading households.');
    }
  } catch (err) {
    console.error('Error fetching households:', err);
    showToast('Network error while loading households.');
  }
}

async function fetchHouseholdStats() {
  try {
    const response = await fetch('../../api/employee/households.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalHouseholds').innerText = s.total || 0;
      document.getElementById('statActiveHouseholds').innerText = s.active || 0;
      document.getElementById('statArchivedHouseholds').innerText = s.archived || 0;
      document.getElementById('statResidentsCovered').innerText = s.residents_covered || 0;
    }
  } catch (err) {
    console.error('Error fetching household stats:', err);
  }
}

async function handleSaveHousehold(e) {
  e.preventDefault();

  const idRef = document.getElementById('householdIdRef').value;
  const payload = {
    household_number: document.getElementById('householdNumber').value.trim(),
    barangay: document.getElementById('householdBarangay').value.trim(),
    street_address: document.getElementById('householdStreetAddress').value.trim(),
    household_type: document.getElementById('householdType').value,
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.household_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/households.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Household saved successfully.');
      closeModal('householdModal');
      await fetchHouseholds(householdsPagination.page);
      await fetchHouseholdStats();
    } else {
      showToast(result.message || 'Failed to save household.');
    }
  } catch (err) {
    console.error('Error saving household:', err);
    showToast('Failed to save household.');
  }
}

async function handleToggleHouseholdStatus(householdId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/households.php?id=${householdId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchHouseholds(householdsPagination.page);
      await fetchHouseholdStats();
    } else {
      showToast(result.message || 'Failed to update household status.');
    }
  } catch (err) {
    console.error('Error updating household status:', err);
    showToast('Failed to update household status.');
  }
}

async function fetchHouseholdDetail(householdId) {
  try {
    const response = await fetch(`../../api/employee/households.php?id=${householdId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching household detail:', err);
    return null;
  }
}
