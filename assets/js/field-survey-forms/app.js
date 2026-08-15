// Field Survey Forms Module Bootstrap
window.loadCiventralScript('assets/js/field-survey-forms/api.js');
window.loadCiventralScript('assets/js/field-survey-forms/table.js');
window.loadCiventralScript('assets/js/field-survey-forms/modal.js');
window.loadCiventralScript('assets/js/field-survey-forms/filters.js');
window.loadCiventralScript('assets/js/field-survey-forms/events.js', () => {
    // Only execute if we are actually on the survey forms page
    if (document.getElementById('surveyFormsTableBody') && typeof fetchSurveyForms === 'function') {
        fetchSurveyForms(1);
        fetchSurveyFormStats();
    }
});
