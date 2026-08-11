// Close modals on backdrop click
document.querySelectorAll('#projectModal, #viewProjectModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const projectSearchInput = document.getElementById('projectSearchInput');
if (projectSearchInput) projectSearchInput.addEventListener('input', triggerProjectFilter);

const projectTypeFilter = document.getElementById('projectTypeFilter');
if (projectTypeFilter) projectTypeFilter.addEventListener('change', triggerProjectFilter);

const projectStatusFilter = document.getElementById('projectStatusFilter');
if (projectStatusFilter) projectStatusFilter.addEventListener('change', triggerProjectFilter);

const projectRowStatusFilter = document.getElementById('projectRowStatusFilter');
if (projectRowStatusFilter) projectRowStatusFilter.addEventListener('change', triggerProjectFilter);

const projectPlanSearch = document.getElementById('projectPlanSearch');
if (projectPlanSearch) {
  projectPlanSearch.addEventListener('input', (e) => triggerProjectPlanPicker(e.target.value));
}

// Hide picker dropdown when clicking outside it
document.addEventListener('click', (e) => {
  const box = document.getElementById('projectPlanResults');
  if (box && !box.contains(e.target) && e.target !== projectPlanSearch) {
    box.classList.add('hidden');
  }
});
