window.civAudit = window.civAudit || {};
window.civAudit.dataChanges = window.civAudit.dataChanges || {};

window.civAudit.dataChanges.events = {
  toggleExportDropdown(event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById('exportDropdownMenu');
    if (!menu) return;
    if (menu.classList.contains('hidden')) {
      menu.classList.remove('hidden');
      setTimeout(() => {
        menu.classList.remove('scale-95', 'opacity-0');
        menu.classList.add('scale-100', 'opacity-100');
      }, 10);
    } else {
      this.hideExportDropdown();
    }
  },

  hideExportDropdown() {
    const menu = document.getElementById('exportDropdownMenu');
    if (menu) {
      menu.classList.remove('scale-100', 'opacity-100');
      menu.classList.add('scale-95', 'opacity-0');
      setTimeout(() => {
        menu.classList.add('hidden');
      }, 150);
    }
  },

  exportLogs(type, event) {
    if (event) event.preventDefault();
    this.hideExportDropdown();

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Change ID,Date Time,Actor,Module,Record ID,Field/Action,Old Value,New Value,Description\r\n";

    const filterSearch = document.getElementById('filterSearch');
    const filterDate = document.getElementById('filterDate');
    const filterModule = document.getElementById('filterModule');
    const searchVal = filterSearch ? filterSearch.value.toLowerCase().trim() : '';
    const dateVal = filterDate ? filterDate.value : '';
    const moduleVal = filterModule ? filterModule.value : 'All';

    const api = window.civAudit.dataChanges.api;
    const filtered = api.auditLogsData.filter(log => {
      let actorName = 'System';
      if (log.users) actorName = `${log.users.first_name || ''} ${log.users.last_name || ''}`.trim() || log.users.email || 'User';
      const recordId = log.target_id || log.session_id || '';
      const action = log.action || '';
      const desc = log.description || '';
      const rawDate = log.created_at || '';
      const isoDate = rawDate.split(' ')[0] || '';
      const modName = (log.modules && log.modules.module_name) ? log.modules.module_name : (log.target_table || 'System');

      const matchSearch = !searchVal || 
                          actorName.toLowerCase().includes(searchVal) || 
                          recordId.toString().toLowerCase().includes(searchVal) || 
                          action.toLowerCase().includes(searchVal) || 
                          desc.toLowerCase().includes(searchVal);
      const matchDate = !dateVal || isoDate === dateVal;
      const matchModule = moduleVal === 'All' || modName === moduleVal;

      return matchSearch && matchDate && matchModule;
    });

    filtered.forEach(log => {
      let actorName = 'System';
      if (log.users) actorName = `${log.users.first_name || ''} ${log.users.last_name || ''}`.trim() || log.users.email || 'User';
      const recordId = log.target_id || log.session_id || 'REC-CORE';
      const modName = (log.modules && log.modules.module_name) ? log.modules.module_name : (log.target_table || 'System');
      const action = log.action || 'Data Update';
      const isSuccess = (log.status || 'Success') === 'Success';
      const oldVal = isSuccess ? (log.target_table ? `${log.target_table}` : 'Active') : 'Attempted';
      const newVal = log.status || 'Success';

      const row = `"${log.audit_id}","${log.created_at || ''}","${actorName}","${modName}","${recordId}","${action}","${oldVal}","${newVal}","${(log.description || '').replace(/"/g, '""')}"`;
      csvContent += row + "\r\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `Civentral_Data_Mutations_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    if (window.showToast) {
      window.showToast(`Export Success`, `Exported ${filtered.length} mutation logs as ${type} file.`);
    }
  },

  registerListeners() {
    document.addEventListener('click', (event) => {
      const container = document.getElementById('exportDropdownContainer');
      if (container && !container.contains(event.target)) {
        this.hideExportDropdown();
      }

      const modal = document.getElementById('mutationDetailsModal');
      const card = document.getElementById('modalCard');
      if (modal && !modal.classList.contains('hidden') && card && !card.contains(event.target)) {
        const row = event.target.closest('#mutationTableBody tr');
        if (!row && window.civAudit.dataChanges.modal) {
          window.civAudit.dataChanges.modal.closeMutationModal();
        }
      }
    });

    // Make functions globally available for inline HTML handlers
    window.toggleExportDropdown = (e) => this.toggleExportDropdown(e);
    window.exportLogs = (type, e) => this.exportLogs(type, e);
    window.openMutationModal = (row) => window.civAudit.dataChanges.modal.openMutationModal(row);
    window.closeMutationModal = () => window.civAudit.dataChanges.modal.closeMutationModal();
    window.applyFilters = () => window.civAudit.dataChanges.filters.applyFilters();
    window.resetFilters = () => window.civAudit.dataChanges.filters.resetFilters();
    window.refreshLogs = () => window.civAudit.dataChanges.api.fetchMutationLogs();
  }
};
