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

function paResidentFullName(r) {
  const mid = r.middle_name ? ` ${r.middle_name}` : '';
  const suf = r.suffix ? ` ${r.suffix}` : '';
  return `${r.first_name || ''}${mid} ${r.last_name || ''}${suf}`.trim();
}

// ---- Resident (applicant) picker ----
function selectPaResident(resident) {
  selectedPaResidentId = resident.resident_id;
  document.getElementById('paResidentId').value = resident.resident_id;
  document.getElementById('paResidentSearch').classList.add('hidden');
  document.getElementById('paResidentResults').classList.add('hidden');
  const badge = document.getElementById('paSelectedResidentBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  const brgy = resident.barangay ? ` • ${resident.barangay}` : '';
  document.getElementById('paSelectedResidentLabel').innerText = `${paResidentFullName(resident)}${brgy}`;
}

function clearSelectedPaResident() {
  selectedPaResidentId = null;
  document.getElementById('paResidentId').value = '';
  document.getElementById('paResidentSearch').value = '';
  document.getElementById('paResidentSearch').classList.remove('hidden');
  const badge = document.getElementById('paSelectedResidentBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

// ---- Application type toggles which project figure fields are shown ----
function togglePaTypeFields() {
  const type = document.getElementById('paApplicationType').value;
  const isSubdivision = type === 'Subdivision Plan';
  document.getElementById('paLotsField').classList.toggle('hidden', !isSubdivision);
  document.getElementById('paFloorAreaField').classList.toggle('hidden', isSubdivision);
  document.getElementById('paStoreysField').classList.toggle('hidden', isSubdivision);
}

function resetApplicationForm() {
  document.getElementById('paForm').reset();
  document.getElementById('paIdRef').value = '';
  clearSelectedPaResident();
  document.getElementById('paApplicationType').disabled = false;
  document.getElementById('paApplicationType').value = 'Building Permit';
  togglePaTypeFields();
}

function openCreatePermitModal() {
  resetApplicationForm();
  document.getElementById('paModalTitle').innerText = 'New Permit Application';
  document.getElementById('paModalIcon').className = 'fa-solid fa-building-shield text-brand-medium';
  openModal('paModal');
}

async function openEditApplicationModal(applicationId) {
  const pa = await fetchApplicationDetail(applicationId);
  if (!pa) return;

  resetApplicationForm();
  document.getElementById('paIdRef').value = pa.application_id;

  selectPaResident({
    resident_id: pa.resident_id,
    first_name: (pa.applicant_name || '').split(' ')[0],
    last_name: (pa.applicant_name || '').split(' ').slice(1).join(' '),
    barangay: pa.applicant_barangay,
  });
  document.getElementById('paSelectedResidentLabel').innerText = pa.applicant_name || 'Applicant';

  document.getElementById('paApplicationType').value = pa.application_type || 'Building Permit';
  document.getElementById('paApplicationType').disabled = true;
  togglePaTypeFields();
  document.getElementById('paProjectName').value = pa.project_name || '';
  document.getElementById('paBarangay').value = pa.barangay || '';
  document.getElementById('paStreetAddress').value = pa.street_address || '';
  document.getElementById('paProjectDescription').value = pa.project_description || '';
  document.getElementById('paLotArea').value = pa.lot_area_sqm ?? '';
  document.getElementById('paFloorArea').value = pa.floor_area_sqm ?? '';
  document.getElementById('paStoreys').value = pa.number_of_storeys ?? '';
  document.getElementById('paLots').value = pa.number_of_lots ?? '';
  document.getElementById('paProjectCost').value = pa.estimated_project_cost ?? '';

  document.getElementById('paModalTitle').innerText = 'Edit Permit Application';
  document.getElementById('paModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('paModal');
}

function paDisciplineStatusColor(status) {
  const map = {
    'Pending': 'border-slate-200 bg-slate-50 text-slate-500',
    'Under Review': 'border-cyan-200 bg-cyan-50 text-cyan-700',
    'Returned for Revision': 'border-amber-200 bg-amber-50 text-amber-700',
    'Approved': 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'Rejected': 'border-rose-200 bg-rose-50 text-rose-700',
  };
  return map[status] || 'border-slate-200 bg-slate-50 text-slate-500';
}

function renderPaDisciplines(pa) {
  const container = document.getElementById('viewPaDisciplines');
  const reviews = pa.discipline_reviews || [];
  if (!reviews.length) {
    container.innerHTML = '<div class="text-slate-400 text-[11px] col-span-2">No discipline reviews on file.</div>';
    return;
  }

  container.innerHTML = reviews.map(d => `
    <div class="border rounded-xl p-3 space-y-2 ${paDisciplineStatusColor(d.review_status)}">
      <div class="flex items-center justify-between">
        <span class="font-black text-[11px]">${escapeHtml(d.discipline)}</span>
        <span class="text-[9px] font-black uppercase">${escapeHtml(d.review_status)}</span>
      </div>
      ${d.remarks ? `<p class="text-[10px] leading-relaxed opacity-90">${escapeHtml(d.remarks)}</p>` : ''}
      ${d.reviewer_name ? `<p class="text-[9px] opacity-70">${escapeHtml(d.reviewer_name)}${d.reviewed_at ? ' • ' + escapeHtml(d.reviewed_at.substring(0, 10)) : ''}</p>` : ''}
      <form onsubmit="handlePaDisciplineFormSubmit(event, ${pa.application_id}, '${d.discipline}')" class="flex items-center gap-1.5 pt-1">
        <select class="border border-white/60 bg-white/70 rounded-md px-1.5 py-1 text-[10px] flex-1 focus:outline-none">
          <option value="Under Review">Under Review</option>
          <option value="Returned for Revision">Returned for Revision</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
        </select>
        <input type="text" placeholder="Remarks" required class="border border-white/60 bg-white/70 rounded-md px-1.5 py-1 text-[10px] flex-1 focus:outline-none">
        <button type="submit" class="bg-slate-900 text-white rounded-md px-2 py-1 text-[10px] font-bold cursor-pointer">Set</button>
      </form>
    </div>
  `).join('');
}

function handlePaDisciplineFormSubmit(e, applicationId, discipline) {
  e.preventDefault();
  const form = e.target;
  const status = form.querySelector('select').value;
  const remarks = form.querySelector('input[type="text"]').value.trim();
  handleTransitionDiscipline(applicationId, discipline, status, remarks);
}

function paDocStatusTag(status) {
  const map = {
    'Current': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Superseded': 'bg-slate-50 text-slate-400 border-slate-200',
    'Archived': 'bg-slate-50 text-slate-400 border-slate-200',
  };
  return map[status] || 'bg-slate-50 text-slate-500 border-slate-200';
}

function renderPaDocuments(pa) {
  const container = document.getElementById('viewPaDocuments');
  const docs = pa.plan_documents || [];
  document.getElementById('uploadPaApplicationId').value = pa.application_id;
  document.getElementById('paDocumentUploadForm').classList.add('hidden');

  if (!docs.length) {
    container.innerHTML = '<div class="px-4 py-3 text-slate-400">No plan documents uploaded yet.</div>';
    return;
  }

  container.innerHTML = docs.map(d => `
    <div class="px-4 py-3 flex items-start justify-between gap-3">
      <div class="min-w-0 space-y-1">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="font-semibold text-slate-700">${escapeHtml(d.document_type)} <span class="text-slate-400 font-normal">v${escapeHtml(d.version_number)}</span></span>
          <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full border ${paDocStatusTag(d.document_status)}">${escapeHtml(d.document_status)}</span>
        </div>
        <a href="../../${escapeHtml(d.file_path)}" target="_blank" rel="noopener" class="text-[11px] text-brand-dark hover:underline break-all">${escapeHtml(d.file_name)}</a>
        <p class="text-[9px] text-slate-400">${d.submitted_by === 'Applicant' ? 'Submitted by applicant' : 'Uploaded by staff'} • ${escapeHtml((d.uploaded_at || '').substring(0, 10))}</p>
      </div>
      <button onclick="handleDeletePaDocument(${d.document_id}, ${pa.application_id})" class="text-slate-400 hover:text-red-500 cursor-pointer p-1.5 shrink-0" title="Delete document">
        <i class="fa-solid fa-trash-can text-xs"></i>
      </button>
    </div>
  `).join('');
}

function openPaDocumentUpload() {
  document.getElementById('paDocumentUploadForm').classList.remove('hidden');
}

function paTimelineEntry(review) {
  const who = escapeHtml([review.reviewer_name, review.discipline || review.reviewer_role].filter(Boolean).join(' — '));
  const roundTag = review.resubmission_round > 0 ? `<span class="text-[9px] text-slate-400 font-bold ml-1.5">(Round ${escapeHtml(review.resubmission_round)})</span>` : '';
  return `
    <div class="px-4 py-3">
      <div class="flex items-center justify-between">
        <span class="font-bold text-slate-800">${escapeHtml(review.action || '')}${roundTag}</span>
        <span class="text-[10px] text-slate-400 font-mono">${escapeHtml(review.created_at || '')}</span>
      </div>
      <p class="text-slate-500 mt-0.5">${who || 'System'}</p>
      ${review.remarks ? `<p class="text-slate-600 mt-1">${escapeHtml(review.remarks)}</p>` : ''}
    </div>
  `;
}

async function openViewApplicationModal(applicationId) {
  const pa = await fetchApplicationDetail(applicationId);
  if (!pa) return;

  document.getElementById('viewPaReference').innerText = pa.reference_number || '—';
  document.getElementById('viewPaMeta').innerText =
    [pa.applicant_name, pa.application_type, pa.project_name].filter(Boolean).join(' • ') || 'No additional details on file.';
  document.getElementById('viewPaConsolidated').innerText = pa.consolidated_result || 'Pending';
  document.getElementById('viewPaFee').innerHTML = `${paFormatPeso(pa.fee_amount)} <span class="text-slate-400 font-semibold">(${escapeHtml(pa.payment_status) || 'Unpaid'})</span>`;

  const certLink = document.getElementById('viewPaCertificateLink');
  if (pa.application_status === 'Permit Issued' || pa.application_status === 'Denied') {
    document.getElementById('viewPaCertificateHref').href = `permit-certificate.php?id=${pa.application_id}`;
    certLink.classList.remove('hidden');
  } else {
    certLink.classList.add('hidden');
  }

  renderPaDisciplines(pa);
  renderPaDocuments(pa);

  const timeline = document.getElementById('viewPaTimeline');
  if (pa.reviews && pa.reviews.length) {
    timeline.innerHTML = pa.reviews.map(paTimelineEntry).join('');
  } else {
    timeline.innerHTML = '<div class="px-4 py-3 text-slate-400">No review activity yet.</div>';
  }

  const resubmitForm = document.getElementById('paResubmitForm');
  if (pa.application_status === 'Returned for Revision') {
    document.getElementById('paResubmitId').value = pa.application_id;
    document.getElementById('paResubmitRemarks').value = '';
    resubmitForm.classList.remove('hidden');
  } else {
    resubmitForm.classList.add('hidden');
  }

  const issueForm = document.getElementById('paIssuePermitForm');
  const allDisciplinesApproved = (pa.discipline_reviews || []).length > 0 &&
    (pa.discipline_reviews || []).every(d => d.review_status === 'Approved');
  if (pa.application_status === 'Approved' && allDisciplinesApproved) {
    document.getElementById('paIssueId').value = pa.application_id;
    document.getElementById('paConditionsOfApproval').value = '';
    document.getElementById('paExpiryDate').value = '';
    issueForm.classList.remove('hidden');
  } else {
    issueForm.classList.add('hidden');
  }

  document.getElementById('paTransitionId').value = pa.application_id;
  document.getElementById('paTransitionStatus').value = '';
  document.getElementById('paTransitionRole').value = '';
  document.getElementById('paTransitionRemarks').value = '';

  openModal('viewPaModal');
}
