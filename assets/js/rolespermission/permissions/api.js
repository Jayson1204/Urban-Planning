

var rolesData = [];      
var modulesData = [];     
var actionsData = [];     
var currentUserScope = null;

window.selectedRoleId = null;
window.expandedModules = {}; 
window.savedPermissions = {}; 
window.currentPermissions = {}; 
window.isDirty = false;


async function fetchPermissionsData() {
  try {
    const response = await fetch('../../api/employee/permissions.php');
    const result = await response.json();

    if (result.status === 'success') {
      currentUserScope = result.current_user || null;
      rolesData = result.roles || [];
      actionsData = result.actions || [];
      const dbModules = result.modules || [];
      const dbResources = result.resources || [];
      const dbPermissions = result.permissions || [];
      const dbRolePermissions = result.role_permissions || [];

      // Build modulesData with nested resources
      modulesData = dbModules.map(m => {
        const resList = dbResources
          .filter(r => r.module_id === m.module_id)
          .map(r => ({
            id: r.resource_id,
            name: r.resource_name,
            desc: r.description || r.resource_route || '',
            department_id: r.department_id || m.department_id || null
          }));
        
        return {
          id: m.module_id,
          name: m.module_name,
          desc: m.description || '',
          department_id: m.department_id || null,
          icon: "fa-folder-tree",
          resources: resList
        };
      });

      const permIdMap = {};
      dbPermissions.forEach(p => {
        permIdMap[p.permission_id] = {
          resource_id: p.resource_id,
          action_id: p.action_id
        };
      });

      const permissionsMap = {};
      rolesData.forEach(r => {
        permissionsMap[r.role_id] = {};
      });

      dbRolePermissions.forEach(rp => {
        const pInfo = permIdMap[rp.permission_id];
        if (pInfo && permissionsMap[rp.role_id]) {
          if (!permissionsMap[rp.role_id][pInfo.resource_id]) {
            permissionsMap[rp.role_id][pInfo.resource_id] = [];
          }
          if (!permissionsMap[rp.role_id][pInfo.resource_id].includes(pInfo.action_id)) {
            permissionsMap[rp.role_id][pInfo.resource_id].push(pInfo.action_id);
          }
        }
      });

      window.savedPermissions = JSON.parse(JSON.stringify(permissionsMap));
      window.currentPermissions = JSON.parse(JSON.stringify(permissionsMap));

      // Determine department filtering scope
      const isSuperAdmin = (typeof window.currentUserIsSuperAdmin !== 'undefined')
        ? window.currentUserIsSuperAdmin
        : (currentUserScope ? (!!currentUserScope.is_superadmin || !!currentUserScope.is_global_access) : false);

      const userDeptId = (typeof window.currentUserDeptId !== 'undefined' && window.currentUserDeptId !== null)
        ? window.currentUserDeptId
        : (currentUserScope ? (currentUserScope.department_id || currentUserScope.role_dept_id) : null);

      const userDeptName = (typeof window.currentUserDeptName !== 'undefined')
        ? window.currentUserDeptName
        : (currentUserScope ? (currentUserScope.department_name || '') : '');

      const validRoles = rolesData.filter(r => {
        if (typeof isRoleAllowedForDepartment === 'function') {
          return isRoleAllowedForDepartment(r, userDeptId, userDeptName, isSuperAdmin);
        }
        if (isSuperAdmin || !userDeptId) return true;
        const rDeptId = r.department_id || r.role_dept_id;
        return !rDeptId || String(rDeptId) === String(userDeptId);
      });

      if (validRoles.length > 0 && (!window.selectedRoleId || !validRoles.some(r => r.role_id === window.selectedRoleId))) {
        window.selectedRoleId = validRoles[0].role_id;
        if (modulesData[0]) {
          window.expandedModules[modulesData[0].name] = true;
        }
      }

      const activeRoleObj = rolesData.find(r => r.role_id === window.selectedRoleId);
      const editHeader = document.getElementById('editingRoleHeader');
      if (editHeader && activeRoleObj) {
        editHeader.innerText = `Editing Permissions for: ${activeRoleObj.role_name}`;
      }

      const searchInput = document.getElementById('roleSearchInput');
      if (typeof renderRoleSelector === 'function') renderRoleSelector(searchInput ? searchInput.value : '');
      if (typeof renderAccordions === 'function') renderAccordions();
      if (typeof renderRestrictions === 'function') renderRestrictions();
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Error loading permissions matrix data.', true);
    }
  } catch (err) {
    console.error('Error fetching permissions matrix data:', err);
    if (typeof showToast === 'function') showToast('Network error loading permissions matrix FROM DATABASE.', true);
  } finally {
    if (typeof hidePermissionsSkeleton === 'function') {
      hidePermissionsSkeleton();
    }
  }
}

// ACTION: SAVE PERMISSION MATRIX CHANGES TO DATABASE
async function saveChanges() {
  const isSuperAdmin = currentUserScope ? !!currentUserScope.is_superadmin : false;
  const grantedActions = currentUserScope ? (currentUserScope.granted_actions || []) : [];
  let canEdit = isSuperAdmin || grantedActions.includes('EDIT') || grantedActions.includes('CREATE');

  if (window.selectedRoleId && window.currentUserRoleId && parseInt(window.selectedRoleId) === parseInt(window.currentUserRoleId)) {
    canEdit = false;
  }

  if (!canEdit) {
    if (typeof showToast === 'function') {
      const msg = (window.selectedRoleId && window.currentUserRoleId && parseInt(window.selectedRoleId) === parseInt(window.currentUserRoleId))
        ? 'Forbidden. You are not allowed to modify permissions for your own role.'
        : 'Forbidden. View-only access level cannot modify role permissions.';
      showToast(msg, true);
    }
    return;
  }

  const roleId = window.selectedRoleId;
  if (!roleId) return;

  const currentRolePerms = window.currentPermissions[roleId] || {};
  const grantedPairs = [];

  for (let resId in currentRolePerms) {
    const actIds = currentRolePerms[resId];
    if (Array.isArray(actIds)) {
      actIds.forEach(aId => {
        grantedPairs.push({
          resource_id: parseInt(resId),
          action_id: parseInt(aId)
        });
      });
    }
  }

  try {
    const response = await fetch('../../api/employee/permissions.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        role_id: roleId,
        granted_permissions: grantedPairs
      })
    });

    const result = await response.json();
    if (result.status === 'success') {
      window.savedPermissions = JSON.parse(JSON.stringify(window.currentPermissions));
      if (typeof setDirtyState === 'function') setDirtyState(false);
      const activeRoleObj = rolesData.find(r => r.role_id === roleId);
      if (typeof showToast === 'function') showToast(result.message || `Permissions saved for ${activeRoleObj ? activeRoleObj.role_name : 'Role'}.`);
    } else {
      if (typeof showToast === 'function') showToast(result.message || 'Failed to save permissions.', true);
    }
  } catch (err) {
    console.error('Error saving permissions matrix:', err);
    if (typeof showToast === 'function') showToast('Failed to save permissions TO DATABASE.', true);
  }
}
