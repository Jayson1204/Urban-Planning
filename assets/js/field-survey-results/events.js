// Close modals on backdrop click
document.querySelectorAll('#resultModal, #viewResultModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const resultSearchInput = document.getElementById('resultSearchInput');
if (resultSearchInput) resultSearchInput.addEventListener('input', triggerResultFilter);

const resultConditionFilter = document.getElementById('resultConditionFilter');
if (resultConditionFilter) resultConditionFilter.addEventListener('change', triggerResultFilter);

const resultStatusFilter = document.getElementById('resultStatusFilter');
if (resultStatusFilter) resultStatusFilter.addEventListener('change', triggerResultFilter);
