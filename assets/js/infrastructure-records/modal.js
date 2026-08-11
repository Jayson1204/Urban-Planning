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

// ---- Urban project picker ----
function selectInfraProject(project) {
  selectedInfraProjectId = project.project_id;
  document.getElementById('infraProjectId').value = project.project_id;
  document.getElementById('infraProjectSearch').classList.add('hidden');
  document.getElementById('infraProjectResults').classList.add('hidden');
  const badge = document.getElementById('infraSelectedProjectBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('infraSelectedProjectLabel').innerText = `${project.project_code} — ${project.project_title}`;
}

function clearSelectedInfraProject() {
  selectedInfraProjectId = null;
  document.getElementById('infraProjectId').value = '';
  document.getElementById('infraProjectSearch').value = '';
  document.getElementById('infraProjectSearch').classList.remove('hidden');
  const badge = document.getElementById('infraSelectedProjectBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

function resetInfraForm() {
  document.getElementById('infraForm').reset();
  document.getElementById('infraIdRef').value = '';
  clearSelectedInfraProject();
  document.getElementById('infraType').value = 'Road';
  document.getElementById('infraCondition').value = 'Good';
}

function openCreateInfraModal() {
  resetInfraForm();
  document.getElementById('infraModalTitle').innerText = 'Add Infrastructure Record';
  document.getElementById('infraModalIcon').className = 'fa-solid fa-road text-brand-medium';
  openModal('infraModal');
}

async function openEditInfraModal(recordId) {
  const r = await fetchInfraDetail(recordId);
  if (!r) return;

  resetInfraForm();
  document.getElementById('infraIdRef').value = r.record_id;
  document.getElementById('infraName').value = r.infrastructure_name || '';
  document.getElementById('infraType').value = r.infrastructure_type || 'Other';
  document.getElementById('infraCondition').value = r.condition_status || 'Good';
  document.getElementById('infraBarangay').value = r.barangay || '';
  document.getElementById('infraCompletionDate').value = r.completion_date || '';
  document.getElementById('infraLocationDetails').value = r.location_details || '';
  document.getElementById('infraRemarks').value = r.remarks || '';

  if (r.project_id) {
    selectInfraProject({ project_id: r.project_id, project_code: r.project_code, project_title: r.project_title });
  }

  document.getElementById('infraModalTitle').innerText = 'Edit Infrastructure Record';
  document.getElementById('infraModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('infraModal');
}
