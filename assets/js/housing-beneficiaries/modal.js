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

// ---- Resident picker ----
function selectResident(resident) {
  selectedResidentId = resident.resident_id;
  document.getElementById('benResidentId').value = resident.resident_id;
  document.getElementById('benHouseholdId').value = resident.household_id || '';
  document.getElementById('benResidentSearch').classList.add('hidden');
  document.getElementById('benResidentResults').classList.add('hidden');
  const badge = document.getElementById('benSelectedResidentBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  const hh = resident.household_number ? ` • HH ${resident.household_number}` : '';
  document.getElementById('benSelectedResidentLabel').innerText = `${residentFullName(resident)}${hh}`;
}

function clearSelectedResident() {
  selectedResidentId = null;
  document.getElementById('benResidentId').value = '';
  document.getElementById('benHouseholdId').value = '';
  document.getElementById('benResidentSearch').value = '';
  document.getElementById('benResidentSearch').classList.remove('hidden');
  const badge = document.getElementById('benSelectedResidentBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

// ---- Unit picker ----
function selectBenUnit(unit) {
  selectedBenUnitId = unit.unit_id;
  document.getElementById('benUnitId').value = unit.unit_id;
  document.getElementById('benUnitSearch').classList.add('hidden');
  document.getElementById('benUnitResults').classList.add('hidden');
  const badge = document.getElementById('benSelectedUnitBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('benSelectedUnitLabel').innerText =
    `${unit.unit_code}${unit.project_name ? ' — ' + unit.project_name : ''}`;
}

function clearSelectedUnit() {
  selectedBenUnitId = null;
  document.getElementById('benUnitId').value = '';
  document.getElementById('benUnitSearch').value = '';
  document.getElementById('benUnitSearch').classList.remove('hidden');
  const badge = document.getElementById('benSelectedUnitBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

// Default the award date to today when the stage is switched to Awarded.
function toggleAwardFields() {
  const stage = document.getElementById('benStatus').value;
  const awardDate = document.getElementById('benAwardDate');
  if (stage === 'Awarded' && !awardDate.value) {
    awardDate.value = new Date().toISOString().substring(0, 10);
  }
}

function resetBeneficiaryForm() {
  document.getElementById('beneficiaryForm').reset();
  document.getElementById('beneficiaryIdRef').value = '';
  clearSelectedResident();
  clearSelectedUnit();
  document.getElementById('benStatus').value = 'Applicant';
  document.getElementById('benCategory').value = 'Informal Settler';
  document.getElementById('benApplicationDate').value = new Date().toISOString().substring(0, 10);
}

function openCreateBeneficiaryModal() {
  resetBeneficiaryForm();
  document.getElementById('beneficiaryModalTitle').innerText = 'Add Beneficiary';
  document.getElementById('beneficiaryModalIcon').className = 'fa-solid fa-hand-holding-heart text-brand-medium';
  openModal('beneficiaryModal');
}

async function openEditBeneficiaryModal(beneficiaryId) {
  const b = await fetchBeneficiaryDetail(beneficiaryId);
  if (!b) return;

  resetBeneficiaryForm();
  document.getElementById('beneficiaryIdRef').value = b.beneficiary_id;

  selectResident({
    resident_id: b.resident_id,
    first_name: (b.resident_name || '').split(' ')[0],
    last_name: (b.resident_name || '').split(' ').slice(1).join(' '),
    household_id: b.household_id,
    household_number: b.household_number,
  });
  document.getElementById('benSelectedResidentLabel').innerText =
    `${b.resident_name || 'Resident'}${b.household_number ? ' • HH ' + b.household_number : ''}`;

  if (b.unit_id) {
    selectBenUnit({ unit_id: b.unit_id, unit_code: b.unit_code, project_name: b.project_name });
  }

  document.getElementById('benStatus').value = b.beneficiary_status || 'Applicant';
  document.getElementById('benCategory').value = b.category || 'Other';
  document.getElementById('benApplicationDate').value = b.application_date || '';
  document.getElementById('benAwardDate').value = b.award_date || '';
  document.getElementById('benMonthlyIncome').value = b.monthly_income ?? '';
  document.getElementById('benFamilySize').value = b.family_size ?? '';
  document.getElementById('benRemarks').value = b.remarks || '';

  document.getElementById('beneficiaryModalTitle').innerText = 'Edit Beneficiary';
  document.getElementById('beneficiaryModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('beneficiaryModal');
}

async function openViewBeneficiaryModal(beneficiaryId) {
  const b = await fetchBeneficiaryDetail(beneficiaryId);
  if (!b) return;

  document.getElementById('viewBenName').innerText = b.resident_name || 'Unknown resident';
  document.getElementById('viewBenMeta').innerText =
    [b.beneficiary_status, b.category, b.resident_barangay].filter(Boolean).join(' • ') || 'No additional details on file.';
  document.getElementById('viewBenUnit').innerText = b.unit_id
    ? `${b.unit_code || ''}${b.project_name ? ' (' + b.project_name + ')' : ''}`
    : 'Unassigned';
  document.getElementById('viewBenAwardDate').innerText = b.award_date || '—';
  document.getElementById('viewBenIncome').innerHTML = benFormatPeso(b.monthly_income);
  document.getElementById('viewBenFamilySize').innerText =
    (b.family_size !== null && b.family_size !== undefined && b.family_size !== '') ? b.family_size : '—';

  const remarksEl = document.getElementById('viewBenRemarks');
  remarksEl.innerText = b.remarks && b.remarks.trim() ? b.remarks : 'No remarks on file.';

  openModal('viewBeneficiaryModal');
}
