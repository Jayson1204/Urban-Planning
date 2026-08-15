// Close modals on backdrop click
document.querySelectorAll('#assignmentModal').forEach(modal => {
  modal.addEventListener('mousedown', function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

const assignmentSearchInput = document.getElementById('assignmentSearchInput');
if (assignmentSearchInput) assignmentSearchInput.addEventListener('input', triggerAssignmentFilter);

const assignmentSubjectFilter = document.getElementById('assignmentSubjectFilter');
if (assignmentSubjectFilter) assignmentSubjectFilter.addEventListener('change', triggerAssignmentFilter);

const assignmentStatusFilter = document.getElementById('assignmentStatusFilter');
if (assignmentStatusFilter) assignmentStatusFilter.addEventListener('change', triggerAssignmentFilter);

const assignmentRecordStatusFilter = document.getElementById('assignmentRecordStatusFilter');
if (assignmentRecordStatusFilter) assignmentRecordStatusFilter.addEventListener('change', triggerAssignmentFilter);

const assignmentResidentSearch = document.getElementById('assignmentResidentSearch');
if (assignmentResidentSearch) {
  assignmentResidentSearch.addEventListener('input', (e) => triggerAssignmentResidentPicker(e.target.value));
}

const assignmentHouseholdSearch = document.getElementById('assignmentHouseholdSearch');
if (assignmentHouseholdSearch) {
  assignmentHouseholdSearch.addEventListener('input', (e) => triggerAssignmentHouseholdPicker(e.target.value));
}

// Hide picker dropdowns when clicking outside them
document.addEventListener('click', (e) => {
  const rBox = document.getElementById('assignmentResidentResults');
  if (rBox && !rBox.contains(e.target) && e.target !== assignmentResidentSearch) {
    rBox.classList.add('hidden');
  }
  const hBox = document.getElementById('assignmentHouseholdResults');
  if (hBox && !hBox.contains(e.target) && e.target !== assignmentHouseholdSearch) {
    hBox.classList.add('hidden');
  }
});
