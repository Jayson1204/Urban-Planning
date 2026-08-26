let subdivisionFilterDebounce = null;
function triggerSubdivisionFilter() {
  clearTimeout(subdivisionFilterDebounce);
  subdivisionFilterDebounce = setTimeout(() => fetchSubdivisionsList(1), 350);
}
