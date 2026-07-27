// ROLES MANAGEMENT UI

// RENDER ROLES TABLE BASED ON DATABASE FIELDS
function renderRoles(dataToRender = systemRoles) {
  const rolesTbody = document.getElementById('rolesTableBody');
  if (!rolesTbody) return;
  rolesTbody.innerHTML = '';
  
  if (dataToRender.length === 0) {
    rolesTbody.innerHTML = `
      <tr>
        <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-semibold">
          <i class="fa-solid fa-folder-open text-3xl mb-3 block opacity-60"></i>
          No roles match your search filter.
        </td>
      </tr>
    `;
    const paginationEl = document.getElementById('rolesPaginationText');
    if (paginationEl) paginationEl.innerText = "Showing 0 to 0 of 0 defined roles";
    if (typeof updateMetrics === 'function') updateMetrics();
    return;
  }

  const isSuperAdmin = currentUserScope ? !!currentUserScope.is_superadmin : false;
  const grantedActions = currentUserScope ? (currentUserScope.granted_actions || []) : [];
  const canEdit = isSuperAdmin || grantedActions.includes('EDIT');

  dataToRender.forEach(role => {
    // Status Badge classes
    const statusStyles = role.status === 'Active' 
      ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
      : 'bg-slate-100 text-slate-600 border-slate-200';
    
    const dotPulse = role.status === 'Active' 
      ? '<span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>' 
      : '<span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>';

    // Global Access Badge
    const globalAccessBadge = role.is_global_access
      ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200"><i class="fa-solid fa-globe text-[9px]"></i> Global Access</span>`
      : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200"><i class="fa-solid fa-building text-[9px]"></i> Department Scoped</span>`;

    // System Role Protection Badge
    const systemRoleBadge = role.is_system_role
      ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-black bg-amber-50 text-amber-700 border border-amber-200" title="Protected System Role"><i class="fa-solid fa-lock text-[8px]"></i> System Core</span>`
      : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Custom Role</span>`;

    const isChecked = role.status === 'Active' ? 'checked' : '';

    const tr = document.createElement('tr');
    tr.className = 'hover:bg-slate-50/50 transition';
    tr.innerHTML = `
      <!-- Role Designation & Prefix -->
      <td class="px-6 py-4">
        <div class="flex items-center gap-3">
          <div class="h-9 min-w-[2.25rem] px-2.5 rounded-xl bg-gradient-to-br from-brand-light to-blue-50 border border-brand-border/80 flex items-center justify-center text-brand-dark shrink-0 font-mono font-black text-xs tracking-wider shadow-2xs">
            ${role.role_prefix}
          </div>
          <div class="flex items-center gap-2">
            <span class="font-extrabold text-slate-900 tracking-tight text-xs">${role.role_name}</span>
            ${systemRoleBadge}
          </div>
        </div>
      </td>
      
      <!-- Scope & Access Level -->
      <td class="px-6 py-4">
        <div class="space-y-1 max-w-xs">
          <p class="text-[11px] text-slate-600 font-medium leading-relaxed">${role.description}</p>
          <div class="pt-0.5">
            ${globalAccessBadge}
          </div>
        </div>
      </td>

      <!-- Created At -->
      <td class="px-6 py-4 text-slate-500 font-mono text-[10px] font-bold">
        ${role.created_at}
      </td>

      <!-- Status Pill -->
      <td class="px-6 py-4 text-center">
        <span class="text-[10px] font-black uppercase px-2.5 py-1 rounded-full border ${statusStyles} inline-flex items-center gap-1.5">
          ${dotPulse}
          <span>${role.status}</span>
        </span>
      </td>

      <!-- Actions -->
      <td class="px-6 py-4 text-right whitespace-nowrap">
        <div class="inline-flex items-center space-x-3">
          ${canEdit ? `
          <!-- iOS Status Switch Toggle -->
          <label class="relative inline-flex items-center cursor-pointer select-none ${role.is_system_role ? 'opacity-50 cursor-not-allowed' : ''}" title="${role.is_system_role ? 'System roles cannot be deactivated' : 'Activate/Deactivate toggle'}">
            <input type="checkbox" ${isChecked} ${role.is_system_role ? 'disabled' : ''} onchange="handleRoleStatusToggle(${role.role_id}, this)" class="sr-only peer">
            <div class="w-8 h-4.5 bg-slate-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-brand-medium/20 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all peer-checked:bg-emerald-600"></div>
          </label>

          <!-- Edit button -->
          <button type="button" onclick="if(typeof openEditModal === 'function') openEditModal(${role.role_id})" class="text-slate-400 hover:text-[#0f172a] hover:bg-slate-100 p-1.5 rounded-lg border border-slate-200 transition cursor-pointer" title="Edit Role Parameters">
            <i class="fa-solid fa-pen-to-square text-xs"></i>
          </button>` : `<span class="text-[10px] text-slate-400 font-bold italic">Read-only</span>`}
        </div>
      </td>
    `;
    rolesTbody.appendChild(tr);
  });

  const paginationEl = document.getElementById('rolesPaginationText');
  if (paginationEl) {
    paginationEl.innerText = `Showing 1 to ${dataToRender.length} of ${systemRoles.length} defined roles`;
  }

  if (typeof updateMetrics === 'function') updateMetrics();
}

// UPDATE SUMMARY STATISTICS CARDS
function updateMetrics() {
  const total = systemRoles.length;
  const globalCount = systemRoles.filter(r => r.is_global_access).length;
  const activeCount = systemRoles.filter(r => r.status === 'Active').length;
  const systemCount = systemRoles.filter(r => r.is_system_role).length;

  const totalEl = document.getElementById('statTotalRoles');
  const globalEl = document.getElementById('statGlobalRoles');
  const activeEl = document.getElementById('statActiveRoles');
  const systemEl = document.getElementById('statSystemRoles');

  if (totalEl) totalEl.innerText = total;
  if (globalEl) globalEl.innerText = globalCount;
  if (activeEl) activeEl.innerText = activeCount;
  if (systemEl) systemEl.innerText = systemCount;
}
