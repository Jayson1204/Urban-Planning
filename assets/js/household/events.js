// Close modals on backdrop click
document.querySelectorAll('#householdModal, #viewHouseholdModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const householdSearchInput = document.getElementById('householdSearchInput');
if (householdSearchInput) householdSearchInput.addEventListener('input', triggerHouseholdFilter);

const householdTypeFilter = document.getElementById('householdTypeFilter');
if (householdTypeFilter) householdTypeFilter.addEventListener('change', triggerHouseholdFilter);

const householdStatusFilter = document.getElementById('householdStatusFilter');
if (householdStatusFilter) householdStatusFilter.addEventListener('change', triggerHouseholdFilter);
