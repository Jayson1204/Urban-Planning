function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('opacity-0', 'pointer-events-none');
  modal.classList.add('opacity-100', 'pointer-events-auto');
  modal.querySelector('.transform').classList.remove('scale-95');
  modal.querySelector('.transform').classList.add('scale-100');
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('opacity-100', 'pointer-events-auto');
  modal.classList.add('opacity-0', 'pointer-events-none');
  modal.querySelector('.transform').classList.remove('scale-100');
  modal.querySelector('.transform').classList.add('scale-95');
}

function resetSurveyFormForm() {
  document.getElementById('surveyFormForm').reset();
  document.getElementById('surveyFormIdRef').value = '';
  document.getElementById('surveyFormCategory').value = 'Household Assessment';
  document.getElementById('surveyFormSubjectType').value = 'Resident';
}

function openCreateSurveyFormModal() {
  resetSurveyFormForm();
  document.getElementById('surveyFormModalTitle').innerText = 'Add Survey Form';
  document.getElementById('surveyFormModalIcon').className = 'fa-solid fa-clipboard-list text-brand-medium';
  openModal('surveyFormModal');
}

async function openEditSurveyFormModal(formId) {
  const form = await fetchSurveyFormDetail(formId);
  if (!form) return;

  resetSurveyFormForm();
  document.getElementById('surveyFormIdRef').value = form.form_id;
  document.getElementById('surveyFormCode').value = form.form_code || '';
  document.getElementById('surveyFormTitle').value = form.form_title || '';
  document.getElementById('surveyFormCategory').value = form.category || 'Other';
  document.getElementById('surveyFormSubjectType').value = form.subject_type || 'Site';
  document.getElementById('surveyFormDescription').value = form.description || '';

  document.getElementById('surveyFormModalTitle').innerText = 'Edit Survey Form';
  document.getElementById('surveyFormModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('surveyFormModal');
}
