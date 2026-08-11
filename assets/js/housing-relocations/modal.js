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
async function selectRelResident(resident) {
  selectedRelResidentId = resident.resident_id;
  document.getElementById('relResidentId').value = resident.resident_id;
  document.getElementById('relResidentSearch').classList.add('hidden');
  document.getElementById('relResidentResults').classList.add('hidden');
  const badge = document.getElementById('relSelectedResidentBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('relSelectedResidentLabel').innerText = residentFullName(resident);

  window.relCurrentUnitId = null;
  const hint = document.getElementById('relCurrentUnitHint');
  const activeOccupancy = await fetchActiveOccupancyForResident(resident.resident_id);
  if (activeOccupancy) {
    window.relCurrentUnitId = activeOccupancy.unit_id;
    hint.innerText = `Currently in unit #${activeOccupancy.unit_id} — this will be recorded as the source unit.`;
    hint.classList.remove('hidden');
  } else {
    hint.innerText = 'No current occupancy on record for this resident — will be logged without a source unit.';
    hint.classList.remove('hidden');
  }
}

function clearRelSelectedResident() {
  selectedRelResidentId = null;
  window.relCurrentUnitId = null;
  document.getElementById('relResidentId').value = '';
  document.getElementById('relResidentSearch').value = '';
  document.getElementById('relResidentSearch').classList.remove('hidden');
  document.getElementById('relCurrentUnitHint').classList.add('hidden');
  const badge = document.getElementById('relSelectedResidentBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

// ---- Destination unit picker ----
function selectRelToUnit(unit) {
  selectedRelToUnitId = unit.unit_id;
  document.getElementById('relToUnitId').value = unit.unit_id;
  document.getElementById('relToUnitSearch').classList.add('hidden');
  document.getElementById('relToUnitResults').classList.add('hidden');
  const badge = document.getElementById('relSelectedToUnitBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('relSelectedToUnitLabel').innerText =
    `${unit.unit_code}${unit.project_name ? ' — ' + unit.project_name : ''}`;
}

function clearRelSelectedToUnit() {
  selectedRelToUnitId = null;
  document.getElementById('relToUnitId').value = '';
  document.getElementById('relToUnitSearch').value = '';
  document.getElementById('relToUnitSearch').classList.remove('hidden');
  const badge = document.getElementById('relSelectedToUnitBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

function resetRelocationForm() {
  document.getElementById('relocationForm').reset();
  clearRelSelectedResident();
  clearRelSelectedToUnit();
  document.getElementById('relReason').value = 'Other';
  document.getElementById('relDate').value = new Date().toISOString().substring(0, 10);
}

function openCreateRelocationModal() {
  resetRelocationForm();
  openModal('relocationModal');
}

async function openViewRelocationModal(relocationId) {
  const r = await fetchRelocationDetail(relocationId);
  if (!r) return;

  document.getElementById('viewRelResident').innerText = r.resident_name || 'Unknown resident';
  document.getElementById('viewRelMeta').innerText =
    [r.relocation_date, r.reason, r.status].filter(Boolean).join(' • ') || 'No additional details on file.';
  document.getElementById('viewRelFromUnit').innerText = r.from_unit_code || 'Unrecorded';
  document.getElementById('viewRelToUnit').innerText = r.to_unit_code || '—';

  const remarksEl = document.getElementById('viewRelRemarks');
  remarksEl.innerText = r.remarks && r.remarks.trim() ? r.remarks : 'No remarks on file.';

  openModal('viewRelocationModal');
}
