// Close modals on backdrop click
document.querySelectorAll('#housingModal, #viewHousingModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const housingSearchInput = document.getElementById('housingSearchInput');
if (housingSearchInput) housingSearchInput.addEventListener('input', triggerHousingFilter);

const housingBarangayFilter = document.getElementById('housingBarangayFilter');
if (housingBarangayFilter) housingBarangayFilter.addEventListener('input', triggerHousingFilter);

const housingOccupancyFilter = document.getElementById('housingOccupancyFilter');
if (housingOccupancyFilter) housingOccupancyFilter.addEventListener('change', triggerHousingFilter);

const housingStatusFilter = document.getElementById('housingStatusFilter');
if (housingStatusFilter) housingStatusFilter.addEventListener('change', triggerHousingFilter);
