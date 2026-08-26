document.querySelectorAll('#subdivisionModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const subdivisionSearchInput = document.getElementById('subdivisionSearchInput');
if (subdivisionSearchInput) subdivisionSearchInput.addEventListener('input', triggerSubdivisionFilter);

const subdivisionStatusFilter = document.getElementById('subdivisionStatusFilter');
if (subdivisionStatusFilter) subdivisionStatusFilter.addEventListener('change', triggerSubdivisionFilter);
