// RESOURCE MANAGEMENT API

var systemResources = [];
var systemModulesList = [];
var currentUserScope = null;
var archiveTargetResourceId = null;

// FETCH RESOURCES AND MODULES FROM Database API
async function fetchResources() {
  try {
    const response = await fetch('../../api/employee/resources.php');
    const result = await response.json();
    if (result.status === 'success') {
      currentUserScope = result.current_user || null;
      if (Array.isArray(result.modules)) {
        systemModulesList = result.modules;
        populateModuleSelects();
      }

      if (Array.isArray(result.data)) {
        systemResources = result.data.map(r => ({
          id: r.resource_id,
          module_id: r.module_id,
          module: r.modules ? r.modules.module_name : (systemModulesList.find(m => m.module_id === r.module_id)?.module_name || 'Unassigned'),
          name: r.resource_name,
          route: r.resource_route || '',
          desc: r.description || '',
          status: r.status || 'Active',
          created_at: r.created_at ? r.created_at.replace('T', ' ').substring(0, 19) : '',
          updated_at: r.updated_at ? r.updated_at.replace('T', ' ').substring(0, 19) : ''
        }));
        if (typeof filterResources === 'function') filterResources();
      }
    } else {
      console.warn('Resources fetch notice:', result.message);
    }
  } catch (err) {
    console.error('Error fetching resources FROM DATABASE:', err);
    if (typeof showToast === 'function') showToast('Network error connecting to Database.');
  }
}

// DYNAMICALLY POPULATE PARENT MODULE SELECT DROPDOWNS
function populateModuleSelects() {
  const filterSelect = document.getElementById('parentModuleFilter');
  const modalSelect = document.getElementById('resourceParentModule');

  if (filterSelect) {
    const curVal = filterSelect.value || 'ALL';
    let optionsHtml = '<option value="ALL">All Parent Modules</option>';
    systemModulesList.forEach(m => {
      optionsHtml += `<option value="${m.module_id}">${m.module_name}</option>`;
    });
    filterSelect.innerHTML = optionsHtml;
    filterSelect.value = curVal;
  }

  if (modalSelect) {
    const curVal = modalSelect.value;
    let optionsHtml = '';
    systemModulesList.forEach(m => {
      optionsHtml += `<option value="${m.module_id}">${m.module_name}</option>`;
    });
    modalSelect.innerHTML = optionsHtml;
    if (curVal) modalSelect.value = curVal;
  }
}

// UPDATE RESOURCE STATUS IN DATABASE
async function updateResourceStatusInDb(resourceId, newStatus) {
  try {
    const response = await fetch('../../api/employee/resources.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ resource_id: resourceId, status: newStatus })
    });
    const result = await response.json();
    if (result.status === 'success') {
      if (typeof showToast === 'function') showToast(`Resource status updated to ${newStatus}.`);
      await fetchResources();
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Failed to update resource status.');
    }
  } catch (err) {
    console.error('Error updating status:', err);
    if (typeof showToast === 'function') showToast('Error updating status IN DATABASE.');
  }
}
