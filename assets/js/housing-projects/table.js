function hpProjectStatusBadge(status) {
  if (!status) return '<span class="text-slate-400">Data unavailable</span>';
  const map = {
    'Existing': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Ongoing': 'bg-amber-50 text-amber-700 border-amber-150',
    'Proposed': 'bg-slate-50 text-slate-500 border-slate-200',
    'Completed': 'bg-emerald-50 text-emerald-700 border-emerald-150',
  };
  const cls = map[status] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls}">${status}</span>`;
}

function renderHousingProjects() {
  const tbody = document.getElementById('hpTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (hpData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-building-shield text-3xl mb-3 block opacity-60"></i>
          No housing projects matched your query.
        </td>
      </tr>
    `;
    document.getElementById('hpPaginationText').innerText = 'Showing 0 to 0 of 0 projects';
    return;
  }

  hpData.forEach(p => {
    const isActive = p.status === 'Active';
    const statusClass = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200';

    const row = `
      <tr class="hover:bg-slate-50/50 transition">
        <td class="px-6 py-4.5">
          <span class="font-black text-slate-900 tracking-tight text-xs block">${escapeHtml(p.name)}</span>
          <span class="text-[10px] text-slate-400">${escapeHtml(p.source)}</span>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${escapeHtml(p.barangay_name || p.barangay) || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs font-semibold text-slate-700">${p.units ?? 'Data unavailable'}</td>
        <td class="px-6 py-4.5">${hpProjectStatusBadge(p.project_status)}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${escapeHtml(p.developer) || 'Data unavailable'}</td>
        <td class="px-6 py-4.5">
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${statusClass}">${p.status}</span>
        </td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openEditHousingProjectModal(${p.housing_project_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${isActive ? `
            <button onclick="handleToggleHpStatus(${p.housing_project_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleHpStatus(${p.housing_project_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Reactivate">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = hpPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('hpPaginationText').innerText = `Showing ${from} to ${to} of ${total} projects`;
}

function renderHpPagination() {
  const container = document.getElementById('hpPaginationControls');
  if (!container) return;
  const { page, total_pages } = hpPagination;
  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchHousingProjectsList(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchHousingProjectsList(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
