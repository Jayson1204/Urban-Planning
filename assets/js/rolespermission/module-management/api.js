// MODULE MANAGEMENT API

var systemModules = [];
var currentUserScope = null;
var archiveTargetId = null;

// FETCH MODULES FROM Database REST API
async function fetchModules() {
  try {
    const response = await fetch('../../api/employee/modules.php');
    const result = await response.json();
    if (result.status === 'success' && Array.isArray(result.data)) {
      systemModules = result.data.map(m => ({
        id: m.module_id || m.id,
        name: m.module_name || m.name || 'Unassigned Module',
        desc: m.description || '',
        status: m.status || 'Active',
        created_at: m.created_at ? String(m.created_at).replace('T', ' ').substring(0, 19) : '',
        updated_at: m.updated_at ? String(m.updated_at).replace('T', ' ').substring(0, 19) : ''
      }));
      currentUserScope = result.current_user || null;
      if (typeof filterModules === 'function') filterModules();
    } else {
      console.warn('Modules fetch notice:', result.message);
    }
  } catch (err) {
    console.error('Error fetching modules FROM DATABASE:', err);
    if (typeof showToast === 'function') showToast('Network error connecting to Database.');
  } finally {
    if (typeof hideModuleSkeleton === 'function') {
      hideModuleSkeleton();
    }
  }
}

// UPDATE MODULE STATUS IN DATABASE
async function updateModuleStatusInDb(moduleId, newStatus) {
  try {
    const response = await fetch('../../api/employee/modules.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ module_id: moduleId, status: newStatus })
    });
    const result = await response.json();
    if (result.status === 'success') {
      if (typeof showToast === 'function') showToast(`Module status updated to ${newStatus}.`);
      await fetchModules();
      if (typeof switchStatusTab === 'function') switchStatusTab(newStatus);
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Failed to update module status.');
    }
  } catch (err) {
    console.error('Error updating module status:', err);
    if (typeof showToast === 'function') showToast('Error updating status IN DATABASE.');
  }
}
