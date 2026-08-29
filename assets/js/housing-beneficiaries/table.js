function benStageBadge(stage) {
  const map = {
    'Applicant': 'bg-indigo-50 text-indigo-700 border-indigo-150',
    'Qualified': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Awarded': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Disqualified': 'bg-rose-50 text-rose-700 border-rose-150',
    'Cancelled': 'bg-slate-50 text-slate-500 border-slate-200',
  };
  const cls = map[stage] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${stage || '—'}</span>`;
}

function benFormatPeso(value) {
  if (value === null || value === undefined || value === '') return '&mdash;';
  const num = parseFloat(value);
  if (isNaN(num)) return '&mdash;';
  return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function benScoreBadge(score) {
  if (score === null || score === undefined || score === '') return '<span class="text-slate-400">&mdash;</span>';
  const num = parseFloat(score);
  if (isNaN(num)) return '<span class="text-slate-400">&mdash;</span>';
  const cls = num >= 65 ? 'bg-emerald-50 text-emerald-700 border-emerald-150'
    : num >= 35 ? 'bg-amber-50 text-amber-700 border-amber-150'
    : 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black px-2 py-0.5 rounded-full border ${cls} inline-flex items-center">${num.toFixed(0)}</span>`;
}

function renderBeneficiaries() {
  const tbody = document.getElementById('beneficiariesTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (beneficiariesData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-hand-holding-heart text-3xl mb-3 block opacity-60"></i>
          No beneficiaries matched your query.
        </td>
      </tr>
    `;
    document.getElementById('benPaginationText').innerText = 'Showing 0 to 0 of 0 beneficiaries';
    return;
  }

  beneficiariesData.forEach(b => {
    const isArchived = b.status === 'Archived';
    const unitDisplay = b.unit_id
      ? `<span class="font-bold text-slate-700 font-mono">${escapeHtml(b.unit_code || '')}</span>${b.project_name ? `<br><span class="text-[10px] text-slate-400">${escapeHtml(b.project_name)}</span>` : ''}`
      : '<span class="text-slate-400 italic">Unassigned</span>';

    const archivedTag = isArchived
      ? '<span class="ml-1.5 text-[9px] font-black uppercase text-slate-400">(Archived)</span>'
      : '';

    const row = `
      <tr class="hover:bg-slate-50/50 transition ${isArchived ? 'opacity-60' : ''}">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block">${escapeHtml(b.resident_name) || 'Unknown resident'}${archivedTag}</span>
              <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(b.resident_barangay || '')}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs">${unitDisplay}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${b.category || '&mdash;'}</td>
        <td class="px-6 py-4.5">${benScoreBadge(b.eligibility_score)}</td>
        <td class="px-6 py-4.5">${benStageBadge(b.beneficiary_status)}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600 font-mono">${b.award_date || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewBeneficiaryModal(${b.beneficiary_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditBeneficiaryModal(${b.beneficiary_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Beneficiary">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${!isArchived ? `
            <button onclick="handleToggleBeneficiaryStatus(${b.beneficiary_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Beneficiary">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleBeneficiaryStatus(${b.beneficiary_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Restore Beneficiary">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = beneficiariesPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('benPaginationText').innerText = `Showing ${from} to ${to} of ${total} beneficiaries`;
}

function renderBenPagination() {
  const container = document.getElementById('benPaginationControls');
  if (!container) return;
  const { page, total_pages } = beneficiariesPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchBeneficiaries(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchBeneficiaries(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
