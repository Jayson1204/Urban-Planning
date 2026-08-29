// Global State
let surveyFormData = [];
let surveyFormPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };

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

// Fetch paginated/filtered survey forms from the server
async function fetchSurveyForms(page = 1) {
  const search = document.getElementById('surveyFormSearchInput').value.trim();
  const category = document.getElementById('surveyFormCategoryFilter').value;
  const subjectType = document.getElementById('surveyFormSubjectFilter').value;
  const status = document.getElementById('surveyFormStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: surveyFormPagination.per_page });
  if (search) params.set('search', search);
  if (category) params.set('category', category);
  if (subjectType) params.set('subject_type', subjectType);
  if (status) params.set('status', status);

  try {
    const response = await fetch(`../../api/employee/field-survey-forms.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      surveyFormData = result.data || [];
      surveyFormPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderSurveyForms();
      renderSurveyFormPagination();
    } else {
      showToast(result.message || 'Error loading survey forms.');
    }
  } catch (err) {
    console.error('Error fetching survey forms:', err);
    showToast('Network error while loading survey forms.');
  }
}

async function fetchSurveyFormStats() {
  try {
    const response = await fetch('../../api/employee/field-survey-forms.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalForms').innerText = s.total || 0;
      document.getElementById('statActiveForms').innerText = s.active || 0;
      document.getElementById('statArchivedForms').innerText = s.archived || 0;
    }
  } catch (err) {
    console.error('Error fetching survey form stats:', err);
  }
}

let isSavingSurveyForm = false;

async function handleSaveSurveyForm(e) {
  e.preventDefault();

  if (isSavingSurveyForm) return;
  isSavingSurveyForm = true;
  const saveBtn = document.getElementById('surveyFormSaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  const idRef = document.getElementById('surveyFormIdRef').value;
  const payload = {
    form_code: document.getElementById('surveyFormCode').value.trim(),
    form_title: document.getElementById('surveyFormTitle').value.trim(),
    category: document.getElementById('surveyFormCategory').value,
    subject_type: document.getElementById('surveyFormSubjectType').value,
    description: document.getElementById('surveyFormDescription').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.form_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/field-survey-forms.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Survey form saved successfully.');
      closeModal('surveyFormModal');
      await fetchSurveyForms(surveyFormPagination.page);
      await fetchSurveyFormStats();
    } else {
      showToast(result.message || 'Failed to save survey form.');
    }
  } catch (err) {
    console.error('Error saving survey form:', err);
    showToast('Failed to save survey form.');
  } finally {
    isSavingSurveyForm = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleToggleSurveyFormStatus(formId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/field-survey-forms.php?id=${formId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchSurveyForms(surveyFormPagination.page);
      await fetchSurveyFormStats();
    } else {
      showToast(result.message || 'Failed to update form status.');
    }
  } catch (err) {
    console.error('Error updating survey form status:', err);
    showToast('Failed to update form status.');
  }
}

async function fetchSurveyFormDetail(formId) {
  try {
    const response = await fetch(`../../api/employee/field-survey-forms.php?id=${formId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching survey form detail:', err);
    return null;
  }
}
