let planFilterDebounce = null;
function triggerPlanFilter() {
  clearTimeout(planFilterDebounce);
  planFilterDebounce = setTimeout(() => fetchDevelopmentPlans(1), 350);
}
