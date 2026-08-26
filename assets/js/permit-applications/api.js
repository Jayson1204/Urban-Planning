// Global State
let applicationsData = [];
let applicationsPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedPaResidentId = null;

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

async function fetchApplications(page = 1) {
  const search = document.getElementById('paSearchInput').value.trim();
  const type = document.getElementById('paTypeFilter').value;
  const status = document.getElementById('paStatusFilter').value;
  const recordStatus = document.getElementById('paRecordStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: applicationsPagination.per_page });
  if (search) params.set('search', search);
  if (type) params.set('application_type', type);
  if (status) params.set('application_status', status);
  if (recordStatus) params.set('status', recordStatus);

  try {
    const response = await fetch(`../../api/employee/permit-applications.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      applicationsData = result.data || [];
      applicationsPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderApplications();
      renderPaPagination();
    } else {
      showToast(result.message || 'Error loading permit applications.');
    }
  } catch (err) {
    console.error('Error fetching permit applications:', err);
    showToast('Network error while loading permit applications.');
  }
}

async function fetchApplicationStats() {
  try {
    const response = await fetch('../../api/employee/permit-applications.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalPa').innerText = s.total || 0;
      document.getElementById('statPendingPa').innerText = s.pending || 0;
      document.getElementById('statIssuedPa').innerText = s.issued || 0;
      document.getElementById('statDeniedPa').innerText = s.denied || 0;
    }
  } catch (err) {
    console.error('Error fetching permit application stats:', err);
  }
}

async function fetchApplicationDetail(applicationId) {
  try {
    const response = await fetch(`../../api/employee/permit-applications.php?id=${applicationId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching permit application detail:', err);
    return null;
  }
}

async function handleSaveApplication(e) {
  e.preventDefault();

  const idRef = document.getElementById('paIdRef').value;
  const isEdit = idRef !== '';

  const payload = {
    resident_id: selectedPaResidentId ? parseInt(selectedPaResidentId) : null,
    project_name: document.getElementById('paProjectName').value.trim(),
    project_description: document.getElementById('paProjectDescription').value.trim(),
    barangay: document.getElementById('paBarangay').value.trim(),
    street_address: document.getElementById('paStreetAddress').value.trim(),
    lot_area_sqm: document.getElementById('paLotArea').value || null,
    floor_area_sqm: document.getElementById('paFloorArea').value || null,
    number_of_storeys: document.getElementById('paStoreys').value || null,
    number_of_lots: document.getElementById('paLots').value || null,
    estimated_project_cost: document.getElementById('paProjectCost').value || null,
  };

  if (!isEdit) {
    payload.application_type = document.getElementById('paApplicationType').value;
  } else {
    payload.application_id = parseInt(idRef);
  }

  try {
    const response = await fetch('../../api/employee/permit-applications.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Application saved successfully.');
      closeModal('paModal');
      await fetchApplications(applicationsPagination.page);
      await fetchApplicationStats();
    } else {
      showToast(result.message || 'Failed to save application.');
    }
  } catch (err) {
    console.error('Error saving permit application:', err);
    showToast('Failed to save application.');
  }
}

async function handleTransitionApplication(e) {
  e.preventDefault();

  const payload = {
    application_id: parseInt(document.getElementById('paTransitionId').value),
    application_status: document.getElementById('paTransitionStatus').value,
    reviewer_role: document.getElementById('paTransitionRole').value.trim(),
    remarks: document.getElementById('paTransitionRemarks').value.trim(),
  };

  try {
    const response = await fetch('../../api/employee/permit-applications.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Application status updated.');
      await openViewApplicationModal(payload.application_id);
      await fetchApplications(applicationsPagination.page);
      await fetchApplicationStats();
    } else {
      showToast(result.message || 'Failed to update application status.');
    }
  } catch (err) {
    console.error('Error updating application status:', err);
    showToast('Failed to update application status.');
  }
}

async function handleTransitionDiscipline(applicationId, discipline, disciplineStatus, remarks) {
  try {
    const response = await fetch('../../api/employee/permit-applications.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ application_id: applicationId, discipline, discipline_status: disciplineStatus, remarks })
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Discipline review updated.');
      await openViewApplicationModal(applicationId);
      await fetchApplications(applicationsPagination.page);
      await fetchApplicationStats();
    } else {
      showToast(result.message || 'Failed to update discipline review.');
    }
  } catch (err) {
    console.error('Error updating discipline review:', err);
    showToast('Failed to update discipline review.');
  }
}

async function handleResubmitApplication(e) {
  e.preventDefault();
  const applicationId = parseInt(document.getElementById('paResubmitId').value);
  const remarks = document.getElementById('paResubmitRemarks').value.trim();

  try {
    const response = await fetch('../../api/employee/permit-applications.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ application_id: applicationId, action: 'resubmit', remarks })
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Application resubmitted.');
      await openViewApplicationModal(applicationId);
      await fetchApplications(applicationsPagination.page);
      await fetchApplicationStats();
    } else {
      showToast(result.message || 'Failed to resubmit application.');
    }
  } catch (err) {
    console.error('Error resubmitting application:', err);
    showToast('Failed to resubmit application.');
  }
}

async function handleIssuePermit(e) {
  e.preventDefault();
  const applicationId = parseInt(document.getElementById('paIssueId').value);
  const conditionsOfApproval = document.getElementById('paConditionsOfApproval').value.trim();
  const expiryDate = document.getElementById('paExpiryDate').value;

  try {
    const response = await fetch('../../api/employee/permit-applications.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ application_id: applicationId, action: 'issue_permit', conditions_of_approval: conditionsOfApproval, expiry_date: expiryDate })
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Permit issued.');
      await openViewApplicationModal(applicationId);
      await fetchApplications(applicationsPagination.page);
      await fetchApplicationStats();
    } else {
      showToast(result.message || 'Failed to issue permit.');
    }
  } catch (err) {
    console.error('Error issuing permit:', err);
    showToast('Failed to issue permit.');
  }
}

async function handleToggleApplicationStatus(applicationId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/permit-applications.php?id=${applicationId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchApplications(applicationsPagination.page);
      await fetchApplicationStats();
    } else {
      showToast(result.message || 'Failed to update application record status.');
    }
  } catch (err) {
    console.error('Error updating application record status:', err);
    showToast('Failed to update application record status.');
  }
}

