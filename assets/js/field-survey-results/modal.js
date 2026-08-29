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

function resetResultForm() {
  document.getElementById('resultForm').reset();
  document.getElementById('resultIdRef').value = '';
  document.getElementById('resultAssignmentId').disabled = false;
}

function openCreateResultModal() {
  resetResultForm();
  document.getElementById('resultModalTitle').innerText = 'Record Result';
  document.getElementById('resultModalIcon').className = 'fa-solid fa-file-circle-check text-brand-medium';
  openModal('resultModal');
}

async function openEditResultModal(resultId) {
  const r = await fetchResultDetail(resultId);
  if (!r) return;

  resetResultForm();
  document.getElementById('resultIdRef').value = r.result_id;

  const select = document.getElementById('resultAssignmentId');
  if (![...select.options].some(o => o.value == r.assignment_id)) {
    const opt = document.createElement('option');
    opt.value = r.assignment_id;
    opt.innerText = `${r.form_code || ''} — ${r.subject_name || 'Unnamed subject'} (${r.subject_type || ''})`;
    select.appendChild(opt);
  }
  select.value = r.assignment_id;
  select.disabled = true;

  document.getElementById('resultSurveyDate').value = r.survey_date || '';
  document.getElementById('resultConditionRating').value = r.condition_rating || '';
  document.getElementById('resultPopulationCount').value = r.population_count ?? '';
  document.getElementById('resultIncomeBracket').value = r.income_bracket || '';
  document.getElementById('resultFindings').value = r.findings || '';
  document.getElementById('resultRecommendations').value = r.recommendations || '';
  document.getElementById('resultAdditionalNotes').value = r.additional_notes || '';

  document.getElementById('resultModalTitle').innerText = 'Edit Result';
  document.getElementById('resultModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('resultModal');
}

function renderResultPhotos(photos) {
  const container = document.getElementById('viewResultPhotos');
  if (!photos || !photos.length) {
    container.innerHTML = '<div class="col-span-3 px-4 py-3 text-slate-400 text-xs border border-slate-200/60 rounded-xl">No photos uploaded yet.</div>';
    return;
  }
  container.innerHTML = photos.map(p => `
    <div class="relative group rounded-xl overflow-hidden border border-slate-200/60 aspect-square">
      <img src="../../${escapeHtml(p.file_path)}" alt="${escapeHtml(p.caption) || 'Survey photo'}" class="w-full h-full object-cover">
      <button type="button" onclick="handleDeletePhoto(${p.photo_id}, ${p.result_id})" class="absolute top-1 right-1 h-6 w-6 rounded-full bg-slate-900/70 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-pointer" title="Delete photo">
        <i class="fa-solid fa-xmark text-[10px]"></i>
      </button>
      ${p.caption ? `<span class="absolute bottom-0 left-0 right-0 bg-slate-900/70 text-white text-[9px] px-1.5 py-1 truncate">${escapeHtml(p.caption)}</span>` : ''}
    </div>
  `).join('');
}

async function openViewResultModal(resultId) {
  const r = await fetchResultDetail(resultId);
  if (!r) return;

  document.getElementById('viewResultForm').innerText =
    `${r.form_code || ''}${r.form_title ? ' — ' + r.form_title : ''}`;
  document.getElementById('viewResultMeta').innerText =
    [r.subject_name, r.subject_type, r.condition_rating, r.survey_date].filter(Boolean).join(' • ') || 'No additional details on file.';

  const findingsEl = document.getElementById('viewResultFindings');
  findingsEl.innerText = r.findings && r.findings.trim() ? r.findings : 'No findings on file.';

  const recsEl = document.getElementById('viewResultRecommendations');
  recsEl.innerText = r.recommendations && r.recommendations.trim() ? r.recommendations : 'No recommendations on file.';

  document.getElementById('uploadResultId').value = r.result_id;
  document.getElementById('photoUploadForm').classList.add('hidden');
  renderResultPhotos(r.photos || []);

  openModal('viewResultModal');
}

function openPhotoUpload() {
  document.getElementById('photoUploadForm').classList.remove('hidden');
}
