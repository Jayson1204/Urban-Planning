let resultFilterDebounce = null;
function triggerResultFilter() {
  clearTimeout(resultFilterDebounce);
  resultFilterDebounce = setTimeout(() => fetchResults(1), 350);
}
