// ROLES MANAGEMENT FILTERS

// FILTER ROLES IN REAL TIME
function filterRoles() {
  const searchInput = document.getElementById('roleSearchInput');
  const globalAccessFilter = document.getElementById('globalAccessFilterSelect');
  const statusFilter = document.getElementById('statusFilterSelect');

  const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
  const globalVal = globalAccessFilter ? globalAccessFilter.value : 'ALL';
  const statusVal = statusFilter ? statusFilter.value : 'ALL';

  const filtered = systemRoles.filter(role => {
    const matchesQuery = !query || 
                         (role.role_name || '').toLowerCase().includes(query) || 
                         (role.role_prefix || '').toLowerCase().includes(query) || 
                         (role.description || '').toLowerCase().includes(query) ||
                         (query === 'system' && role.is_system_role);

    const matchesGlobal = globalVal === 'ALL' || 
                          (globalVal === 'GLOBAL' && role.is_global_access) || 
                          (globalVal === 'DEPARTMENT' && !role.is_global_access);

    const matchesStatus = statusVal === 'ALL' || role.status === statusVal;

    return matchesQuery && matchesGlobal && matchesStatus;
  });

  if (typeof renderRoles === 'function') renderRoles(filtered);
}

// INTERACTIVE METRIC CARD FILTER & NAVIGATION
function filterByCard(type) {
  const globalSelect = document.getElementById('globalAccessFilterSelect');
  const statusSelect = document.getElementById('statusFilterSelect');
  const searchInputEl = document.getElementById('roleSearchInput');

  // Clear card highlight rings
  document.querySelectorAll('.role-metric-card').forEach(card => {
    card.classList.remove('ring-2', 'ring-cyan-500', 'ring-blue-500', 'ring-emerald-500', 'ring-amber-500', 'ring-brand-medium');
  });

  if (type === 'ALL') {
    if (globalSelect) globalSelect.value = 'ALL';
    if (statusSelect) statusSelect.value = 'ALL';
    if (searchInputEl) searchInputEl.value = '';
    const card = document.getElementById('cardTotalRoles');
    if (card) card.classList.add('ring-2', 'ring-cyan-500');
  } else if (type === 'GLOBAL') {
    if (globalSelect) globalSelect.value = 'GLOBAL';
    if (statusSelect) statusSelect.value = 'ALL';
    if (searchInputEl) searchInputEl.value = '';
    const card = document.getElementById('cardGlobalRoles');
    if (card) card.classList.add('ring-2', 'ring-blue-500');
  } else if (type === 'ACTIVE') {
    if (statusSelect) statusSelect.value = 'Active';
    if (globalSelect) globalSelect.value = 'ALL';
    if (searchInputEl) searchInputEl.value = '';
    const card = document.getElementById('cardActiveRoles');
    if (card) card.classList.add('ring-2', 'ring-emerald-500');
  } else if (type === 'SYSTEM') {
    if (globalSelect) globalSelect.value = 'ALL';
    if (statusSelect) statusSelect.value = 'ALL';
    if (searchInputEl) searchInputEl.value = 'system';
    const card = document.getElementById('cardSystemRoles');
    if (card) card.classList.add('ring-2', 'ring-amber-500');
  }

  // Trigger filtering
  filterRoles();

  // Scroll to table workspace smoothly
  const targetTable = document.getElementById('roleSearchInput');
  if (targetTable) {
    targetTable.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}
