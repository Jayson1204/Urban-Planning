const PLAN_TYPE_LABELS = {
  'Comprehensive Land Use Plan': 'Comprehensive Land Use Plan',
  'Zoning Plan': 'Zoning Plan',
  'Infrastructure Plan': 'Infrastructure Plan',
  'Development Framework': 'Development Framework',
  'Other': 'Other',
};

function planStatusBadge(planStatus) {
  const map = {
    'Draft': 'bg-slate-50 text-slate-500 border-slate-200',
    'Active': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Completed': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Archived': 'bg-amber-50 text-amber-700 border-amber-150',
  };
  const cls = map[planStatus] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${planStatus || '—'}</span>`;
}

function formatPeso(value) {
  if (value === null || value === undefined || value === '') return '&mdash;';
  const num = parseFloat(value);
  if (isNaN(num)) return '&mdash;';
  return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatPlanDate(value) {
  if (!value) return null;
  const d = new Date(value);
  if (isNaN(d.getTime())) return null;
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function renderDevelopmentPlans() {
  const tbody = document.getElementById('developmentPlansTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (planData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-map-location-dot text-3xl mb-3 block opacity-60"></i>
          No development plans matched your query.
        </td>
      </tr>
    `;
    document.getElementById('planPaginationText').innerText = 'Showing 0 to 0 of 0 plans';
    return;
  }

  planData.forEach(p => {
    const isActive = p.status === 'Active';
    const statusClass = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200';
    const dotPulse = isActive
      ? '<span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>'
      : '<span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>';

    const coverageDisplay = (p.barangay || p.coverage_area)
      ? `<span class="font-bold text-slate-700">${escapeHtml(p.barangay || '')}</span>${p.coverage_area ? `<br><span class="text-[10px] text-slate-400">${escapeHtml(p.coverage_area)}</span>` : ''}`
      : '<span class="text-slate-400">&mdash;</span>';

    const startDisplay = formatPlanDate(p.start_date);
    const endDisplay = formatPlanDate(p.end_date);
    const timelineDisplay = (startDisplay || endDisplay)
      ? `${startDisplay || '&mdash;'} <span class="text-slate-300">&rarr;</span> ${endDisplay || '&mdash;'}`
      : '<span class="text-slate-400">&mdash;</span>';

    const row = `
      <tr class="hover:bg-slate-50/50 transition">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-map"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block font-mono">${escapeHtml(p.plan_code)}</span>
              <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(p.plan_title) || 'Untitled plan'}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${PLAN_TYPE_LABELS[p.plan_type] || escapeHtml(p.plan_type) || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs">${coverageDisplay}</td>
        <td class="px-6 py-4.5 text-xs font-semibold text-slate-700 whitespace-nowrap">${timelineDisplay}</td>
        <td class="px-6 py-4.5">${planStatusBadge(p.plan_status)}</td>
        <td class="px-6 py-4.5">
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${statusClass} inline-flex items-center gap-1.5">
            ${dotPulse}
            <span>${p.status}</span>
          </span>
        </td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewPlanModal(${p.plan_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditPlanModal(${p.plan_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Plan">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${isActive ? `
            <button onclick="handleTogglePlanStatus(${p.plan_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Plan">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleTogglePlanStatus(${p.plan_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Reactivate Plan">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = planPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('planPaginationText').innerText = `Showing ${from} to ${to} of ${total} plans`;
}

function renderPlanPagination() {
  const container = document.getElementById('planPaginationControls');
  if (!container) return;
  const { page, total_pages } = planPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchDevelopmentPlans(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchDevelopmentPlans(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