// Resident picker reuses the residents endpoint (no new endpoint needed).
async function searchResidentsForPaPicker(term) {
  try {
    const response = await fetch(`../../api/employee/residents.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching residents:', err);
    return [];
  }
}

async function handleUploadPaDocument(e) {
  e.preventDefault();

  const applicationId = document.getElementById('uploadPaApplicationId').value;
  const documentType = document.getElementById('paDocumentType').value;
  const fileInput = document.getElementById('paDocumentFile');

  if (!fileInput.files.length) return;

  const formData = new FormData();
  formData.append('application_id', applicationId);
  formData.append('document_type', documentType);
  formData.append('file', fileInput.files[0]);

  try {
    const response = await fetch('../../api/employee/permit-plan-documents.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document uploaded.');
      document.getElementById('paDocumentUploadForm').reset();
      document.getElementById('paDocumentUploadForm').classList.add('hidden');
      await openViewApplicationModal(applicationId);
    } else {
      showToast(result.message || 'Failed to upload document.');
    }
  } catch (err) {
    console.error('Error uploading plan document:', err);
    showToast('Failed to upload document.');
  }
}

async function handleDeletePaDocument(documentId, applicationId) {
  try {
    const response = await fetch(`../../api/employee/permit-plan-documents.php?id=${documentId}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document deleted.');
      await openViewApplicationModal(applicationId);
    } else {
      showToast(result.message || 'Failed to delete document.');
    }
  } catch (err) {
    console.error('Error deleting plan document:', err);
    showToast('Failed to delete document.');
  }
}
