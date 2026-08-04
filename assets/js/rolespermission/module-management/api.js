// MODULE MANAGEMENT API

var systemModules = [];
var currentUserScope = null;
var archiveTargetId = null;

const LOCAL_MODULES_KEY = 'civentral_custom_modules';

function getLocalCustomModules() {
  try {
    const raw = localStorage.getItem(LOCAL_MODULES_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.error('Error reading custom modules from localStorage:', e);
    return [];
  }
}

function saveLocalCustomModule(modObj) {
  try {
    const customList = getLocalCustomModules();
    const existingIndex = customList.findIndex(m => String(m.id) === String(modObj.id) || m.name.trim().toLowerCase() === modObj.name.trim().toLowerCase());
    if (existingIndex >= 0) {
      customList[existingIndex] = { ...customList[existingIndex], ...modObj };
    } else {
      customList.unshift(modObj);
    }
    localStorage.setItem(LOCAL_MODULES_KEY, JSON.stringify(customList));
  } catch (e) {
    console.error('Error saving custom module to localStorage:', e);
  }
}

function updateLocalCustomModuleStatus(moduleId, newStatus) {
  try {
    const customList = getLocalCustomModules();
    const existingIndex = customList.findIndex(m => String(m.id) === String(moduleId));
    if (existingIndex >= 0) {
      customList[existingIndex].status = newStatus;
      customList[existingIndex].updated_at = new Date().toISOString().replace('T', ' ').substring(0, 19);
      localStorage.setItem(LOCAL_MODULES_KEY, JSON.stringify(customList));
    }
  } catch (e) {
    console.error('Error updating status in localStorage:', e);
  }
}

window.saveLocalCustomModule = saveLocalCustomModule;
window.getLocalCustomModules = getLocalCustomModules;
window.updateLocalCustomModuleStatus = updateLocalCustomModuleStatus;

// FETCH MODULES FROM Database REST API
async function fetchModules() {
  try {
    const response = await fetch('../../api/employee/modules.php');
    if (!response.ok) {
      console.warn('Fetch modules response HTTP status:', response.status);
    }
    const result = await response.json();
    const dataList = Array.isArray(result.data) ? result.data : (Array.isArray(result) ? result : (result.modules || null));
    
    const serverModules = (Array.isArray(dataList) ? dataList : []).map(m => ({
      id: m.module_id || m.id,
      name: m.module_name || m.name || 'Unassigned Module',
      desc: m.description || m.desc || '',
      status: (m.status || 'Active').trim(),
      created_at: m.created_at ? String(m.created_at).replace('T', ' ').substring(0, 19) : '',
      updated_at: m.updated_at ? String(m.updated_at).replace('T', ' ').substring(0, 19) : ''
    }));

    // Retrieve locally persisted custom modules from localStorage
    const localModules = getLocalCustomModules();

    // Merge server modules with localModules
    const mergedMap = new Map();

    // 1. Put local custom modules first
    localModules.forEach(lm => {
      if (lm && lm.id) {
        mergedMap.set(String(lm.id), lm);
      }
    });

    // 2. Put server modules next
    serverModules.forEach(sm => {
      const key = String(sm.id);
      if (mergedMap.has(key)) {
        mergedMap.set(key, { ...sm, ...mergedMap.get(key) });
      } else {
        mergedMap.set(key, sm);
      }
    });

    let combined = Array.from(mergedMap.values());
    combined.sort((a, b) => {
      const timeA = a.updated_at || a.created_at || '';
      const timeB = b.updated_at || b.created_at || '';
      if (timeA && timeB && timeA !== timeB) {
        return timeB.localeCompare(timeA);
      }
      return String(b.id).localeCompare(String(a.id), undefined, { numeric: true });
    });

    systemModules = combined;
    currentUserScope = result.current_user || currentUserScope || null;
    if (typeof filterModules === 'function') filterModules();
  } catch (err) {
    console.error('Error fetching modules FROM DATABASE:', err);
    // On network error or exception, fall back to localStorage
    const localModules = getLocalCustomModules();
    if (localModules.length > 0) {
      systemModules = localModules;
      if (typeof filterModules === 'function') filterModules();
    }
    if (typeof showToast === 'function') showToast('Network error connecting to Database.');
  } finally {
    if (typeof hideModuleSkeleton === 'function') {
      hideModuleSkeleton();
    }
  }
}

// UPDATE MODULE STATUS IN DATABASE
async function updateModuleStatusInDb(moduleId, newStatus) {
  // Update local state immediately so UI updates instantly
  const modIndex = systemModules.findIndex(m => String(m.id) === String(moduleId));
  if (modIndex >= 0) {
    systemModules[modIndex].status = newStatus;
    if (typeof filterModules === 'function') filterModules();
  }
  updateLocalCustomModuleStatus(moduleId, newStatus);

  try {
    const response = await fetch('../../api/employee/modules.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ module_id: moduleId, status: newStatus })
    });
    const result = await response.json();
    const isSuccess = response.ok && (
      result.status === 'success' || 
      result.success === true || 
      result.code === 200 || 
      !result.status
    );

    if (isSuccess) {
      if (typeof showToast === 'function') showToast(`Module status updated to ${newStatus}.`);
      await fetchModules();
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Failed to update module status.');
      await fetchModules(); // Revert state if failed
    }
  } catch (err) {
    console.error('Error updating module status:', err);
    if (typeof showToast === 'function') showToast('Error updating status IN DATABASE.');
    await fetchModules();
  }
}
