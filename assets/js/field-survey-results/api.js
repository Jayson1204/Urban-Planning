// Global State
let resultsData = [];
let resultsPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };

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

async function loadAssignmentOptions() {
  const select = document.getElementById('resultAssignmentId');
  if (!select) return;
  try {
    const response = await fetch('../../api/employee/field-survey-assignments.php?status=Active&per_page=100');
    const result = await response.json();
    if (result.status === 'success') {
      const assignments = result.data || [];
      select.innerHTML = '<option value="">Select an assignment...</option>' +
        assignments.map(a => `<option value="${a.assignment_id}">${escapeHtml(a.form_code)} — ${escapeHtml(a.subject_name) || 'Unnamed subject'} (${escapeHtml(a.subject_type)})</option>`).join('');
    }
  } catch (err) {
    console.error('Error loading survey assignments:', err);
  }
}

// Fetch paginated/filtered survey results from the server
async function fetchResults(page = 1) {
  const search = document.getElementById('resultSearchInput').value.trim();
  const condition = document.getElementById('resultConditionFilter').value;
  const status = document.getElementById('resultStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: resultsPagination.per_page });
  if (search) params.set('search', search);
  if (condition) params.set('condition_rating', condition);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/field-survey-results.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      resultsData = result.data || [];
      resultsPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderResults();
      renderResultPagination();
    } else {
      showToast(result.message || 'Error loading survey results.');
    }
  } catch (err) {
    console.error('Error fetching survey results:', err);
    showToast('Network error while loading survey results.');
  }
}

async function fetchResultStats() {
  try {
    const response = await fetch('../../api/employee/field-survey-results.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalResults').innerText = s.total || 0;
      document.getElementById('statActiveResults').innerText = s.active || 0;
      document.getElementById('statArchivedResults').innerText = s.archived || 0;
    }
  } catch (err) {
    console.error('Error fetching survey result stats:', err);
  }
}

let isSavingResult = false;

async function handleSaveResult(e) {
  e.preventDefault();

  if (isSavingResult) return;
  isSavingResult = true;
  const saveBtn = document.getElementById('resultSaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  const idRef = document.getElementById('resultIdRef').value;
  const payload = {
    assignment_id: document.getElementById('resultAssignmentId').value ? parseInt(document.getElementById('resultAssignmentId').value) : null,
    survey_date: document.getElementById('resultSurveyDate').value || null,
    condition_rating: document.getElementById('resultConditionRating').value || null,
    population_count: document.getElementById('resultPopulationCount').value || null,
    income_bracket: document.getElementById('resultIncomeBracket').value.trim(),
    findings: document.getElementById('resultFindings').value.trim(),
    recommendations: document.getElementById('resultRecommendations').value.trim(),
    additional_notes: document.getElementById('resultAdditionalNotes').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.result_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/field-survey-results.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Survey result saved successfully.');
      closeModal('resultModal');
      await fetchResults(resultsPagination.page);
      await fetchResultStats();
    } else {
      showToast(result.message || 'Failed to save survey result.');
    }
  } catch (err) {
    console.error('Error saving survey result:', err);
    showToast('Failed to save survey result.');
  } finally {
    isSavingResult = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleToggleResultStatus(resultId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/field-survey-results.php?id=${resultId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchResults(resultsPagination.page);
      await fetchResultStats();
    } else {
      showToast(result.message || 'Failed to update result status.');
    }
  } catch (err) {
    console.error('Error updating survey result status:', err);
    showToast('Failed to update result status.');
  }
}

async function fetchResultDetail(resultId) {
  try {
    const response = await fetch(`../../api/employee/field-survey-results.php?id=${resultId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching survey result detail:', err);
    return null;
  }
}

async function handleUploadPhoto(e) {
  e.preventDefault();

  const resultId = document.getElementById('uploadResultId').value;
  const caption = document.getElementById('photoCaption').value.trim();
  const fileInput = document.getElementById('photoFile');

  if (!fileInput.files.length) return;

  const formData = new FormData();
  formData.append('result_id', resultId);
  formData.append('caption', caption);
  formData.append('file', fileInput.files[0]);

  try {
    const response = await fetch('../../api/employee/field-survey-photos.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Photo uploaded.');
      document.getElementById('photoUploadForm').reset();
      document.getElementById('photoUploadForm').classList.add('hidden');
      await openViewResultModal(resultId);
    } else {
      showToast(result.message || 'Failed to upload photo.');
    }
  } catch (err) {
    console.error('Error uploading photo:', err);
    showToast('Failed to upload photo.');
  }
}

async function handleDeletePhoto(photoId, resultId) {
  try {
    const response = await fetch(`../../api/employee/field-survey-photos.php?id=${photoId}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Photo deleted.');
      await openViewResultModal(resultId);
    } else {
      showToast(result.message || 'Failed to delete photo.');
    }
  } catch (err) {
    console.error('Error deleting photo:', err);
    showToast('Failed to delete photo.');
  }
}
