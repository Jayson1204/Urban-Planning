// Close modals on backdrop click
document.querySelectorAll('#zcModal, #viewZcModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const zcSearchInput = document.getElementById('zcSearchInput');
if (zcSearchInput) zcSearchInput.addEventListener('input', triggerZcFilter);

const zcStatusFilter = document.getElementById('zcStatusFilter');
if (zcStatusFilter) zcStatusFilter.addEventListener('change', triggerZcFilter);

const zcConformityFilter = document.getElementById('zcConformityFilter');
if (zcConformityFilter) zcConformityFilter.addEventListener('change', triggerZcFilter);

const zcRecordStatusFilter = document.getElementById('zcRecordStatusFilter');
if (zcRecordStatusFilter) zcRecordStatusFilter.addEventListener('change', triggerZcFilter);

const zcResidentSearch = document.getElementById('zcResidentSearch');
if (zcResidentSearch) {
  zcResidentSearch.addEventListener('input', (e) => triggerZcResidentPicker(e.target.value));
}

// Hide picker dropdown when clicking outside it
document.addEventListener('click', (e) => {
  const rBox = document.getElementById('zcResidentResults');
  if (rBox && !rBox.contains(e.target) && e.target !== zcResidentSearch) {
    rBox.classList.add('hidden');
  }
});
