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

function resetHouseholdForm() {
  document.getElementById('householdForm').reset();
  document.getElementById('householdIdRef').value = '';
  document.getElementById('householdType').value = 'Owned';
}

function openCreateHouseholdModal() {
  resetHouseholdForm();
  document.getElementById('householdModalTitle').innerText = 'Add Household';
  document.getElementById('householdModalIcon').className = 'fa-solid fa-house-chimney text-brand-medium';
  openModal('householdModal');
}

async function openEditHouseholdModal(householdId) {
  const h = await fetchHouseholdDetail(householdId);
  if (!h) return;

  resetHouseholdForm();
  document.getElementById('householdIdRef').value = h.household_id;
  document.getElementById('householdNumber').value = h.household_number || '';
  document.getElementById('householdBarangay').value = h.barangay || '';
  document.getElementById('householdStreetAddress').value = h.street_address || '';
  document.getElementById('householdType').value = h.household_type || 'Owned';

  document.getElementById('householdModalTitle').innerText = 'Edit Household';
  document.getElementById('householdModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('householdModal');
}

async function openViewHouseholdModal(householdId) {
  const h = await fetchHouseholdDetail(householdId);
  if (!h) return;

  document.getElementById('viewHouseholdAddress').innerText =
    `${h.barangay || ''}${h.street_address ? ' — ' + h.street_address : ''}`;
  document.getElementById('viewHouseholdMeta').innerText =
    [h.household_number ? 'HH ' + h.household_number : null, h.household_type, h.status].filter(Boolean).join(' • ');

  const members = h.members || [];
  const membersEl = document.getElementById('viewHouseholdMembersList');
  membersEl.innerHTML = members.length
    ? members.map(m => `
        <div class="px-4 py-2.5 flex items-center justify-between">
          <span class="font-semibold text-slate-700">${escapeHtml(m.first_name || '')} ${m.middle_name ? escapeHtml(m.middle_name) + ' ' : ''}${escapeHtml(m.last_name || '')}</span>
          <span class="text-slate-400 text-[10px] font-black uppercase">${escapeHtml(m.relationship_to_head) || '&mdash;'}</span>
        </div>
      `).join('')
    : '<div class="px-4 py-3 text-slate-400">No members registered under this household.</div>';

  openModal('viewHouseholdModal');
}
