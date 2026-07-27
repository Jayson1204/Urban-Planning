// FILTER MODULES REAL TIME
function filterModules() {
  const searchInput = document.getElementById('moduleSearchInput');
  const statusSelect = document.getElementById('statusFilterSelect');

  const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
  const selectedStatus = statusSelect ? statusSelect.value : 'ALL';

  const filtered = systemModules.filter(mod => {
    const matchesQuery = mod.name.toLowerCase().includes(query) || 
                         (mod.desc && mod.desc.toLowerCase().includes(query));

    const matchesStatus = selectedStatus === 'ALL' || mod.status === selectedStatus;

    return matchesQuery && matchesStatus;
  });

  if (typeof renderModulesTable === 'function') renderModulesTable(filtered);
}

window.filterModules = filterModules;
