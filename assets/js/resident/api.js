// Global State
let residentsData = [];
let residentsPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedHouseholdId = null;

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

// Fetch paginated/filtered residents from the server
async function fetchResidents(page = 1) {
  const search = document.getElementById('residentSearchInput').value.trim();
  const barangay = document.getElementById('residentBarangayFilter').value.trim();
  const status = document.getElementById('residentStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: residentsPagination.per_page });
  if (search) params.set('search', search);
  if (barangay) params.set('barangay', barangay);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/residents.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      residentsData = result.data || [];
      residentsPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderResidents();
      renderPagination();
    } else {
      showToast(result.message || 'Error loading resident data.');
    }
  } catch (err) {
    console.error('Error fetching residents:', err);
    showToast('Network error while loading residents.');
  }
}

async function fetchResidentStats() {
  try {
    const response = await fetch('../../api/employee/residents.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalResidents').innerText = s.total || 0;
      document.getElementById('statActiveResidents').innerText = s.active || 0;
      document.getElementById('statArchivedResidents').innerText = s.archived || 0;
      document.getElementById('statHouseholdsCovered').innerText = s.households_covered || 0;
    }
  } catch (err) {
    console.error('Error fetching resident stats:', err);
  }
}

let isSavingResident = false;

async function handleSaveResident(e) {
  e.preventDefault();

  if (isSavingResident) return;
  isSavingResident = true;
  const saveBtn = document.getElementById('residentSaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  const idRef = document.getElementById('residentIdRef').value;
  const payload = {
    first_name: document.getElementById('residentFirstName').value.trim(),
    middle_name: document.getElementById('residentMiddleName').value.trim(),
    last_name: document.getElementById('residentLastName').value.trim(),
    suffix: document.getElementById('residentSuffix').value.trim(),
    birth_date: document.getElementById('residentBirthDate').value || null,
    gender: document.getElementById('residentGender').value,
    civil_status: document.getElementById('residentCivilStatus').value,
    contact_number: document.getElementById('residentContactNumber').value.trim(),
    email: document.getElementById('residentEmail').value.trim(),
    occupation: document.getElementById('residentOccupation').value.trim(),
    barangay: document.getElementById('residentBarangay').value.trim(),
    street_address: document.getElementById('residentStreetAddress').value.trim(),
    household_id: selectedHouseholdId,
    relationship_to_head: selectedHouseholdId ? document.getElementById('residentRelationship').value : null,
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.resident_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/residents.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Resident saved successfully.');
      closeModal('residentModal');
      await fetchResidents(residentsPagination.page);
      await fetchResidentStats();
    } else {
      showToast(result.message || 'Failed to save resident.');
    }
  } catch (err) {
    console.error('Error saving resident:', err);
    showToast('Failed to save resident.');
  } finally {
    isSavingResident = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleToggleResidentStatus(residentId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/residents.php?id=${residentId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchResidents(residentsPagination.page);
      await fetchResidentStats();
    } else {
      showToast(result.message || 'Failed to update resident status.');
    }
  } catch (err) {
    console.error('Error updating resident status:', err);
    showToast('Failed to update resident status.');
  }
}

async function fetchResidentDetail(residentId) {
  try {
    const response = await fetch(`../../api/employee/residents.php?id=${residentId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching resident detail:', err);
    return null;
  }
}

async function searchHouseholds(term) {
  try {
    const response = await fetch(`../../api/employee/households.php?search=${encodeURIComponent(term)}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : [];
  } catch (err) {
    console.error('Error searching households:', err);
    return [];
  }
}

async function handleUploadDocument(e) {
  e.preventDefault();

  const residentId = document.getElementById('uploadResidentId').value;
  const documentType = document.getElementById('documentType').value;
  const fileInput = document.getElementById('documentFile');

  if (!fileInput.files.length) return;

  const formData = new FormData();
  formData.append('resident_id', residentId);
  formData.append('document_type', documentType);
  formData.append('file', fileInput.files[0]);

  try {
    const response = await fetch('../../api/employee/resident-documents.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document uploaded.');
      document.getElementById('documentUploadForm').reset();
      document.getElementById('documentUploadForm').classList.add('hidden');
      await openViewResidentModal(residentId);
    } else {
      showToast(result.message || 'Failed to upload document.');
    }
  } catch (err) {
    console.error('Error uploading document:', err);
    showToast('Failed to upload document.');
  }
}

async function handleDeleteDocument(documentId, residentId) {
  try {
    const response = await fetch(`../../api/employee/resident-documents.php?id=${documentId}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document deleted.');
      await openViewResidentModal(residentId);
    } else {
      showToast(result.message || 'Failed to delete document.');
    }
  } catch (err) {
    console.error('Error deleting document:', err);
    showToast('Failed to delete document.');
  }
}
