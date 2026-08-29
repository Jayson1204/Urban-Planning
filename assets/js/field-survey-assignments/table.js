function assignmentProgressBadge(status) {
  const map = {
    'Pending': 'bg-amber-50 text-amber-700 border-amber-150',
    'In Progress': 'bg-cyan-50 text-cyan-700 border-cyan-150',
    'Completed': 'bg-emerald-50 text-emerald-700 border-emerald-150',
    'Cancelled': 'bg-slate-50 text-slate-500 border-slate-200',
  };
  const cls = map[status] || 'bg-slate-50 text-slate-500 border-slate-200';
  return `<span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${cls} inline-flex items-center gap-1.5">${status || '—'}</span>`;
}

function subjectIcon(subjectType) {
  if (subjectType === 'Resident') return 'fa-user';
  if (subjectType === 'Household') return 'fa-house-chimney';
  return 'fa-map-location-dot';
}

function renderAssignments() {
  const tbody = document.getElementById('assignmentsTableBody');
  if (!tbody) return;
  tbody.innerHTML = '';

  if (assignmentsData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-clipboard-user text-3xl mb-3 block opacity-60"></i>
          No survey assignments matched your query.
        </td>
      </tr>
    `;
    document.getElementById('assignmentPaginationText').innerText = 'Showing 0 to 0 of 0 assignments';
    return;
  }

  assignmentsData.forEach(a => {
    const isActive = a.status === 'Active';

    const row = `
      <tr class="hover:bg-slate-50/50 transition">
        <td class="px-6 py-4.5">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-600 shrink-0 font-bold text-xs">
              <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div>
              <span class="font-black text-slate-900 tracking-tight text-xs block font-mono">${escapeHtml(a.form_code) || '&mdash;'}</span>
              <span class="text-[10px] text-slate-400 font-medium">${escapeHtml(a.form_title || '')}</span>
            </div>
          </div>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">
          <i class="fa-solid ${subjectIcon(a.subject_type)} text-[10px] text-slate-400 mr-1"></i>
          ${escapeHtml(a.subject_name) || '&mdash;'}
          <span class="text-[10px] text-slate-400 block">${a.subject_type || ''}</span>
        </td>
        <td class="px-6 py-4.5 text-xs text-slate-600">${escapeHtml(a.assigned_to) || '&mdash;'}</td>
        <td class="px-6 py-4.5 text-xs font-semibold text-slate-700">${a.due_date || '&mdash;'}</td>
        <td class="px-6 py-4.5">${assignmentProgressBadge(a.assignment_status)}</td>
        <td class="px-6 py-4.5 text-right whitespace-nowrap">
          <div class="inline-flex items-center space-x-2">
            <button onclick="openEditAssignmentModal(${a.assignment_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-50 p-1.5 rounded-lg border border-transparent hover:border-slate-150 transition cursor-pointer" title="Edit Assignment">
              <i class="fa-solid fa-pen text-xs"></i>
            </button>
            ${isActive ? `
            <button onclick="handleToggleAssignmentStatus(${a.assignment_id}, 'Archived')" class="text-slate-400 hover:text-amber-600 hover:bg-amber-50 p-1.5 rounded-lg border border-transparent hover:border-amber-150 transition cursor-pointer" title="Archive Assignment">
              <i class="fa-solid fa-box-archive text-xs"></i>
            </button>` : `
            <button onclick="handleToggleAssignmentStatus(${a.assignment_id}, 'Active')" class="text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Reactivate Assignment">
              <i class="fa-solid fa-rotate-left text-xs"></i>
            </button>`}
          </div>
        </td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  const { page, per_page, total } = assignmentsPagination;
  const from = total === 0 ? 0 : (page - 1) * per_page + 1;
  const to = Math.min(page * per_page, total);
  document.getElementById('assignmentPaginationText').innerText = `Showing ${from} to ${to} of ${total} assignments`;
}

function renderAssignmentPagination() {
  const container = document.getElementById('assignmentPaginationControls');
  if (!container) return;
  const { page, total_pages } = assignmentsPagination;

  const prevDisabled = page <= 1;
  const nextDisabled = page >= total_pages;

  container.innerHTML = `
    <button onclick="fetchAssignments(${page - 1})" ${prevDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-left text-[9px]"></i>
    </button>
    <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
    <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
    <button onclick="fetchAssignments(${page + 1})" ${nextDisabled ? 'disabled' : ''} class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
      <i class="fa-solid fa-chevron-right text-[9px]"></i>
    </button>
  `;
}
