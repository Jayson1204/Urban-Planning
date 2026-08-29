function conditionBadge(rating) {
  const map = {
    'Excellent': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Good': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Fair': 'bg-amber-50 text-amber-700 border-amber-150',
    'Poor': 'bg-orange-50 text-orange-700 border-orange-150',
    'Critical': 'bg-red-50 text-red-700 border-red-150',
  };
  if (!rating) return '<span class="text-slate-400">Not rated</span>';
  const cls = map[rating] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${rating}</span>`;
}

function renderResults() {
  const tbody = document.getElementById('resultsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (resultsData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-file-circle-check text-3xl mb-3 block opacity-60"></i>
          No survey results matched your query.
        </td>
      </tr>
    `;
    document.getElementById('resultPaginationText').innerText = 'Showing 0 to 0 of 0 results';
    return;
  }

  resultsData.forEach(r => {
    const isActive = r.status === 'Active';
    const statusClass = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200';
    const dotPulse = isActive
      ? '<span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>'
      : '<span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>';

    const row = `
      <tr class="hover:bg-slate-50/50 transition">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-file-circle-check"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block font-mono">${escapeHtml(r.form_code) || '&mdash;'}</span>
              <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(r.form_title || '')}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">
          ${escapeHtml(r.subject_name) || '&mdash;'}
          <span class="text-[10px] text-slate-400 block">${r.subject_type || ''}</span>
        </td>
        <td class="px-6 py-4.5 text-xs font-semibold text-slate-700">${r.survey_date || '&mdash;'}</td>
        <td class="px-6 py-4.5">${conditionBadge(r.condition_rating)}</td>
        <td class="px-6 py-4.5">
          <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${statusClass} inline-flex items-center gap-1.5">
            ${dotPulse}
            <span>${r.status}</span>
          </span>
        </td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openViewResultModal(${r.result_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="View Details &amp; Photos">
              <i class="fa-solid fa-circle-info text-xs"></i>
            </button>
            <button onclick="openEditResultModal(${r.result_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Result">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${isActive ? `
            <button onclick="handleToggleResultStatus(${r.result_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Result">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleResultStatus(${r.result_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Reactivate Result">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = resultsPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('resultPaginationText').innerText = `Showing ${from} to ${to} of ${total} results`;
}

function renderResultPagination() {
  const container = document.getElementById('resultPaginationControls');
  if (!container) return;
  const { page, total_pages } = resultsPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchResults(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchResults(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
