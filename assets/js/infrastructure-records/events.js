// Close modals on backdrop click
document.querySelectorAll('#infraModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const infraSearchInput = document.getElementById('infraSearchInput');
if (infraSearchInput) infraSearchInput.addEventListener('input', triggerInfraFilter);

const infraTypeFilter = document.getElementById('infraTypeFilter');
if (infraTypeFilter) infraTypeFilter.addEventListener('change', triggerInfraFilter);

const infraConditionFilter = document.getElementById('infraConditionFilter');
if (infraConditionFilter) infraConditionFilter.addEventListener('change', triggerInfraFilter);

const infraRowStatusFilter = document.getElementById('infraRowStatusFilter');
if (infraRowStatusFilter) infraRowStatusFilter.addEventListener('change', triggerInfraFilter);

const infraProjectSearch = document.getElementById('infraProjectSearch');
if (infraProjectSearch) {
  infraProjectSearch.addEventListener('input', (e) => triggerInfraProjectPicker(e.target.value));
}

// Hide picker dropdown when clicking outside it
document.addEventListener('click', (e) => {
  const box = document.getElementById('infraProjectResults');
  if (box && !box.contains(e.target) && e.target !== infraProjectSearch) {
    box.classList.add('hidden');
  }
});
