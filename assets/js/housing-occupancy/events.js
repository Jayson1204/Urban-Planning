// Close modals on backdrop click
document.querySelectorAll('#occupancyModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const occupancySearchInput = document.getElementById('occupancySearchInput');
if (occupancySearchInput) occupancySearchInput.addEventListener('input', triggerOccFilter);

const occupancyStatusFilter = document.getElementById('occupancyStatusFilter');
if (occupancyStatusFilter) occupancyStatusFilter.addEventListener('change', triggerOccFilter);

const occResidentSearch = document.getElementById('occResidentSearch');
if (occResidentSearch) {
  occResidentSearch.addEventListener('input', (e) => triggerOccResidentPicker(e.target.value));
}

const occUnitSearch = document.getElementById('occUnitSearch');
if (occUnitSearch) {
  occUnitSearch.addEventListener('input', (e) => triggerOccUnitPicker(e.target.value));
}

// Hide picker dropdowns when clicking outside them
document.addEventListener('click', (e) => {
  const rBox = document.getElementById('occResidentResults');
  if (rBox && !rBox.contains(e.target) && e.target !== occResidentSearch) {
    rBox.classList.add('hidden');
  }
  const uBox = document.getElementById('occUnitResults');
  if (uBox && !uBox.contains(e.target) && e.target !== occUnitSearch) {
    uBox.classList.add('hidden');
  }
});
