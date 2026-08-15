// Close modals on backdrop click
document.querySelectorAll('#surveyFormModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const surveyFormSearchInput = document.getElementById('surveyFormSearchInput');
if (surveyFormSearchInput) surveyFormSearchInput.addEventListener('input', triggerSurveyFormFilter);

const surveyFormCategoryFilter = document.getElementById('surveyFormCategoryFilter');
if (surveyFormCategoryFilter) surveyFormCategoryFilter.addEventListener('change', triggerSurveyFormFilter);

const surveyFormSubjectFilter = document.getElementById('surveyFormSubjectFilter');
if (surveyFormSubjectFilter) surveyFormSubjectFilter.addEventListener('change', triggerSurveyFormFilter);

const surveyFormStatusFilter = document.getElementById('surveyFormStatusFilter');
if (surveyFormStatusFilter) surveyFormStatusFilter.addEventListener('change', triggerSurveyFormFilter);
