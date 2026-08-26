// Close modals on backdrop click
document.querySelectorAll('#paModal, #viewPaModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const paSearchInput = document.getElementById('paSearchInput');
if (paSearchInput) paSearchInput.addEventListener('input', triggerPaFilter);

const paTypeFilter = document.getElementById('paTypeFilter');
if (paTypeFilter) paTypeFilter.addEventListener('change', triggerPaFilter);

const paStatusFilter = document.getElementById('paStatusFilter');
if (paStatusFilter) paStatusFilter.addEventListener('change', triggerPaFilter);

const paRecordStatusFilter = document.getElementById('paRecordStatusFilter');
if (paRecordStatusFilter) paRecordStatusFilter.addEventListener('change', triggerPaFilter);

const paResidentSearch = document.getElementById('paResidentSearch');
if (paResidentSearch) {
  paResidentSearch.addEventListener('input', (e) => triggerPaResidentPicker(e.target.value));
}

// Hide picker dropdown when clicking outside it
document.addEventListener('click', (e) => {
  const rBox = document.getElementById('paResidentResults');
  if (rBox && !rBox.contains(e.target) && e.target !== paResidentSearch) {
    rBox.classList.add('hidden');
  }
});
