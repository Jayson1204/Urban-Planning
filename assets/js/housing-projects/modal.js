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

function resetHpForm() {
  document.getElementById('hpForm').reset();
  document.getElementById('hpIdRef').value = '';
}

function openCreateHousingProjectModal() {
  resetHpForm();
  document.getElementById('hpModalTitle').innerText = 'Add Housing Project';
  document.getElementById('hpModalIcon').className = 'fa-solid fa-building-shield text-brand-medium';
  openModal('hpModal');
}

async function openEditHousingProjectModal(id) {
  const p = await fetchHousingProjectDetail(id);
  if (!p) return;

  resetHpForm();
  document.getElementById('hpIdRef').value = p.housing_project_id;
  document.getElementById('hpName').value = p.name || '';
  document.getElementById('hpBarangayId').value = p.barangay_id || '';
  document.getElementById('hpDeveloper').value = p.developer || '';
  document.getElementById('hpUnits').value = p.units ?? '';
  document.getElementById('hpProjectStatus').value = p.project_status || '';
  document.getElementById('hpSource').value = p.source || '';
  document.getElementById('hpLatitude').value = p.latitude ?? '';
  document.getElementById('hpLongitude').value = p.longitude ?? '';
  document.getElementById('hpBoundary').value = p.boundary_geojson || '';
  document.getElementById('hpDescription').value = p.description || '';

  document.getElementById('hpModalTitle').innerText = 'Edit Housing Project';
  document.getElementById('hpModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('hpModal');
}
