// ROLES MANAGEMENT FORM

// AUTO-GENERATE PREFIX FROM ROLE NAME
function autoGenerateRolePrefix(nameVal) {
  const prefixInput = document.getElementById('rolePrefix');
  if (!prefixInput || prefixInput.dataset.manual === 'true') return;

  const words = nameVal.trim().split(/\s+/).filter(Boolean);
  if (words.length === 0) {
    prefixInput.value = '';
    return;
  }
  if (words.length === 1) {
    prefixInput.value = words[0].substring(0, 4).toUpperCase();
  } else {
    prefixInput.value = words.map(w => w[0]).join('').substring(0, 4).toUpperCase();
  }
}

async function handleSaveRole(e) {
  e.preventDefault();
  
  const idRef = document.getElementById('roleIdRef').value;
  const name = document.getElementById('roleName').value.trim();
  const prefix = document.getElementById('rolePrefix').value.trim().toUpperCase();
  const isGlobal = document.getElementById('roleIsGlobalAccess').checked;
  const desc = document.getElementById('roleDesc').value.trim();
  const status = document.getElementById('roleStatus').value;

  const payload = {
    role_name: name,
    role_prefix: prefix,
    is_global_access: isGlobal,
    description: desc,
    status: status
  };

  if (idRef !== '') {
    payload.role_id = parseInt(idRef);
  }

  const method = idRef === '' ? 'POST' : 'PUT';

  try {
    const response = await fetch('../../api/employee/roles.php', {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await response.json();

    if (result.status === 'success') {
      if (typeof showToast === 'function') showToast(result.message || 'System role saved successfully.');
      if (typeof closeModal === 'function') closeModal('roleModal');
      if (typeof fetchRoles === 'function') await fetchRoles();
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Error saving role.', true);
    }
  } catch (err) {
    console.error('Save role error:', err);
    if (typeof showToast === 'function') showToast('Failed to save role TO DATABASE.', true);
  }
}
