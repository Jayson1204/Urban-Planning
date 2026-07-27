// EVENTS & FORM SUBMISSION

async function handleSaveModule(event) {
  event.preventDefault();

  const idVal = document.getElementById('formModuleId').value;
  const name = document.getElementById('moduleName').value.trim();
  const status = document.getElementById('moduleStatus').value;
  const desc = document.getElementById('moduleDesc').value.trim();

  const payload = {
    module_name: name,
    description: desc,
    status: status
  };

  if (idVal !== '') {
    payload.module_id = parseInt(idVal);
  }

  const method = idVal === '' ? 'POST' : 'PUT';

  try {
    const response = await fetch('../../api/employee/modules.php', {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await response.json();

    if (result.status === 'success') {
      if (typeof showToast === 'function') showToast(result.message || 'Module saved successfully.');
      if (typeof closeModuleModal === 'function') closeModuleModal();
      if (typeof fetchModules === 'function') await fetchModules();
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Failed to save module.');
    }
  } catch (err) {
    console.error('Error saving module:', err);
    if (typeof showToast === 'function') showToast('Failed to save module TO DATABASE.');
  }
}

window.handleSaveModule = handleSaveModule;

// DISMISS MODALS ON BACKDROP CLICK & ESCAPE KEY
document.addEventListener('click', (e) => {
  if (e.target.id === 'moduleModal' && typeof closeModuleModal === 'function') closeModuleModal();
  if (e.target.id === 'archiveModal' && typeof closeArchiveModal === 'function') closeArchiveModal();
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if (typeof closeModuleModal === 'function') closeModuleModal();
    if (typeof closeArchiveModal === 'function') closeArchiveModal();
  }
});
