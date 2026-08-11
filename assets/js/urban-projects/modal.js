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

// ---- Development plan picker ----
function selectProjectPlan(plan) {
  selectedProjectPlanId = plan.plan_id;
  document.getElementById('projectPlanId').value = plan.plan_id;
  document.getElementById('projectPlanSearch').classList.add('hidden');
  document.getElementById('projectPlanResults').classList.add('hidden');
  const badge = document.getElementById('projectSelectedPlanBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  document.getElementById('projectSelectedPlanLabel').innerText = `${plan.plan_code} — ${plan.plan_title}`;
}

function clearSelectedProjectPlan() {
  selectedProjectPlanId = null;
  document.getElementById('projectPlanId').value = '';
  document.getElementById('projectPlanSearch').value = '';
  document.getElementById('projectPlanSearch').classList.remove('hidden');
  const badge = document.getElementById('projectSelectedPlanBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

function resetProjectForm() {
  document.getElementById('projectForm').reset();
  document.getElementById('projectIdRef').value = '';
  clearSelectedProjectPlan();
  document.getElementById('projectType').value = 'Road';
  document.getElementById('projectStatus').value = 'Planned';
}

function openCreateProjectModal() {
  resetProjectForm();
  document.getElementById('projectModalTitle').innerText = 'Add Urban Project';
  document.getElementById('projectModalIcon').className = 'fa-solid fa-diagram-project text-brand-medium';
  openModal('projectModal');
}

async function openEditProjectModal(projectId) {
  const p = await fetchProjectDetail(projectId);
  if (!p) return;

  resetProjectForm();
  document.getElementById('projectIdRef').value = p.project_id;
  document.getElementById('projectCode').value = p.project_code || '';
  document.getElementById('projectTitle').value = p.project_title || '';
  document.getElementById('projectType').value = p.project_type || 'Other';
  document.getElementById('projectStatus').value = p.project_status || 'Planned';
  document.getElementById('projectBarangay').value = p.barangay || '';
  document.getElementById('projectCoverageArea').value = p.coverage_area || '';
  document.getElementById('projectContractor').value = p.contractor || '';
  document.getElementById('projectBudget').value = p.budget ?? '';
  document.getElementById('projectStartDate').value = p.start_date || '';
  document.getElementById('projectTargetDate').value = p.target_completion_date || '';
  document.getElementById('projectActualDate').value = p.actual_completion_date || '';
  document.getElementById('projectDescription').value = p.description || '';

  if (p.plan_id) {
    selectProjectPlan({ plan_id: p.plan_id, plan_code: p.plan_code, plan_title: p.plan_title });
  }

  document.getElementById('projectModalTitle').innerText = 'Edit Urban Project';
  document.getElementById('projectModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('projectModal');
}

async function openViewProjectModal(projectId) {
  const p = await fetchProjectDetail(projectId);
  if (!p) return;

  document.getElementById('viewProjectTitle').innerText = `${p.project_code} — ${p.project_title}`;
  document.getElementById('viewProjectMeta').innerText =
    [p.project_type, p.project_status, p.barangay].filter(Boolean).join(' • ') || 'No additional details on file.';
  document.getElementById('viewProjectPlan').innerText = p.plan_code ? `${p.plan_code} — ${p.plan_title}` : 'Unlinked';
  document.getElementById('viewProjectBudget').innerHTML = formatPeso(p.budget);
  document.getElementById('viewProjectContractor').innerText = p.contractor || '—';

  const timelineParts = [p.start_date, p.target_completion_date].filter(Boolean);
  document.getElementById('viewProjectTimeline').innerText = timelineParts.length ? timelineParts.join(' → ') : '—';

  const descEl = document.getElementById('viewProjectDescription');
  descEl.innerText = p.description && p.description.trim() ? p.description : 'No description on file.';

  openModal('viewProjectModal');
}
