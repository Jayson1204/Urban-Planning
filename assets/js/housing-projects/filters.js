let hpFilterDebounce = null;
function triggerHpFilter() {
  clearTimeout(hpFilterDebounce);
  hpFilterDebounce = setTimeout(() => fetchHousingProjectsList(1), 350);
}
