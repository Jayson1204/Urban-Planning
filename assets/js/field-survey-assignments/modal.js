function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('opacity-0', 'pointer-events-none');
  modal.classList.add('opacity-100', 'pointer-events-auto');
  modal.querySelector('.transform').classList.remove('scale-95');
  modal.querySelector('.transform').classList.add('scale-100');
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('opacity-100', 'pointer-events-auto');
  modal.classList.add('opacity-0', 'pointer-events-none');
  modal.querySelector('.transform').classList.remove('scale-100');
  modal.querySelector('.transform').classList.add('scale-95');
}

function residentFullName(r) {
  const mid = r.middle_name ? ` ${r.middle_name}` : '';
  const suf = r.suffix ? ` ${r.suffix}` : '';
  return `${r.first_name || ''}${mid} ${r.last_name || ''}${suf}`.trim();
}

function toggleSubjectFields() {
  const type = document.getElementById('assignmentSubjectType').value;
  document.getElementById('assignmentResidentFields').classList.toggle('hidden', type !== 'Resident');
  document.getElementById('assignmentHouseholdFields').classList.toggle('hidden', type !== 'Household');
  document.getElementById('assignmentSiteFields').classList.toggle('hidden', type !== 'Site');
}

// ---- Resident picker ----
function selectAssignmentResident(resident) {
  selectedAssignmentResidentId = resident.resident_id;
  document.getElementById('assignmentResidentId').value = resident.resident_id;
  document.getElementById('assignmentResidentSearch').classList.add('hidden');
  document.getElementById('assignmentResidentResults').classList.add('hidden');
  const badge = document.getElementById('assignmentSelectedResidentBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('assignmentSelectedResidentLabel').innerText = residentFullName(resident) || 'Resident';
}

function clearSelectedAssignmentResident() {
  selectedAssignmentResidentId = null;
  document.getElementById('assignmentResidentId').value = '';
  document.getElementById('assignmentResidentSearch').value = '';
  document.getElementById('assignmentResidentSearch').classList.remove('hidden');
  const badge = document.getElementById('assignmentSelectedResidentBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

// ---- Household picker ----
function selectAssignmentHousehold(household) {
  selectedAssignmentHouseholdId = household.household_id;
  document.getElementById('assignmentHouseholdId').value = household.household_id;
  document.getElementById('assignmentHouseholdSearch').classList.add('hidden');
  document.getElementById('assignmentHouseholdResults').classList.add('hidden');
  const badge = document.getElementById('assignmentSelectedHouseholdBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  const label = household.household_number ? `HH ${household.household_number}` : 'Household';
  document.getElementById('assignmentSelectedHouseholdLabel').innerText =
    `${label}${household.barangay ? ' — ' + household.barangay : ''}`;
}

function clearSelectedAssignmentHousehold() {
  selectedAssignmentHouseholdId = null;
  document.getElementById('assignmentHouseholdId').value = '';
  document.getElementById('assignmentHouseholdSearch').value = '';
  document.getElementById('assignmentHouseholdSearch').classList.remove('hidden');
  const badge = document.getElementById('assignmentSelectedHouseholdBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

function resetAssignmentForm() {
  document.getElementById('assignmentForm').reset();
  document.getElementById('assignmentIdRef').value = '';
  clearSelectedAssignmentResident();
  clearSelectedAssignmentHousehold();
  document.getElementById('assignmentSubjectType').value = 'Resident';
  document.getElementById('assignmentStatus').value = 'Pending';
  toggleSubjectFields();
}

function openCreateAssignmentModal() {
  resetAssignmentForm();
  document.getElementById('assignmentModalTitle').innerText = 'Add Assignment';
  document.getElementById('assignmentModalIcon').className = 'fa-solid fa-clipboard-user text-brand-medium';
  openModal('assignmentModal');
}

async function openEditAssignmentModal(assignmentId) {
  const a = await fetchAssignmentDetail(assignmentId);
  if (!a) return;

  resetAssignmentForm();
  document.getElementById('assignmentIdRef').value = a.assignment_id;
  document.getElementById('assignmentFormId').value = a.form_id || '';
  document.getElementById('assignmentSubjectType').value = a.subject_type || 'Site';
  toggleSubjectFields();

  if (a.subject_type === 'Resident' && a.subject_id) {
    selectAssignmentResident({ resident_id: a.subject_id, first_name: a.subject_name || 'Resident', middle_name: '', last_name: '', suffix: '' });
    document.getElementById('assignmentSelectedResidentLabel').innerText = a.subject_name || 'Resident';
  } else if (a.subject_type === 'Household' && a.subject_id) {
    selectAssignmentHousehold({ household_id: a.subject_id, household_number: a.subject_name, barangay: a.household_barangay });
  } else {
    document.getElementById('assignmentSiteLabel').value = a.site_label || '';
    document.getElementById('assignmentSiteAddress').value = a.site_address || '';
  }

  document.getElementById('assignmentAssignedTo').value = a.assigned_to || '';
  document.getElementById('assignmentDueDate').value = a.due_date || '';
  document.getElementById('assignmentStatus').value = a.assignment_status || 'Pending';
  document.getElementById('assignmentRemarks').value = a.remarks || '';

  document.getElementById('assignmentModalTitle').innerText = 'Edit Assignment';
  document.getElementById('assignmentModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('assignmentModal');
}
