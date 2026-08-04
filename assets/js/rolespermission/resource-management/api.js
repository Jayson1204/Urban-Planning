// RESOURCE MANAGEMENT API

var systemResources = [];
var systemModulesList = [];
var currentUserScope = null;
var archiveTargetResourceId = null;

const LOCAL_RESOURCES_KEY = 'civentral_custom_resources';

function getLocalCustomResources() {
  try {
    const raw = localStorage.getItem(LOCAL_RESOURCES_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.error('Error reading custom resources from localStorage:', e);
    return [];
  }
}

function saveLocalCustomResource(resObj) {
  try {
    const customList = getLocalCustomResources();
    const cleanName = (resObj.name || '').trim().toLowerCase();
    const existingIndex = customList.findIndex(r => 
      String(r.id) === String(resObj.id) || 
      (r.name || '').trim().toLowerCase() === cleanName
    );
    if (existingIndex >= 0) {
      customList[existingIndex] = { ...customList[existingIndex], ...resObj };
    } else {
      customList.unshift(resObj);
    }
    localStorage.setItem(LOCAL_RESOURCES_KEY, JSON.stringify(customList));
  } catch (e) {
    console.error('Error saving custom resource to localStorage:', e);
  }
}

function updateLocalCustomResourceStatus(resourceId, newStatus) {
  try {
    const customList = getLocalCustomResources();
    const existingIndex = customList.findIndex(r => String(r.id) === String(resourceId));
    if (existingIndex >= 0) {
      customList[existingIndex].status = newStatus;
      customList[existingIndex].updated_at = new Date().toISOString().replace('T', ' ').substring(0, 19);
      localStorage.setItem(LOCAL_RESOURCES_KEY, JSON.stringify(customList));
    }
  } catch (e) {
    console.error('Error updating resource status in localStorage:', e);
  }
}

window.saveLocalCustomResource = saveLocalCustomResource;
window.getLocalCustomResources = getLocalCustomResources;
window.updateLocalCustomResourceStatus = updateLocalCustomResourceStatus;

// FETCH RESOURCES AND MODULES FROM Database API
async function fetchResources() {
  try {
    const response = await fetch('../../api/employee/resources.php');
    if (!response.ok) {
      console.warn('Fetch resources response HTTP status:', response.status);
    }
    const result = await response.json();
    if (result.status === 'success' || result.success || response.ok) {
      currentUserScope = result.current_user || currentUserScope || null;
      let rawModules = Array.isArray(result.modules) ? result.modules : [];

      // Read custom modules from localStorage
      let customModules = [];
      try {
        const raw = localStorage.getItem('civentral_custom_modules');
        customModules = raw ? JSON.parse(raw) : [];
      } catch (e) {
        console.error('Error reading civentral_custom_modules:', e);
      }

      // Merge rawModules with customModules
      const modMap = new Map();
      rawModules.forEach(m => {
        const mKey = String(m.module_id || m.id);
        modMap.set(mKey, m);
      });

      customModules.forEach(cm => {
        if (!cm || !cm.name) return;
        const key = String(cm.id || cm.module_id);
        const nameLower = cm.name.trim().toLowerCase();

        let foundKey = null;
        for (let [k, existing] of modMap.entries()) {
          if (k === key || (existing.module_name || existing.name || '').trim().toLowerCase() === nameLower) {
            foundKey = k;
            break;
          }
        }

        if (foundKey) {
          modMap.set(foundKey, {
            ...modMap.get(foundKey),
            module_name: cm.name,
            description: cm.desc || cm.description || '',
            status: cm.status || modMap.get(foundKey).status || 'Active'
          });
        } else {
          modMap.set(key, {
            module_id: cm.id,
            module_name: cm.name,
            description: cm.desc || cm.description || '',
            status: cm.status || 'Active'
          });
        }
      });

      systemModulesList = Array.from(modMap.values());
      populateModuleSelects();

      // Merge server resources with custom local resources
      const localResources = getLocalCustomResources();
      const resMap = new Map();

      // 1. Insert local custom resources first
      localResources.forEach(lr => {
        if (lr && lr.id) {
          resMap.set(String(lr.id), lr);
        }
      });

      // 2. Insert server resources next
      if (Array.isArray(result.data)) {
        result.data.forEach(r => {
          const mKey = String(r.resource_id);
          const modObj = systemModulesList.find(m => String(m.module_id || m.id) === String(r.module_id));
          const serverObj = {
            id: r.resource_id,
            module_id: r.module_id,
            module: r.modules ? r.modules.module_name : (modObj ? (modObj.module_name || modObj.name) : 'Unassigned'),
            name: r.resource_name,
            route: r.resource_route || '',
            desc: r.description || '',
            status: r.status || 'Active',
            created_at: r.created_at ? r.created_at.replace('T', ' ').substring(0, 19) : '',
            updated_at: r.updated_at ? r.updated_at.replace('T', ' ').substring(0, 19) : ''
          };

          if (resMap.has(mKey)) {
            resMap.set(mKey, { ...serverObj, ...resMap.get(mKey) });
          } else {
            resMap.set(mKey, serverObj);
          }
        });
      }

      let combined = Array.from(resMap.values());

      // Ensure items are sorted so newest created/updated resources ALWAYS render at the top (Page 1)
      combined.sort((a, b) => {
        const timeA = a.updated_at || a.created_at || '';
        const timeB = b.updated_at || b.created_at || '';
        if (timeA && timeB && timeA !== timeB) {
          return timeB.localeCompare(timeA); // Descending order (newest first)
        }
        return String(b.id).localeCompare(String(a.id), undefined, { numeric: true });
      });

      systemResources = combined;
      if (typeof filterResources === 'function') filterResources();
    } else {
      console.warn('Resources fetch notice:', result.message);
    }
  } catch (err) {
    console.error('Error fetching resources FROM DATABASE:', err);
    const localResources = getLocalCustomResources();
    if (localResources.length > 0) {
      systemResources = localResources;
      if (typeof filterResources === 'function') filterResources();
    }
    if (typeof showToast === 'function') showToast('Network error connecting to Database.');
  } finally {
    if (typeof hideResourceSkeleton === 'function') {
      hideResourceSkeleton();
    }
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
      const mId = m.module_id || m.id;
      const mName = m.module_name || m.name || 'Unassigned Module';
      optionsHtml += `<option value="${mId}">${mName}</option>`;
    });
    filterSelect.innerHTML = optionsHtml;
    filterSelect.value = curVal;
  }

  if (modalSelect) {
    const curVal = modalSelect.value;
    let optionsHtml = '';
    systemModulesList.forEach(m => {
      const isArchived = (m.status || '').toString().trim().toLowerCase() === 'archived';
      if (isArchived) return; // Exclude archived modules from parent module selection modal

      const mId = m.module_id || m.id;
      const mName = m.module_name || m.name || 'Unassigned Module';
      optionsHtml += `<option value="${mId}">${mName}</option>`;
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
      if (typeof switchStatusTab === 'function') switchStatusTab(newStatus);
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Failed to update resource status.');
    }
  } catch (err) {
    console.error('Error updating status:', err);
    if (typeof showToast === 'function') showToast('Error updating status IN DATABASE.');
  }
}
