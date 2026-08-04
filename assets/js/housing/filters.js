let housingFilterDebounce = null;
function triggerHousingFilter() {
  clearTimeout(housingFilterDebounce);
  housingFilterDebounce = setTimeout(() => fetchHousingUnits(1), 350);
}
