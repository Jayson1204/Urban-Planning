function projectStatusBadge(status) {
  const map = {
    'Planned': 'bg-slate-50 text-slate-500 border-slate-200',
    'Ongoing': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Completed': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Delayed': 'bg-amber-50 text-amber-700 border-amber-150',
    'Cancelled': 'bg-rose-50 text-rose-700 border-rose-150',
  };
  const cls = map[status] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${status || '&mdash;'}</span>`;
}

function formatPeso(value) {
  if (value === null || value === undefined || value === '') return '&mdash;';
  const num = parseFloat(value);
  if (isNaN(num)) return '&mdash;';
  return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderProjects() {
  const tbody = document.getElementById('projectsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (projectsData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-diagram-project text-3xl mb-3 block opacity-60"></i>
          No urban projects matched your query.
        </td>
      </tr>
    `;
    document.getElementById('projectsPaginationText').innerText = 'Showing 0 to 0 of 0 projects';
    return;
  }

  projectsData.forEach(p => {
    const isArchived = p.status === 'Archived';
    const archivedTag = isArchived
      ? '<span class="ml-1.5 text-[9px] font-black uppercase text-slate-400">(Archived)</span>'
      : '';

    const row = `
      <tr class="hover:bg-slate-50/50 transition ${isArchived ? 'opacity-60' : ''}">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-diagram-project"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block">${escapeHtml(p.project_code)}${archivedTag}</span>
              <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(p.project_title || '')}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${p.plan_code ? `<span class="font-mono font-bold">${escapeHtml(p.plan_code)}</span>` : '<span class="text-slate-400 italic">Unlinked</span>'}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${escapeHtml(p.project_type) || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${formatPeso(p.budget)}</td>
        <td class="px-6 py-4.5">${projectStatusBadge(p.project_status)}</td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewProjectModal(${p.project_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditProjectModal(${p.project_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Project">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${!isArchived ? `
            <button onclick="handleToggleProjectStatus(${p.project_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Project">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleProjectStatus(${p.project_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Restore Project">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = projectsPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('projectsPaginationText').innerText = `Showing ${from} to ${to} of ${total} projects`;
}

function renderProjectsPagination() {
  const container = document.getElementById('projectsPaginationControls');
  if (!container) return;
  const { page, total_pages } = projectsPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchProjects(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchProjects(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
