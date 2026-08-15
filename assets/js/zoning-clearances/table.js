function zcStageBadge(stage) {
  const map = {
    'Submitted': 'bg-indigo-50 text-indigo-700 border-indigo-150',
    'Under Review': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Returned for Revision': 'bg-amber-50 text-amber-700 border-amber-150',
    'Approved': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Denied': 'bg-rose-50 text-rose-700 border-rose-150',
    'Cancelled': 'bg-slate-50 text-slate-500 border-slate-200',
  };
  const cls = map[stage] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${stage || '—'}</span>`;
}

function zcConformityBadge(result) {
  const map = {
    'Conforming': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Non-Conforming': 'bg-rose-50 text-rose-700 border-rose-150',
    'Needs Manual Review': 'bg-amber-50 text-amber-700 border-amber-150',
  };
  const cls = map[result] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black px-2 py-0.5 rounded-full border ${cls} inline-flex items-center">${result || 'Pending'}</span>`;
}

function zcFormatPeso(value) {
  if (value === null || value === undefined || value === '') return '&mdash;';
  const num = parseFloat(value);
  if (isNaN(num)) return '&mdash;';
  return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderClearances() {
  const tbody = document.getElementById('zcTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (clearancesData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-stamp text-3xl mb-3 block opacity-60"></i>
          No zoning clearance applications matched your query.
        </td>
      </tr>
    `;
    document.getElementById('zcPaginationText').innerText = 'Showing 0 to 0 of 0 applications';
    return;
  }

  clearancesData.forEach(zc => {
    const isArchived = zc.status === 'Archived';
    const archivedTag = isArchived
      ? '<span class="ml-1.5 text-[9px] font-black uppercase text-slate-400">(Archived)</span>'
      : '';

    const row = `
      <tr class="hover:bg-slate-50/50 transition ${isArchived ? 'opacity-60' : ''}">
        <td class="px-6 py-4.5">
          <div>
            <span class="font-black text-slate-900 tracking-tight text-xs block font-mono">${zc.reference_number || ''}${archivedTag}</span>
            <span class="text-[10px] text-slate-400 font-medium">${zc.applicant_name || 'Unknown applicant'}</span>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">
          <span class="font-bold text-slate-700">${zc.zone_classification || '&mdash;'}</span><br>
          <span class="text-[10px] text-slate-400">${zc.use_category || ''}</span>
        </td>
        <td class="px-6 py-4.5">${zcConformityBadge(zc.conformity_result)}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600">
          ${zcFormatPeso(zc.fee_amount)}<br>
          <span class="text-[10px] text-slate-400">${zc.payment_status || ''}</span>
        </td>
        <td class="px-6 py-4.5">${zcStageBadge(zc.clearance_status)}</td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewClearanceModal(${zc.clearance_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditClearanceModal(${zc.clearance_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Application">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${!isArchived ? `
            <button onclick="handleToggleClearanceStatus(${zc.clearance_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Application">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleClearanceStatus(${zc.clearance_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Restore Application">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = clearancesPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('zcPaginationText').innerText = `Showing ${from} to ${to} of ${total} applications`;
}

function renderZcPagination() {
  const container = document.getElementById('zcPaginationControls');
  if (!container) return;
  const { page, total_pages } = clearancesPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchClearances(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchClearances(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
