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

function zcResidentFullName(r) {
  const mid = r.middle_name ? ` ${r.middle_name}` : '';
  const suf = r.suffix ? ` ${r.suffix}` : '';
  return `${r.first_name || ''}${mid} ${r.last_name || ''}${suf}`.trim();
}

// ---- Resident (applicant) picker ----
function selectZcResident(resident) {
  selectedZcResidentId = resident.resident_id;
  document.getElementById('zcResidentId').value = resident.resident_id;
  document.getElementById('zcResidentSearch').classList.add('hidden');
  document.getElementById('zcResidentResults').classList.add('hidden');
  const badge = document.getElementById('zcSelectedResidentBadge');
  badge.classList.remove('hidden');
  badge.classList.add('flex');
  const brgy = resident.barangay ? ` • ${resident.barangay}` : '';
  document.getElementById('zcSelectedResidentLabel').innerText = `${zcResidentFullName(resident)}${brgy}`;
}

function clearSelectedZcResident() {
  selectedZcResidentId = null;
  document.getElementById('zcResidentId').value = '';
  document.getElementById('zcResidentSearch').value = '';
  document.getElementById('zcResidentSearch').classList.remove('hidden');
  const badge = document.getElementById('zcSelectedResidentBadge');
  badge.classList.add('hidden');
  badge.classList.remove('flex');
}

// ---- Live conformity pre-screening preview ----
let zcPreviewDebounce = null;
function refreshConformityPreview() {
  clearTimeout(zcPreviewDebounce);
  zcPreviewDebounce = setTimeout(async () => {
    const box = document.getElementById('zcConformityPreview');
    const params = {
      zone_classification: document.getElementById('zcZoneClassification').value,
      use_category: document.getElementById('zcUseCategory').value,
      proposed_height_m: document.getElementById('zcHeight').value,
      proposed_setback_m: document.getElementById('zcSetback').value,
      proposed_far: document.getElementById('zcFar').value,
      proposed_lot_occupancy_pct: document.getElementById('zcLotOccupancy').value,
    };
    const result = await fetchConformityPreview(params);
    if (!result) return;

    const colorMap = {
      'Conforming': 'border-emerald-200 bg-emerald-50 text-emerald-700',
      'Non-Conforming': 'border-rose-200 bg-rose-50 text-rose-700',
      'Needs Manual Review': 'border-amber-200 bg-amber-50 text-amber-700',
    };
    box.className = `mt-3 border rounded-xl px-3.5 py-3 text-[11px] leading-relaxed ${colorMap[result.result] || 'border-slate-200 bg-slate-50 text-slate-500'}`;
    box.innerText = `${result.result}: ${result.notes}`;
  }, 300);
}

function resetClearanceForm() {
  document.getElementById('zcForm').reset();
  document.getElementById('zcIdRef').value = '';
  clearSelectedZcResident();
  document.getElementById('zcZoneClassification').value = 'Residential-1';
  document.getElementById('zcUseCategory').value = 'Residential Dwelling';
  document.getElementById('zcConformityPreview').innerText = 'Fill in the zone, use, and project figures above to see a live conformity pre-screening result.';
  document.getElementById('zcConformityPreview').className = 'mt-3 border rounded-xl px-3.5 py-3 text-[11px] leading-relaxed border-slate-200 bg-slate-50 text-slate-500';
}

function openCreateClearanceModal() {
  resetClearanceForm();
  document.getElementById('zcModalTitle').innerText = 'New Zoning Clearance Application';
  document.getElementById('zcModalIcon').className = 'fa-solid fa-stamp text-brand-medium';
  openModal('zcModal');
}

async function openEditClearanceModal(clearanceId) {
  const zc = await fetchClearanceDetail(clearanceId);
  if (!zc) return;

  resetClearanceForm();
  document.getElementById('zcIdRef').value = zc.clearance_id;

  selectZcResident({
    resident_id: zc.resident_id,
    first_name: (zc.applicant_name || '').split(' ')[0],
    last_name: (zc.applicant_name || '').split(' ').slice(1).join(' '),
    barangay: zc.applicant_barangay,
  });
  document.getElementById('zcSelectedResidentLabel').innerText = zc.applicant_name || 'Applicant';

  document.getElementById('zcZoneClassification').value = zc.zone_classification || 'Residential-1';
  document.getElementById('zcUseCategory').value = zc.use_category || 'Residential Dwelling';
  document.getElementById('zcBarangay').value = zc.barangay || '';
  document.getElementById('zcStreetAddress').value = zc.street_address || '';
  document.getElementById('zcProjectDescription').value = zc.project_description || '';
  document.getElementById('zcLotArea').value = zc.lot_area_sqm ?? '';
  document.getElementById('zcHeight').value = zc.proposed_height_m ?? '';
  document.getElementById('zcSetback').value = zc.proposed_setback_m ?? '';
  document.getElementById('zcFar').value = zc.proposed_far ?? '';
  document.getElementById('zcLotOccupancy').value = zc.proposed_lot_occupancy_pct ?? '';

  document.getElementById('zcModalTitle').innerText = 'Edit Zoning Clearance Application';
  document.getElementById('zcModalIcon').className = 'fa-solid fa-pen text-brand-medium';
  openModal('zcModal');
  refreshConformityPreview();
}

function zcTimelineEntry(review) {
  const who = [review.reviewer_name, review.reviewer_role].filter(Boolean).join(' — ');
  return `
    <div class="px-4 py-3">
      <div class="flex items-center justify-between">
        <span class="font-bold text-slate-800">${review.action || ''}</span>
        <span class="text-[10px] text-slate-400 font-mono">${review.created_at || ''}</span>
      </div>
      <p class="text-slate-500 mt-0.5">${who || 'System'}</p>
      ${review.remarks ? `<p class="text-slate-600 mt-1">${review.remarks}</p>` : ''}
    </div>
  `;
}

async function openViewClearanceModal(clearanceId) {
  const zc = await fetchClearanceDetail(clearanceId);
  if (!zc) return;

  document.getElementById('viewZcReference').innerText = zc.reference_number || '—';
  document.getElementById('viewZcMeta').innerText =
    [zc.applicant_name, zc.zone_classification, zc.use_category].filter(Boolean).join(' • ') || 'No additional details on file.';
  document.getElementById('viewZcConformity').innerText = zc.conformity_result || 'Pending';
  document.getElementById('viewZcFee').innerHTML = `${zcFormatPeso(zc.fee_amount)} <span class="text-slate-400 font-semibold">(${zc.payment_status || 'Unpaid'})</span>`;
  document.getElementById('viewZcConformityNotes').innerText = zc.conformity_notes || 'No conformity notes on file.';

  const certLink = document.getElementById('viewZcCertificateLink');
  if (zc.clearance_status === 'Approved' || zc.clearance_status === 'Denied') {
    document.getElementById('viewZcCertificateHref').href = `zoning-clearance-certificate.php?id=${zc.clearance_id}`;
    certLink.classList.remove('hidden');
  } else {
    certLink.classList.add('hidden');
  }

  const timeline = document.getElementById('viewZcTimeline');
  if (zc.reviews && zc.reviews.length) {
    timeline.innerHTML = zc.reviews.map(zcTimelineEntry).join('');
  } else {
    timeline.innerHTML = '<div class="px-4 py-3 text-slate-400">No review activity yet.</div>';
  }

  document.getElementById('zcTransitionId').value = zc.clearance_id;
  document.getElementById('zcTransitionStatus').value = '';
  document.getElementById('zcTransitionRole').value = '';
  document.getElementById('zcTransitionRemarks').value = '';

  openModal('viewZcModal');
}
