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

function clearSelectedHousehold() {
  selectedHouseholdId = null;
  document.getElementById('residentHouseholdId').value = '';
  document.getElementById('residentHouseholdSearch').value = '';
  document.getElementById('selectedHouseholdBadge').classList.add('hidden');
  document.getElementById('selectedHouseholdBadge').classList.remove('flex');
  document.getElementById('residentHouseholdSearch').classList.remove('hidden');
  document.getElementById('relationshipToHeadWrapper').classList.add('hidden');
  document.getElementById('residentRelationship').value = '';
}

function selectHousehold(household) {
  selectedHouseholdId = household.household_id;
  document.getElementById('residentHouseholdId').value = household.household_id;
  document.getElementById('residentHouseholdSearch').classList.add('hidden');
  document.getElementById('householdSearchResults').classList.add('hidden');
  const badge = document.getElementById('selectedHouseholdBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('selectedHouseholdLabel').innerText =
    `${household.household_number ? 'HH ' + household.household_number + ' — ' : ''}${household.barangay}, ${household.street_address}`;
  document.getElementById('relationshipToHeadWrapper').classList.remove('hidden');
}

function resetResidentForm() {
  document.getElementById('residentForm').reset();
  document.getElementById('residentIdRef').value = '';
  clearSelectedHousehold();
  document.getElementById('relationshipToHeadWrapper').classList.add('hidden');
}

function openCreateResidentModal() {
  resetResidentForm();
  document.getElementById('residentModalTitle').innerText = 'Add Resident';
  document.getElementById('residentModalIcon').className = 'fa-solid fa-user-plus text-brand-medium';
  openModal('residentModal');
}

async function openEditResidentModal(residentId) {
  const resident = await fetchResidentDetail(residentId);
  if (!resident) return;

  resetResidentForm();
  document.getElementById('residentIdRef').value = resident.resident_id;
  document.getElementById('residentFirstName').value = resident.first_name || '';
  document.getElementById('residentMiddleName').value = resident.middle_name || '';
  document.getElementById('residentLastName').value = resident.last_name || '';
  document.getElementById('residentSuffix').value = resident.suffix || '';
  document.getElementById('residentBirthDate').value = resident.birth_date || '';
  document.getElementById('residentGender').value = resident.gender || '';
  document.getElementById('residentCivilStatus').value = resident.civil_status || '';
  document.getElementById('residentContactNumber').value = resident.contact_number || '';
  document.getElementById('residentEmail').value = resident.email || '';
  document.getElementById('residentOccupation').value = resident.occupation || '';
  document.getElementById('residentBarangay').value = resident.barangay || '';
  document.getElementById('residentStreetAddress').value = resident.street_address || '';

  if (resident.household_id) {
    selectHousehold({
      household_id: resident.household_id,
      household_number: resident.household_number,
      barangay: resident.household_barangay,
      street_address: resident.household_address,
    });
    document.getElementById('residentRelationship').value = resident.relationship_to_head || '';
  }

  document.getElementById('residentModalTitle').innerText = 'Edit Resident';
  document.getElementById('residentModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('residentModal');
}

async function openViewResidentModal(residentId) {
  const resident = await fetchResidentDetail(residentId);
  if (!resident) return;

  document.getElementById('viewResidentName').innerText = fullName(resident);
  document.getElementById('viewResidentMeta').innerText =
    [resident.gender, resident.civil_status, resident.occupation].filter(Boolean).join(' • ') || 'No additional details on file.';
  document.getElementById('viewResidentContact').innerText = resident.contact_number || resident.email || '—';
  document.getElementById('viewResidentHousehold').innerText = resident.household_id
    ? `${resident.household_barangay || ''}${resident.household_number ? ' (HH ' + resident.household_number + ')' : ''}`
    : (resident.barangay || 'Not part of a household');

  const membersContainer = document.getElementById('viewHouseholdMembers');
  const members = (resident.household_members || []).filter(m => m.resident_id !== resident.resident_id);
  membersContainer.innerHTML = members.length
    ? members.map(m => `
        <div class="px-4 py-2.5 flex items-center justify-between">
          <span class="font-semibold text-slate-700">${m.first_name} ${m.last_name}</span>
          <span class="text-[10px] text-slate-400 font-bold uppercase">${m.relationship_to_head || ''}</span>
        </div>
      `).join('')
    : '<div class="px-4 py-3 text-slate-400">Not part of a household.</div>';

  const docsContainer = document.getElementById('viewResidentDocuments');
  const docs = resident.documents || [];
  docsContainer.innerHTML = docs.length
    ? docs.map(d => `
        <div class="px-4 py-2.5 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-file-lines text-slate-400"></i>
            <div>
              <span class="font-semibold text-slate-700 block">${d.document_type}</span>
              <a href="../../${d.file_path}" target="_blank" class="text-[10px] text-brand-dark hover:underline">${d.file_name}</a>
            </div>
          </div>
          <button onclick="handleDeleteDocument(${d.document_id}, ${resident.resident_id})" class="text-slate-400 hover:text-red-500 cursor-pointer" title="Delete document">
            <i class="fa-solid fa-trash-can text-xs"></i>
          </button>
        </div>
      `).join('')
    : '<div class="px-4 py-3 text-slate-400">No documents uploaded yet.</div>';

  document.getElementById('uploadResidentId').value = resident.resident_id;
  document.getElementById('documentUploadForm').classList.add('hidden');

  openModal('viewResidentModal');
}

function openDocumentUpload() {
  document.getElementById('documentUploadForm').classList.remove('hidden');
}
