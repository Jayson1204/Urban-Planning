const historyResidentSearch = document.getElementById('historyResidentSearch');
if (historyResidentSearch) {
  historyResidentSearch.addEventListener('input', (e) => triggerHistoryResidentPicker(e.target.value));
}

const historyHouseholdSearch = document.getElementById('historyHouseholdSearch');
if (historyHouseholdSearch) {
  historyHouseholdSearch.addEventListener('input', (e) => triggerHistoryHouseholdPicker(e.target.value));
}

const historySiteLabel = document.getElementById('historySiteLabel');
if (historySiteLabel) {
  historySiteLabel.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      loadHistoryForSite();
    }
  });
}

// Hide picker dropdowns when clicking outside them
document.addEventListener('click', (e) => {
  const rBox = document.getElementById('historyResidentResults');
  if (rBox && !rBox.contains(e.target) && e.target !== historyResidentSearch) {
    rBox.classList.add('hidden');
  }
  const hBox = document.getElementById('historyHouseholdResults');
  if (hBox && !hBox.contains(e.target) && e.target !== historyHouseholdSearch) {
    hBox.classList.add('hidden');
  }
});
