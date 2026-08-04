const HOUSING_UNIT_TYPE_LABELS = {
  'Single Detached': 'Single Detached',
  'Duplex': 'Duplex',
  'Row House': 'Row House',
  'Medium Rise': 'Medium Rise',
  'Studio': 'Studio',
  'Other': 'Other',
};

function occupancyBadge(occupancy) {
  const map = {
    'Vacant': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Occupied': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Reserved': 'bg-indigo-50 text-indigo-700 border-indigo-150',
    'Under Maintenance': 'bg-amber-50 text-amber-700 border-amber-150',
  };
  const cls = map[occupancy] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${occupancy || '—'}</span>`;
}

function formatPeso(value) {
  if (value === null || value === undefined || value === '') return '&mdash;';
  const num = parseFloat(value);
  if (isNaN(num)) return '&mdash;';
  return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function renderHousingUnits() {
  const tbody = document.getElementById('housingUnitsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (housingData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-house-chimney text-3xl mb-3 block opacity-60"></i>
          No housing units matched your query.
        </td>
      </tr>
    `;
    document.getElementById('housingPaginationText').innerText = 'Showing 0 to 0 of 0 units';
    return;
  }

  housingData.forEach(u => {
    const isActive = u.status === 'Active';
    const statusClass = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200';
    const dotPulse = isActive
      ? '<span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>'
      : '<span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>';

    const locationDisplay = (u.barangay || u.street_address)
      ? `<span class="font-bold text-slate-700">${u.barangay || ''}</span>${u.street_address ? `<br><span class="text-[10px] text-slate-400">${u.street_address}</span>` : ''}`
      : '<span class="text-slate-400">&mdash;</span>';

    const row = `
      <tr class="hover:bg-slate-50/50 transition">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-house"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block font-mono">${u.unit_code}</span>
              <span class="text-[10px] text-slate-400 font-medium">${u.project_name || 'No project'}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${HOUSING_UNIT_TYPE_LABELS[u.unit_type] || u.unit_type || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs">${locationDisplay}</td>
        <td class="px-6 py-4.5 text-xs font-semibold text-slate-700">${formatPeso(u.monthly_amortization)}</td>
        <td class="px-6 py-4.5">${occupancyBadge(u.occupancy_status)}</td>
        <td class="px-6 py-4.5">
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${statusClass} inline-flex items-center gap-1.5">
            ${dotPulse}
            <span>${u.status}</span>
          </span>
        </td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewHousingModal(${u.unit_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditHousingModal(${u.unit_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Unit">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${isActive ? `
            <button onclick="handleToggleHousingStatus(${u.unit_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Unit">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleHousingStatus(${u.unit_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Reactivate Unit">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = housingPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('housingPaginationText').innerText = `Showing ${from} to ${to} of ${total} units`;
}

function renderHousingPagination() {
  const container = document.getElementById('housingPaginationControls');
  if (!container) return;
  const { page, total_pages } = housingPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchHousingUnits(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchHousingUnits(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
