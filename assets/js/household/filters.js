let householdFilterDebounce = null;
function triggerHouseholdFilter() {
  clearTimeout(householdFilterDebounce);
  householdFilterDebounce = setTimeout(() => fetchHouseholds(1), 350);
}
