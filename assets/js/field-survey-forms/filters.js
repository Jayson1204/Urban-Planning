let surveyFormFilterDebounce = null;
function triggerSurveyFormFilter() {
  clearTimeout(surveyFormFilterDebounce);
  surveyFormFilterDebounce = setTimeout(() => fetchSurveyForms(1), 350);
}
