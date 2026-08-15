function residentFullName(r) {
  const mid = r.middle_name ? ` ${r.middle_name}` : '';
  const suf = r.suffix ? ` ${r.suffix}` : '';
  return `${r.first_name || ''}${mid} ${r.last_name || ''}${suf}`.trim();
}

function toggleHistorySubjectFields() {
  const type = document.getElementById('historySubjectType').value;
  document.getElementById('historyResidentFields').classList.toggle('hidden', type !== 'Resident');
  document.getElementById('historyHouseholdFields').classList.toggle('hidden', type !== 'Household');
  document.getElementById('historySiteFields').classList.toggle('hidden', type !== 'Site');
  document.getElementById('historyTimeline').innerHTML =
    '<div class="px-6 py-8 text-center text-slate-400">Select a resident, household, or site above to view its survey history.</div>';
}

// ---- Resident picker ----
function selectHistoryResident(resident) {
  document.getElementById('historySubjectId').value = resident.resident_id;
  document.getElementById('historyResidentSearch').classList.add('hidden');
  document.getElementById('historyResidentResults').classList.add('hidden');
  const badge = document.getElementById('historySelectedResidentBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('historySelectedResidentLabel').innerText = residentFullName(resident) || 'Resident';
  fetchSubjectHistory('Resident', resident.resident_id, null);
}

function clearSelectedHistoryResident() {
  document.getElementById('historySubjectId').value = '';
  document.getElementById('historyResidentSearch').value = '';
  document.getElementById('historyResidentSearch').classList.remove('hidden');
  const badge = document.getElementById('historySelectedResidentBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
  toggleHistorySubjectFields();
}

// ---- Household picker ----
function selectHistoryHousehold(household) {
  document.getElementById('historyHouseholdSearch').classList.add('hidden');
  document.getElementById('historyHouseholdResults').classList.add('hidden');
  const badge = document.getElementById('historySelectedHouseholdBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  const label = household.household_number ? `HH ${household.household_number}` : 'Household';
  document.getElementById('historySelectedHouseholdLabel').innerText =
    `${label}${household.barangay ? ' — ' + household.barangay : ''}`;
  fetchSubjectHistory('Household', household.household_id, null);
}

function clearSelectedHistoryHousehold() {
  document.getElementById('historyHouseholdSearch').value = '';
  document.getElementById('historyHouseholdSearch').classList.remove('hidden');
  const badge = document.getElementById('historySelectedHouseholdBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
  toggleHistorySubjectFields();
}

// ---- Site lookup ----
function loadHistoryForSite() {
  const label = document.getElementById('historySiteLabel').value.trim();
  if (!label) {
    showToast('Enter a site label to look up.');
    return;
  }
  fetchSubjectHistory('Site', null, label);
}
