// Close modals on backdrop click
document.querySelectorAll('#beneficiaryModal, #viewBeneficiaryModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const benSearchInput = document.getElementById('benSearchInput');
if (benSearchInput) benSearchInput.addEventListener('input', triggerBenFilter);

const benStatusFilter = document.getElementById('benStatusFilter');
if (benStatusFilter) benStatusFilter.addEventListener('change', triggerBenFilter);

const benCategoryFilter = document.getElementById('benCategoryFilter');
if (benCategoryFilter) benCategoryFilter.addEventListener('change', triggerBenFilter);

const benRecordStatusFilter = document.getElementById('benRecordStatusFilter');
if (benRecordStatusFilter) benRecordStatusFilter.addEventListener('change', triggerBenFilter);

const benSortFilter = document.getElementById('benSortFilter');
if (benSortFilter) benSortFilter.addEventListener('change', triggerBenFilter);

const benResidentSearch = document.getElementById('benResidentSearch');
if (benResidentSearch) {
  benResidentSearch.addEventListener('input', (e) => triggerResidentPicker(e.target.value));
}

const benUnitSearch = document.getElementById('benUnitSearch');
if (benUnitSearch) {
  benUnitSearch.addEventListener('input', (e) => triggerUnitPicker(e.target.value));
}

// Hide picker dropdowns when clicking outside them
document.addEventListener('click', (e) => {
  const rBox = document.getElementById('benResidentResults');
  if (rBox && !rBox.contains(e.target) && e.target !== benResidentSearch) {
    rBox.classList.add('hidden');
  }
  const uBox = document.getElementById('benUnitResults');
  if (uBox && !uBox.contains(e.target) && e.target !== benUnitSearch) {
    uBox.classList.add('hidden');
  }
});
