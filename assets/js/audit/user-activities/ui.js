window.civAudit = window.civAudit || {};
window.civAudit.userActivities = window.civAudit.userActivities || {};

window.civAudit.userActivities.ui = {
  populateFilterDropdowns() {
    const filterDepartment = document.getElementById('filterDepartment');
    const filterModule = document.getElementById('filterModule');
    const api = window.civAudit.userActivities.api;

    if (filterDepartment) {
      const curVal = filterDepartment.value;
      filterDepartment.innerHTML = '<option value="All">All Departments</option>';
      api.availableDepartments.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.department_name;
        opt.textContent = d.department_name;
        filterDepartment.appendChild(opt);
      });
      filterDepartment.value = curVal || 'All';
    }

    if (filterModule) {
      const curVal = filterModule.value;
      filterModule.innerHTML = '<option value="All">All Modules</option>';
      api.availableModules.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.module_name;
        opt.textContent = m.module_name;
        filterModule.appendChild(opt);
      });
      filterModule.value = curVal || 'All';
    }
  },

  renderTableRows(logsList) {
    const tbody = document.getElementById('auditTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (logsList.length === 0) {
      tbody.innerHTML = `
        <tr id="noResultsRow">
          <td colspan="7" class="py-12 text-center text-slate-400">
            <div class="flex flex-col items-center justify-center space-y-2">
              <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
              <p class="text-xs font-bold">No matching audit logs found</p>
              <p class="text-[10px] font-semibold text-slate-400">Try adjusting your filters or resetting them to defaults.</p>
            </div>
          </td>
        </tr>
      `;
      return;
    }

    logsList.forEach(item => {
      let roleBadgeClass = 'bg-slate-100 text-slate-600 ring-1 ring-slate-600/10';
      if (item.roleColor === 'purple') roleBadgeClass = 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20';
      else if (item.roleColor === 'blue') roleBadgeClass = 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';

      let actionBadgeClass = 'bg-slate-50 text-slate-700 ring-1 ring-slate-600/20';
      if (item.action === 'Approve') actionBadgeClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
      else if (item.action === 'Create') actionBadgeClass = 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
      else if (item.action === 'Update' || item.action === 'Edit') actionBadgeClass = 'bg-amber-50 text-amber-800 ring-1 ring-amber-600/20';
      else if (item.action === 'Reject' || item.action === 'Delete') actionBadgeClass = 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20';

      let statusBadgeHTML = `
        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">
          <i class="fa-solid fa-circle-check text-[9px]"></i> Success
        </span>
      `;
      if (item.status !== 'Success') {
        statusBadgeHTML = `
          <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20">
            <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> ${window.escapeHtml ? window.escapeHtml(item.status) : item.status}
          </span>
        `;
      }

      const tr = document.createElement('tr');
      tr.className = 'hover:bg-slate-50/70 transition cursor-pointer';
      tr.setAttribute('onclick', 'window.civAudit.userActivities.modal.openLogDetailsModal(this)');
      tr.setAttribute('data-id', item.id);
      tr.setAttribute('data-date-time', item.dateTimeStr);
      tr.setAttribute('data-date', item.date);
      tr.setAttribute('data-actor', item.actor);
      tr.setAttribute('data-role', item.role);
      tr.setAttribute('data-role-color', item.roleColor);
      tr.setAttribute('data-department', item.department);
      tr.setAttribute('data-module', item.module);
      tr.setAttribute('data-action', item.action);
      tr.setAttribute('data-desc', item.desc);
      tr.setAttribute('data-ip', item.ip);
      tr.setAttribute('data-device', item.device);
      tr.setAttribute('data-status', item.status);
      tr.setAttribute('data-payload', item.payloadStr);

      const esc = window.escapeHtml || (s => s);

      tr.innerHTML = `
        <td class="py-4 px-5 font-mono text-[11px] font-bold text-slate-500">${esc(item.id)}</td>
        <td class="py-4 px-5">
          <div class="font-bold text-slate-800">${esc(item.datePart)}</div>
          <div class="text-[10px] text-slate-400 font-semibold mt-0.5">${esc(item.timePart)}</div>
        </td>
        <td class="py-4 px-5">
          <div class="font-bold text-slate-800">${esc(item.actor)}</div>
          <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase ${roleBadgeClass} mt-1">${esc(item.role)}</span>
        </td>
        <td class="py-4 px-5">
          <div class="font-bold text-slate-800">${esc(item.department)}</div>
          <div class="text-[10px] text-slate-400 font-semibold mt-0.5">${esc(item.module)}</div>
        </td>
        <td class="py-4 px-5">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase ${actionBadgeClass}">${esc(item.action)}</span>
            <span class="font-bold text-slate-800">${esc(item.desc)}</span>
          </div>
        </td>
        <td class="py-4 px-5">
          <div class="font-semibold text-slate-700 font-mono text-[11px]">${esc(item.ip)}</div>
          <div class="text-[10px] text-slate-400 font-semibold mt-0.5">${esc(item.device)}</div>
        </td>
        <td class="py-4 px-5 text-center">
          ${statusBadgeHTML}
        </td>
      `;
      tbody.appendChild(tr);
    });
  }
};
