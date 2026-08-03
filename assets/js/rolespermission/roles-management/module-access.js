// CAPSTONE MODULE ACCESS (local, per-role — separate from production's Role & Permission system)

async function openModuleAccessModal(roleId, roleName) {
  document.getElementById('moduleAccessRoleId').value = roleId;
  document.getElementById('moduleAccessRoleLabel').innerText = roleName ? `Role: ${roleName}` : '—';

  const listEl = document.getElementById('moduleAccessCheckboxList');
  listEl.innerHTML = '<p class="text-xs text-slate-400">Loading modules...</p>';
  openModal('moduleAccessModal');

  try {
    const response = await fetch('../../api/employee/capstone-module-access.php');
    const result = await response.json();

    if (result.status !== 'success') {
      listEl.innerHTML = `<p class="text-xs text-rose-500">${result.message || 'Failed to load modules.'}</p>`;
      return;
    }

    const modules = result.modules || {};
    const grantedForRole = (result.assignments && result.assignments[roleId]) || [];

    const slugs = Object.keys(modules);
    if (slugs.length === 0) {
      listEl.innerHTML = '<p class="text-xs text-slate-400">No capstone modules registered yet.</p>';
      return;
    }

    listEl.innerHTML = slugs.map(slug => {
      const mod = modules[slug];
      const checked = grantedForRole.includes(slug) ? 'checked' : '';
      return `
        <label class="flex items-center gap-3 p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition">
          <input type="checkbox" value="${slug}" class="module-access-checkbox h-4 w-4 rounded border-slate-300 text-brand-dark focus:ring-brand-dark/20 accent-brand-dark cursor-pointer" ${checked}>
          <i class="fa-solid ${mod.icon || 'fa-cube'} text-brand-dark text-sm"></i>
          <span class="text-xs font-bold text-slate-700">${mod.label}</span>
        </label>
      `;
    }).join('');
  } catch (err) {
    console.error('Error loading module access:', err);
    listEl.innerHTML = '<p class="text-xs text-rose-500">Network error loading modules.</p>';
  }
}

async function handleSaveModuleAccess() {
  const roleId = parseInt(document.getElementById('moduleAccessRoleId').value, 10);
  const selected = Array.from(document.querySelectorAll('.module-access-checkbox:checked')).map(cb => cb.value);

  try {
    const response = await fetch('../../api/employee/capstone-module-access.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ role_id: roleId, modules: selected })
    });
    const result = await response.json();

    if (result.status === 'success') {
      if (typeof showToast === 'function') showToast('Module access updated successfully.');
      closeModal('moduleAccessModal');
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Failed to update module access.', true);
    }
  } catch (err) {
    console.error('Error saving module access:', err);
    if (typeof showToast === 'function') showToast('Network error saving module access.', true);
  }
}
