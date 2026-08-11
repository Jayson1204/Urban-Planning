// Close modals on backdrop click
document.querySelectorAll('#relocationModal, #viewRelocationModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const relocationSearchInput = document.getElementById('relocationSearchInput');
if (relocationSearchInput) relocationSearchInput.addEventListener('input', triggerRelFilter);

const relocationReasonFilter = document.getElementById('relocationReasonFilter');
if (relocationReasonFilter) relocationReasonFilter.addEventListener('change', triggerRelFilter);

const relocationStatusFilter = document.getElementById('relocationStatusFilter');
if (relocationStatusFilter) relocationStatusFilter.addEventListener('change', triggerRelFilter);

const relResidentSearch = document.getElementById('relResidentSearch');
if (relResidentSearch) {
  relResidentSearch.addEventListener('input', (e) => triggerRelResidentPicker(e.target.value));
}

const relToUnitSearch = document.getElementById('relToUnitSearch');
if (relToUnitSearch) {
  relToUnitSearch.addEventListener('input', (e) => triggerRelToUnitPicker(e.target.value));
}

// Hide picker dropdowns when clicking outside them
document.addEventListener('click', (e) => {
  const rBox = document.getElementById('relResidentResults');
  if (rBox && !rBox.contains(e.target) && e.target !== relResidentSearch) {
    rBox.classList.add('hidden');
  }
  const uBox = document.getElementById('relToUnitResults');
  if (uBox && !uBox.contains(e.target) && e.target !== relToUnitSearch) {
    uBox.classList.add('hidden');
  }
});
