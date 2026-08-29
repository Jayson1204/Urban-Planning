function relReasonBadge(reason) {
  const map = {
    'Overcrowding': 'bg-amber-50 text-amber-700 border-amber-150',
    'Safety Issue': 'bg-rose-50 text-rose-700 border-rose-150',
    'Unit Upgrade': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Personal Request': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Government Directive': 'bg-indigo-50 text-indigo-700 border-indigo-150',
    'Other': 'bg-slate-50 text-slate-500 border-slate-200',
  };
  const cls = map[reason] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${reason || '&mdash;'}</span>`;
}

function renderRelocations() {
  const tbody = document.getElementById('relocationsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (relocationsData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-truck-moving text-3xl mb-3 block opacity-60"></i>
          No relocation records matched your query.
        </td>
      </tr>
    `;
    document.getElementById('relocationsPaginationText').innerText = 'Showing 0 to 0 of 0 records';
    return;
  }

  relocationsData.forEach(r => {
    const isArchived = r.status === 'Archived';
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
            <span class="font-black text-slate-900 tracking-tight text-xs block">${escapeHtml(r.resident_name) || 'Unknown resident'}${archivedTag}</span>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs font-mono text-slate-600">${escapeHtml(r.from_unit_code) || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs font-mono text-slate-600">${escapeHtml(r.to_unit_code) || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600 font-mono">${r.relocation_date || '&mdash;'}</td>
        <td class="px-6 py-4.5">${relReasonBadge(r.reason)}</td>
        <td class="px-6 py-4.5">
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${isArchived ? 'bg-slate-50 text-slate-500 border-slate-200' : 'bg-emerald-50 text-emerald-700 border-emerald-150'}">${r.status || 'Active'}</span>
        </td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewRelocationModal(${r.relocation_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            ${!isArchived ? `
            <button onclick="handleToggleRelocationStatus(${r.relocation_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Record">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleRelocationStatus(${r.relocation_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Restore Record">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = relocationsPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('relocationsPaginationText').innerText = `Showing ${from} to ${to} of ${total} records`;
}

function renderRelocationsPagination() {
  const container = document.getElementById('relocationsPaginationControls');
  if (!container) return;
  const { page, total_pages } = relocationsPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchRelocations(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchRelocations(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
