document.querySelectorAll('#hpModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const hpSearchInput = document.getElementById('hpSearchInput');
if (hpSearchInput) hpSearchInput.addEventListener('input', triggerHpFilter);

const hpProjectStatusFilter = document.getElementById('hpProjectStatusFilter');
if (hpProjectStatusFilter) hpProjectStatusFilter.addEventListener('change', triggerHpFilter);

const hpStatusFilter = document.getElementById('hpStatusFilter');
if (hpStatusFilter) hpStatusFilter.addEventListener('change', triggerHpFilter);
