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

function resetHousingForm() {
  document.getElementById('housingForm').reset();
  document.getElementById('housingIdRef').value = '';
  document.getElementById('housingUnitType').value = 'Single Detached';
  document.getElementById('housingOccupancyStatus').value = 'Vacant';
}

function openCreateHousingModal() {
  resetHousingForm();
  document.getElementById('housingModalTitle').innerText = 'Add Housing Unit';
  document.getElementById('housingModalIcon').className = 'fa-solid fa-house-circle-check text-brand-medium';
  openModal('housingModal');
}

async function openEditHousingModal(unitId) {
  const unit = await fetchHousingUnitDetail(unitId);
  if (!unit) return;

  resetHousingForm();
  document.getElementById('housingIdRef').value = unit.unit_id;
  document.getElementById('housingUnitCode').value = unit.unit_code || '';
  document.getElementById('housingProjectName').value = unit.project_name || '';
  document.getElementById('housingUnitType').value = unit.unit_type || 'Other';
  document.getElementById('housingFloorArea').value = unit.floor_area ?? '';
  document.getElementById('housingBedrooms').value = unit.bedrooms ?? '';
  document.getElementById('housingOccupancyStatus').value = unit.occupancy_status || 'Vacant';
  document.getElementById('housingBarangay').value = unit.barangay || '';
  document.getElementById('housingStreetAddress').value = unit.street_address || '';
  document.getElementById('housingAmortization').value = unit.monthly_amortization ?? '';
  document.getElementById('housingRemarks').value = unit.remarks || '';

  document.getElementById('housingModalTitle').innerText = 'Edit Housing Unit';
  document.getElementById('housingModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('housingModal');
}

async function openViewHousingModal(unitId) {
  const unit = await fetchHousingUnitDetail(unitId);
  if (!unit) return;

  document.getElementById('viewHousingCode').innerText =
    `${unit.unit_code}${unit.project_name ? ' — ' + unit.project_name : ''}`;
  document.getElementById('viewHousingMeta').innerText =
    [unit.unit_type, unit.occupancy_status, unit.status].filter(Boolean).join(' • ') || 'No additional details on file.';

  const location = [unit.street_address, unit.barangay].filter(Boolean).join(', ');
  document.getElementById('viewHousingLocation').innerText = location || '—';
  document.getElementById('viewHousingAmortization').innerHTML = formatPeso(unit.monthly_amortization);
  document.getElementById('viewHousingFloorArea').innerText = unit.floor_area ? `${unit.floor_area} sqm` : '—';
  document.getElementById('viewHousingBedrooms').innerText = (unit.bedrooms !== null && unit.bedrooms !== undefined && unit.bedrooms !== '') ? unit.bedrooms : '—';

  const remarksEl = document.getElementById('viewHousingRemarks');
  remarksEl.innerText = unit.remarks && unit.remarks.trim() ? unit.remarks : 'No remarks on file.';

  const historyEl = document.getElementById('viewHousingHistory');
  historyEl.innerHTML = '<div class="px-4 py-3 text-slate-400">Loading history...</div>';
  openModal('viewHousingModal');

  const history = await fetchHousingUnitHistory(unitId);
  historyEl.innerHTML = history.length
    ? history.map(ev => `
        <div class="px-4 py-2.5">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-700">${ev.type}</span>
            <span class="text-slate-400 font-mono text-[10px]">${ev.date || ''}</span>
          </div>
          <p class="text-slate-500 mt-0.5">${ev.description}</p>
        </div>
      `).join('')
    : '<div class="px-4 py-3 text-slate-400">No recorded history for this unit yet.</div>';
}
