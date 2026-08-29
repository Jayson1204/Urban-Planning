// Global State
let planData = [];
let planPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };

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

// Fetch paginated/filtered development plans from the server
async function fetchDevelopmentPlans(page = 1) {
  const search = document.getElementById('planSearchInput').value.trim();
  const barangay = document.getElementById('planBarangayFilter').value.trim();
  const planType = document.getElementById('planTypeFilter').value;
  const planStatus = document.getElementById('planStatusFilter').value;
  const rowStatus = document.getElementById('rowStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: planPagination.per_page });
  if (search) params.set('search', search);
  if (barangay) params.set('barangay', barangay);
  if (planType) params.set('plan_type', planType);
  if (planStatus) params.set('plan_status', planStatus);
  if (rowStatus) params.set('status', rowStatus);

  try {
    const response = await fetch(`../../api/employee/development-plans.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      planData = result.data || [];
      planPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderDevelopmentPlans();
      renderPlanPagination();
    } else {
      showToast(result.message || 'Error loading development plans.');
    }
  } catch (err) {
    console.error('Error fetching development plans:', err);
    showToast('Network error while loading development plans.');
  }
}

async function fetchPlanStats() {
  try {
    const response = await fetch('../../api/employee/development-plans.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalPlans').innerText = s.total || 0;
      document.getElementById('statDraftPlans').innerText = s.draft || 0;
      document.getElementById('statActivePlans').innerText = s.active || 0;
      document.getElementById('statCompletedPlans').innerText = s.completed || 0;
    }
  } catch (err) {
    console.error('Error fetching development plan stats:', err);
  }
}

let isSavingPlan = false;

async function handleSaveDevelopmentPlan(e) {
  e.preventDefault();

  if (isSavingPlan) return;
  isSavingPlan = true;
  const saveBtn = document.getElementById('planSaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  const idRef = document.getElementById('planIdRef').value;
  const payload = {
    plan_code: document.getElementById('planCode').value.trim(),
    plan_title: document.getElementById('planTitle').value.trim(),
    plan_type: document.getElementById('planType').value,
    plan_status: document.getElementById('planStatus').value,
    barangay: document.getElementById('planBarangay').value.trim(),
    coverage_area: document.getElementById('planCoverageArea').value.trim(),
    lead_department: document.getElementById('planLeadDepartment').value.trim(),
    start_date: document.getElementById('planStartDate').value || null,
    end_date: document.getElementById('planEndDate').value || null,
    budget_allocation: document.getElementById('planBudget').value || null,
    description: document.getElementById('planDescription').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.plan_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/development-plans.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Development plan saved successfully.');
      closeModal('planModal');
      await fetchDevelopmentPlans(planPagination.page);
      await fetchPlanStats();
    } else {
      showToast(result.message || 'Failed to save development plan.');
    }
  } catch (err) {
    console.error('Error saving development plan:', err);
    showToast('Failed to save development plan.');
  } finally {
    isSavingPlan = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleTogglePlanStatus(planId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/development-plans.php?id=${planId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchDevelopmentPlans(planPagination.page);
      await fetchPlanStats();
    } else {
      showToast(result.message || 'Failed to update plan status.');
    }
  } catch (err) {
    console.error('Error updating development plan status:', err);
    showToast('Failed to update plan status.');
  }
}

async function fetchDevelopmentPlanDetail(planId) {
  try {
    const response = await fetch(`../../api/employee/development-plans.php?id=${planId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching development plan detail:', err);
    return null;
  }
}

async function fetchPlanDocuments(planId) {
  try {
    const response = await fetch(`../../api/employee/planning-documents.php?plan_id=${planId}`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error fetching plan documents:', err);
    return [];
  }
}

async function handleUploadPlanDocument(e) {
  e.preventDefault();

  const planId = document.getElementById('uploadPlanId').value;
  const documentType = document.getElementById('planDocumentType').value;
  const fileInput = document.getElementById('planDocumentFile');

  if (!fileInput.files.length) return;

  const formData = new FormData();
  formData.append('plan_id', planId);
  formData.append('document_type', documentType);
  formData.append('file', fileInput.files[0]);

  try {
    const response = await fetch('../../api/employee/planning-documents.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document uploaded.');
      document.getElementById('planDocumentUploadForm').reset();
      document.getElementById('planDocumentUploadForm').classList.add('hidden');
      await renderPlanDocuments(planId);
    } else {
      showToast(result.message || 'Failed to upload document.');
    }
  } catch (err) {
    console.error('Error uploading plan document:', err);
    showToast('Failed to upload document.');
  }
}

async function handleDeletePlanDocument(documentId, planId) {
  try {
    const response = await fetch(`../../api/employee/planning-documents.php?id=${documentId}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document deleted.');
      await renderPlanDocuments(planId);
    } else {
      showToast(result.message || 'Failed to delete document.');
    }
  } catch (err) {
    console.error('Error deleting plan document:', err);
    showToast('Failed to delete document.');
  }
}
