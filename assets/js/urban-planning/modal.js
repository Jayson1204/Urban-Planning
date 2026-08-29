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

function resetPlanForm() {
  document.getElementById('planForm').reset();
  document.getElementById('planIdRef').value = '';
  document.getElementById('planType').value = 'Other';
  document.getElementById('planStatus').value = 'Draft';
}

function openCreatePlanModal() {
  resetPlanForm();
  document.getElementById('planModalTitle').innerText = 'Add Development Plan';
  document.getElementById('planModalIcon').className = 'fa-solid fa-map-location-dot text-brand-medium';
  openModal('planModal');
}

async function openEditPlanModal(planId) {
  const plan = await fetchDevelopmentPlanDetail(planId);
  if (!plan) return;

  resetPlanForm();
  document.getElementById('planIdRef').value = plan.plan_id;
  document.getElementById('planCode').value = plan.plan_code || '';
  document.getElementById('planTitle').value = plan.plan_title || '';
  document.getElementById('planType').value = plan.plan_type || 'Other';
  document.getElementById('planStatus').value = plan.plan_status || 'Draft';
  document.getElementById('planBarangay').value = plan.barangay || '';
  document.getElementById('planCoverageArea').value = plan.coverage_area || '';
  document.getElementById('planLeadDepartment').value = plan.lead_department || '';
  document.getElementById('planStartDate').value = plan.start_date || '';
  document.getElementById('planEndDate').value = plan.end_date || '';
  document.getElementById('planBudget').value = plan.budget_allocation ?? '';
  document.getElementById('planDescription').value = plan.description || '';

  document.getElementById('planModalTitle').innerText = 'Edit Development Plan';
  document.getElementById('planModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('planModal');
}

async function openViewPlanModal(planId) {
  const plan = await fetchDevelopmentPlanDetail(planId);
  if (!plan) return;

  document.getElementById('viewPlanCode').innerText =
    `${plan.plan_code}${plan.plan_title ? ' — ' + plan.plan_title : ''}`;
  document.getElementById('viewPlanMeta').innerText =
    [plan.plan_type, plan.plan_status, plan.status].filter(Boolean).join(' • ') || 'No additional details on file.';

  const coverage = [plan.coverage_area, plan.barangay].filter(Boolean).join(', ');
  document.getElementById('viewPlanCoverage').innerText = coverage || '—';
  document.getElementById('viewPlanBudget').innerHTML = formatPeso(plan.budget_allocation);

  const startDisplay = formatPlanDate(plan.start_date);
  const endDisplay = formatPlanDate(plan.end_date);
  document.getElementById('viewPlanTimeline').innerText = (startDisplay || endDisplay)
    ? `${startDisplay || '—'} → ${endDisplay || '—'}`
    : '—';

  document.getElementById('viewPlanDepartment').innerText = plan.lead_department || '—';

  const descEl = document.getElementById('viewPlanDescription');
  descEl.innerText = plan.description && plan.description.trim() ? plan.description : 'No description on file.';

  document.getElementById('uploadPlanId').value = plan.plan_id;
  document.getElementById('planDocumentUploadForm').classList.add('hidden');
  await renderPlanDocuments(plan.plan_id);

  openModal('viewPlanModal');
}

function openPlanDocumentUpload() {
  document.getElementById('planDocumentUploadForm').classList.remove('hidden');
}

async function renderPlanDocuments(planId) {
  const docsContainer = document.getElementById('viewPlanDocuments');
  const docs = await fetchPlanDocuments(planId);
  docsContainer.innerHTML = docs.length
    ? docs.map(d => `
        <div class="px-4 py-2.5 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i class="fa-solid fa-file-lines text-slate-400"></i>
            <div>
              <span class="font-semibold text-slate-700 block">${escapeHtml(d.document_type)}</span>
              <a href="../../${escapeHtml(d.file_path)}" target="_blank" class="text-[10px] text-brand-dark hover:underline">${escapeHtml(d.file_name)}</a>
            </div>
          </div>
          <button onclick="handleDeletePlanDocument(${d.document_id}, ${planId})" class="text-slate-400 hover:text-red-500 cursor-pointer" title="Delete document">
            <i class="fa-solid fa-trash-can text-xs"></i>
          </button>
        </div>
      `).join('')
    : '<div class="px-4 py-3 text-slate-400">No documents uploaded yet.</div>';
}
