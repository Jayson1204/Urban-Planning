function householdTypeBadge(type) {
  const map = {
    'Owned': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Renting': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Informal Settler': 'bg-amber-50 text-amber-700 border-amber-150',
    'Other': 'bg-slate-50 text-slate-500 border-slate-200',
  };
  const cls = map[type] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${type || '&mdash;'}</span>`;
}

function renderHouseholds() {
  const tbody = document.getElementById('householdsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (householdsData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-house-chimney text-3xl mb-3 block opacity-60"></i>
          No households matched your query.
        </td>
      </tr>
    `;
    document.getElementById('householdsPaginationText').innerText = 'Showing 0 to 0 of 0 households';
    return;
  }

  householdsData.forEach(h => {
    const isArchived = h.status === 'Archived';
    const archivedTag = isArchived
      ? '<span class="ml-1.5 text-[9px] font-black uppercase text-slate-400">(Archived)</span>'
      : '';

    const row = `
      <tr class="hover:bg-slate-50/50 transition ${isArchived ? 'opacity-60' : ''}">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-house-chimney"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block">${escapeHtml(h.barangay || '')}${archivedTag}</span>
              <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(h.street_address || '')}${h.household_number ? ' • HH ' + escapeHtml(h.household_number) : ''}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5">${householdTypeBadge(h.household_type)}</td>
        <td class="px-6 py-4.5 text-xs text-slate-600 font-bold">${h.member_count || 0}</td>
        <td class="px-6 py-4.5">
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${isArchived ? 'bg-slate-50 text-slate-500 border-slate-200' : 'bg-emerald-50 text-emerald-700 border-emerald-150'}">${h.status || 'Active'}</span>
        </td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewHouseholdModal(${h.household_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Members">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditHouseholdModal(${h.household_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Household">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${!isArchived ? `
            <button onclick="handleToggleHouseholdStatus(${h.household_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Household">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleHouseholdStatus(${h.household_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Restore Household">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = householdsPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('householdsPaginationText').innerText = `Showing ${from} to ${to} of ${total} households`;
}

function renderHouseholdsPagination() {
  const container = document.getElementById('householdsPaginationControls');
  if (!container) return;
  const { page, total_pages } = householdsPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchHouseholds(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchHouseholds(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
