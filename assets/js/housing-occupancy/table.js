function renderOccupancy() {
  const tbody = document.getElementById('occupancyTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (occupancyData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-house-user text-3xl mb-3 block opacity-60"></i>
          No occupancy records matched your query.
        </td>
      </tr>
    `;
    document.getElementById('occupancyPaginationText').innerText = 'Showing 0 to 0 of 0 records';
    return;
  }

  occupancyData.forEach(o => {
    const isActive = o.status === 'Active';

    const row = `
      <tr class="hover:bg-slate-50/50 transition ${isActive ? '' : 'opacity-60'}">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-user"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block">${escapeHtml(o.resident_name) || 'Unknown resident'}</span>
              <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(o.resident_barangay || '')}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs">
          <span class="font-bold text-slate-700 font-mono">${escapeHtml(o.unit_code || '')}</span>${o.project_name ? `<br><span class="text-[10px] text-slate-400">${escapeHtml(o.project_name)}</span>` : ''}
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600 font-mono">${o.move_in_date || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600 font-mono">${o.move_out_date || '&mdash;'}</td>
        <td class="px-6 py-4.5">
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200'}">${isActive ? 'Occupied' : 'Moved Out'}</span>
        </td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          ${isActive ? `
          <button onclick="handleVacateOccupancy(${o.occupancy_id})" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Record Move-Out">
            <i class="fa-solid fa-door-closed text-xs"></i>
          </button>` : ''}
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = occupancyPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('occupancyPaginationText').innerText = `Showing ${from} to ${to} of ${total} records`;
}

function renderOccupancyPagination() {
  const container = document.getElementById('occupancyPaginationControls');
  if (!container) return;
  const { page, total_pages } = occupancyPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchOccupancy(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchOccupancy(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
