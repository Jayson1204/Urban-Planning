// RENDER MODULES TABLE AND METRICS
function renderModulesTable(dataToRender = systemModules) {
  const tableBody = document.getElementById('moduleTableBody');
  const emptyState = document.getElementById('emptyTableState');
  if (!tableBody) return;

  tableBody.innerHTML = '';

  if (dataToRender.length === 0) {
    if (emptyState) emptyState.classList.remove('hidden');
    updateMetrics();
    return;
  } else {
    if (emptyState) emptyState.classList.add('hidden');
  }

  const isSuperAdmin = currentUserScope ? !!currentUserScope.is_superadmin : false;
  const grantedActions = currentUserScope ? (currentUserScope.granted_actions || []) : [];
  const canEdit = isSuperAdmin || grantedActions.includes('EDIT');

  dataToRender.forEach(mod => {
    const tr = document.createElement('tr');
    tr.className = 'hover:bg-slate-50/60 transition group';

    // Status Badge HTML
    let statusBadgeHtml = '';
    if (mod.status === 'Active') {
      statusBadgeHtml = `
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          Active
        </span>
      `;
    } else if (mod.status === 'Inactive') {
      statusBadgeHtml = `
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200">
          <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
          Inactive
        </span>
      `;
    } else {
      statusBadgeHtml = `
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200">
          <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
          Archived
        </span>
      `;
    }

    // iOS Style Status Toggle Switch
    const isChecked = mod.status === 'Active';
    const isArchived = mod.status === 'Archived';

    tr.innerHTML = `
      <td class="px-6 py-4">
        <span class="font-extrabold text-slate-900 tracking-tight block text-xs">${mod.name}</span>
      </td>

      <td class="px-6 py-4 max-w-xs">
        <p class="text-xs text-slate-600 font-medium leading-relaxed">${mod.desc || '<span class="text-slate-400 italic">No description</span>'}</p>
      </td>

      <td class="px-6 py-4 text-center">
        ${statusBadgeHtml}
      </td>

      <td class="px-6 py-4 text-slate-500 font-mono text-[10px] font-bold">
        ${mod.created_at}
      </td>

      <td class="px-6 py-4 text-slate-500 font-mono text-[10px] font-bold">
        ${mod.updated_at}
      </td>

      <td class="px-6 py-4 text-right">
        <div class="flex items-center justify-end gap-3">
          ${canEdit ? `
          <!-- iOS Toggle Switch -->
          <label class="relative inline-flex items-center cursor-pointer ${isArchived ? 'opacity-50 pointer-events-none' : ''}" title="Activate/Deactivate Module">
            <input 
              type="checkbox" 
              ${isChecked ? 'checked' : ''} 
              onchange="toggleModuleStatus(${mod.id})" 
              class="sr-only peer"
            >
            <div class="w-8 h-4.5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
          </label>

          <!-- Edit Button -->
          <button 
            type="button" 
            onclick="openEditModal(${mod.id})" 
            class="h-8 w-8 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 hover:text-brand-dark flex items-center justify-center transition cursor-pointer"
            title="Edit Module Parameters"
          >
            <i class="fa-solid fa-pen-to-square text-xs"></i>
          </button>

          <!-- Archive Button -->
          <button 
            type="button" 
            onclick="openArchiveModal(${mod.id})" 
            class="h-8 w-8 rounded-lg border border-slate-200 hover:bg-amber-50 text-slate-400 hover:text-amber-600 flex items-center justify-center transition cursor-pointer ${isArchived ? 'opacity-40 cursor-not-allowed' : ''}"
            ${isArchived ? 'disabled' : ''}
            title="Archive Module"
          >
            <i class="fa-solid fa-box-archive text-xs"></i>
          </button>` : `<span class="text-[10px] text-slate-400 font-bold italic">Read-only</span>`}
        </div>
      </td>
    `;

    tableBody.appendChild(tr);
  });

  updateMetrics();
}

// UPDATE TOP METRIC CARDS
function updateMetrics() {
  const totalEl = document.getElementById('metricTotalModules');
  const activeEl = document.getElementById('metricActiveModules');
  const inactiveEl = document.getElementById('metricInactiveModules');

  if (totalEl) totalEl.textContent = systemModules.length;
  
  const activeCount = systemModules.filter(m => m.status === 'Active').length;
  if (activeEl) activeEl.textContent = activeCount;

  const inactiveCount = systemModules.filter(m => m.status !== 'Active').length;
  if (inactiveEl) inactiveEl.textContent = inactiveCount;
}
