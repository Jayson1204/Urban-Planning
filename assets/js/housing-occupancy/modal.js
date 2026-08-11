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
function selectOccResident(resident) {
  selectedOccResidentId = resident.resident_id;
  document.getElementById('occResidentId').value = resident.resident_id;
  document.getElementById('occResidentSearch').classList.add('hidden');
  document.getElementById('occResidentResults').classList.add('hidden');
  const badge = document.getElementById('occSelectedResidentBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('occSelectedResidentLabel').innerText = residentFullName(resident);
}

function clearOccSelectedResident() {
  selectedOccResidentId = null;
  document.getElementById('occResidentId').value = '';
  document.getElementById('occResidentSearch').value = '';
  document.getElementById('occResidentSearch').classList.remove('hidden');
  const badge = document.getElementById('occSelectedResidentBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

// ---- Unit picker ----
function selectOccUnit(unit) {
  selectedOccUnitId = unit.unit_id;
  document.getElementById('occUnitId').value = unit.unit_id;
  document.getElementById('occUnitSearch').classList.add('hidden');
  document.getElementById('occUnitResults').classList.add('hidden');
  const badge = document.getElementById('occSelectedUnitBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('occSelectedUnitLabel').innerText =
    `${unit.unit_code}${unit.project_name ? ' — ' + unit.project_name : ''}`;
}

function clearOccSelectedUnit() {
  selectedOccUnitId = null;
  document.getElementById('occUnitId').value = '';
  document.getElementById('occUnitSearch').value = '';
  document.getElementById('occUnitSearch').classList.remove('hidden');
  const badge = document.getElementById('occSelectedUnitBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

function resetOccupancyForm() {
  document.getElementById('occupancyForm').reset();
  clearOccSelectedResident();
  clearOccSelectedUnit();
  document.getElementById('occMoveInDate').value = new Date().toISOString().substring(0, 10);
}

function openCreateOccupancyModal() {
  resetOccupancyForm();
  openModal('occupancyModal');
}
