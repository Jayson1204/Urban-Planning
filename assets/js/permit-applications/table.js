function paStageBadge(stage) {
  const map = {
    'Submitted': 'bg-indigo-50 text-indigo-700 border-indigo-150',
    'Under Review': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Returned for Revision': 'bg-amber-50 text-amber-700 border-amber-150',
    'Approved': 'bg-teal-50 text-teal-700 border-teal-150',
    'Permit Issued': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Denied': 'bg-rose-50 text-rose-700 border-rose-150',
    'Cancelled': 'bg-slate-50 text-slate-500 border-slate-200',
  };
  const cls = map[stage] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${stage || '—'}</span>`;
}

function paConsolidatedBadge(result) {
  const map = {
    'Pending': 'bg-slate-50 text-slate-500 border-slate-200',
    'Under Review': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Returned for Revision': 'bg-amber-50 text-amber-700 border-amber-150',
    'Approved': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Rejected': 'bg-rose-50 text-rose-700 border-rose-150',
  };
  const cls = map[result] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black px-2 py-0.5 rounded-full border ${cls} inline-flex items-center">${result || 'Pending'}</span>`;
}

function paFormatPeso(value) {
  if (value === null || value === undefined || value === '') return '&mdash;';
  const num = parseFloat(value);
  if (isNaN(num)) return '&mdash;';
  return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderApplications() {
  const tbody = document.getElementById('paTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (applicationsData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-building-shield text-3xl mb-3 block opacity-60"></i>
          No permit applications matched your query.
        </td>
      </tr>
    `;
    document.getElementById('paPaginationText').innerText = 'Showing 0 to 0 of 0 applications';
    return;
  }

  applicationsData.forEach(pa => {
    const isArchived = pa.status === 'Archived';
    const archivedTag = isArchived
      ? '<span class="ml-1.5 text-[9px] font-black uppercase text-slate-400">(Archived)</span>'
      : '';

    const row = `
      <tr class="hover:bg-slate-50/50 transition ${isArchived ? 'opacity-60' : ''}">
        <td class="px-6 py-4.5">
          <div>
            <span class="font-black text-slate-900 tracking-tight text-xs block font-mono">${pa.reference_number || ''}${archivedTag}</span>
            <span class="text-[10px] text-slate-400 font-medium">${pa.applicant_name || 'Unknown applicant'}</span>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">
          <span class="font-bold text-slate-700">${pa.application_type || '&mdash;'}</span><br>
          <span class="text-[10px] text-slate-400">${pa.project_name || ''}</span>
        </td>
        <td class="px-6 py-4.5">${paConsolidatedBadge(pa.consolidated_result)}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600">
          ${paFormatPeso(pa.fee_amount)}<br>
          <span class="text-[10px] text-slate-400">${pa.payment_status || ''}</span>
        </td>
        <td class="px-6 py-4.5">${paStageBadge(pa.application_status)}</td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewApplicationModal(${pa.application_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditApplicationModal(${pa.application_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Application">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${!isArchived ? `
            <button onclick="handleToggleApplicationStatus(${pa.application_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Application">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleApplicationStatus(${pa.application_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Restore Application">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = applicationsPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('paPaginationText').innerText = `Showing ${from} to ${to} of ${total} applications`;
}

function renderPaPagination() {
  const container = document.getElementById('paPaginationControls');
  if (!container) return;
  const { page, total_pages } = applicationsPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchApplications(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchApplications(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
