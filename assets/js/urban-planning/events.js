// Close modals on backdrop click
document.querySelectorAll('#planModal, #viewPlanModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const planSearchInput = document.getElementById('planSearchInput');
if (planSearchInput) planSearchInput.addEventListener('input', triggerPlanFilter);

const planBarangayFilter = document.getElementById('planBarangayFilter');
if (planBarangayFilter) planBarangayFilter.addEventListener('input', triggerPlanFilter);

const planTypeFilter = document.getElementById('planTypeFilter');
if (planTypeFilter) planTypeFilter.addEventListener('change', triggerPlanFilter);

const planStatusFilter = document.getElementById('planStatusFilter');
if (planStatusFilter) planStatusFilter.addEventListener('change', triggerPlanFilter);

const rowStatusFilter = document.getElementById('rowStatusFilter');
if (rowStatusFilter) rowStatusFilter.addEventListener('change', triggerPlanFilter);
