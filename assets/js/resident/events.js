// Close modals on backdrop click
document.querySelectorAll('#residentModal, #viewResidentModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const residentSearchInput = document.getElementById('residentSearchInput');
if (residentSearchInput) residentSearchInput.addEventListener('input', triggerResidentFilter);

const residentBarangayFilter = document.getElementById('residentBarangayFilter');
if (residentBarangayFilter) residentBarangayFilter.addEventListener('input', triggerResidentFilter);

const residentStatusFilter = document.getElementById('residentStatusFilter');
if (residentStatusFilter) residentStatusFilter.addEventListener('change', triggerResidentFilter);

const residentHouseholdSearch = document.getElementById('residentHouseholdSearch');
if (residentHouseholdSearch) {
  residentHouseholdSearch.addEventListener('input', (e) => triggerHouseholdSearch(e.target.value));
  document.addEventListener('click', (e) => {
    const resultsBox = document.getElementById('householdSearchResults');
    if (resultsBox && !resultsBox.contains(e.target) && e.target !== residentHouseholdSearch) {
      resultsBox.classList.add('hidden');
    }
  });
}
