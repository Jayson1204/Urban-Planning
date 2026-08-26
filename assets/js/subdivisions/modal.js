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

function resetSubdivisionForm() {
  document.getElementById('subdivisionForm').reset();
  document.getElementById('subdivisionIdRef').value = '';
}

function openCreateSubdivisionModal() {
  resetSubdivisionForm();
  document.getElementById('subdivisionModalTitle').innerText = 'Add Subdivision';
  document.getElementById('subdivisionModalIcon').className = 'fa-solid fa-city text-brand-medium';
  openModal('subdivisionModal');
}

async function openEditSubdivisionModal(subdivisionId) {
  const s = await fetchSubdivisionDetail(subdivisionId);
  if (!s) return;

  resetSubdivisionForm();
  document.getElementById('subdivisionIdRef').value = s.subdivision_id;
  document.getElementById('subdivisionName').value = s.name || '';
  document.getElementById('subdivisionBarangayId').value = s.barangay_id || '';
  document.getElementById('subdivisionType').value = s.subdivision_type || '';
  document.getElementById('subdivisionStatusText').value = s.subdivision_status || '';
  document.getElementById('subdivisionSource').value = s.source || '';
  document.getElementById('subdivisionLatitude').value = s.latitude ?? '';
  document.getElementById('subdivisionLongitude').value = s.longitude ?? '';
  document.getElementById('subdivisionBoundary').value = s.boundary_geojson || '';
  document.getElementById('subdivisionDescription').value = s.description || '';

  document.getElementById('subdivisionModalTitle').innerText = 'Edit Subdivision';
  document.getElementById('subdivisionModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('subdivisionModal');
}
