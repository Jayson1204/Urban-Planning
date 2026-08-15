// Global State
let assignmentsData = [];
let assignmentsPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedAssignmentResidentId = null;
let selectedAssignmentHouseholdId = null;

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

async function loadFormOptions() {
  const select = document.getElementById('assignmentFormId');
  if (!select) return;
  try {
    const response = await fetch('../../api/employee/field-survey-forms.php?status=Active&per_page=100');
    const result = await response.json();
    if (result.status === 'success') {
      const forms = result.data || [];
      select.innerHTML = '<option value="">Select a survey form...</option>' +
        forms.map(f => `<option value="${f.form_id}">${f.form_code} — ${f.form_title}</option>`).join('');
    }
  } catch (err) {
    console.error('Error loading survey forms:', err);
  }
}

// Fetch paginated/filtered survey assignments from the server
async function fetchAssignments(page = 1) {
  const search = document.getElementById('assignmentSearchInput').value.trim();
  const subjectType = document.getElementById('assignmentSubjectFilter').value;
  const assignmentStatus = document.getElementById('assignmentStatusFilter').value;
  const recordStatus = document.getElementById('assignmentRecordStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: assignmentsPagination.per_page });
  if (search) params.set('search', search);
  if (subjectType) params.set('subject_type', subjectType);
  if (assignmentStatus) params.set('assignment_status', assignmentStatus);
  if (recordStatus) params.set('status', recordStatus);

  try {
    const response = await fetch(`../../api/employee/field-survey-assignments.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      assignmentsData = result.data || [];
      assignmentsPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderAssignments();
      renderAssignmentPagination();
    } else {
      showToast(result.message || 'Error loading survey assignments.');
    }
  } catch (err) {
    console.error('Error fetching survey assignments:', err);
    showToast('Network error while loading survey assignments.');
  }
}

async function fetchAssignmentStats() {
  try {
    const response = await fetch('../../api/employee/field-survey-assignments.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalAssignments').innerText = s.total || 0;
      document.getElementById('statPendingAssignments').innerText = s.pending || 0;
      document.getElementById('statInProgressAssignments').innerText = s.in_progress || 0;
      document.getElementById('statCompletedAssignments').innerText = s.completed || 0;
    }
  } catch (err) {
    console.error('Error fetching survey assignment stats:', err);
  }
}

async function handleSaveAssignment(e) {
  e.preventDefault();

  const idRef = document.getElementById('assignmentIdRef').value;
  const subjectType = document.getElementById('assignmentSubjectType').value;

  const payload = {
    form_id: document.getElementById('assignmentFormId').value ? parseInt(document.getElementById('assignmentFormId').value) : null,
    subject_type: subjectType,
    subject_id: subjectType === 'Resident' ? (selectedAssignmentResidentId ? parseInt(selectedAssignmentResidentId) : null)
              : subjectType === 'Household' ? (selectedAssignmentHouseholdId ? parseInt(selectedAssignmentHouseholdId) : null)
              : null,
    site_label: document.getElementById('assignmentSiteLabel').value.trim(),
    site_address: document.getElementById('assignmentSiteAddress').value.trim(),
    assigned_to: document.getElementById('assignmentAssignedTo').value.trim(),
    due_date: document.getElementById('assignmentDueDate').value || null,
    assignment_status: document.getElementById('assignmentStatus').value,
    remarks: document.getElementById('assignmentRemarks').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.assignment_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/field-survey-assignments.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Survey assignment saved successfully.');
      closeModal('assignmentModal');
      await fetchAssignments(assignmentsPagination.page);
      await fetchAssignmentStats();
    } else {
      showToast(result.message || 'Failed to save survey assignment.');
    }
  } catch (err) {
    console.error('Error saving survey assignment:', err);
    showToast('Failed to save survey assignment.');
  }
}

async function handleToggleAssignmentStatus(assignmentId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/field-survey-assignments.php?id=${assignmentId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchAssignments(assignmentsPagination.page);
      await fetchAssignmentStats();
    } else {
      showToast(result.message || 'Failed to update assignment status.');
    }
  } catch (err) {
    console.error('Error updating survey assignment status:', err);
    showToast('Failed to update assignment status.');
  }
}

async function fetchAssignmentDetail(assignmentId) {
  try {
    const response = await fetch(`../../api/employee/field-survey-assignments.php?id=${assignmentId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching survey assignment detail:', err);
    return null;
  }
}

// Pickers reuse existing module endpoints (no new endpoints needed)
async function searchResidentsForAssignmentPicker(term) {
  try {
    const response = await fetch(`../../api/employee/residents.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching residents:', err);
    return [];
  }
}

async function searchHouseholdsForAssignmentPicker(term) {
  try {
    const response = await fetch(`../../api/employee/households.php?search=${encodeURIComponent(term)}`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching households:', err);
    return [];
  }
}
