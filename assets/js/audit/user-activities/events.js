window.civAudit = window.civAudit || {};
window.civAudit.userActivities = window.civAudit.userActivities || {};

window.civAudit.userActivities.events = {
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

  mockExport(type, event) {
    if (event) event.preventDefault();
    this.hideExportDropdown();

    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Activity ID,Date Time,Actor Identity,Role,Department,Module,Action,Description,IP Address,Status\r\n";

    const filterDate = document.getElementById('filterDate');
    const filterSearch = document.getElementById('filterSearch');
    const filterDepartment = document.getElementById('filterDepartment');
    const filterModule = document.getElementById('filterModule');
    const filterAction = document.getElementById('filterAction');
    const filterStatus = document.getElementById('filterStatus');

    const dateVal = filterDate ? filterDate.value : '';
    const searchVal = filterSearch ? filterSearch.value.toLowerCase().trim() : '';
    const deptVal = filterDepartment ? filterDepartment.value : 'All';
    const moduleVal = filterModule ? filterModule.value : 'All';
    const actionVal = filterAction ? filterAction.value : 'All';
    const statusVal = filterStatus ? filterStatus.value : 'All';

    const api = window.civAudit.userActivities.api;

    const filtered = api.auditLogsData.filter(item => {
      const matchDate = !dateVal || item.date === dateVal;
      const matchSearch = !searchVal || item.actor.toLowerCase().includes(searchVal) || item.id.toLowerCase().includes(searchVal) || item.desc.toLowerCase().includes(searchVal);
      const matchDept = (deptVal === 'All' || item.department === deptVal);
      const matchModule = (moduleVal === 'All' || item.module === moduleVal);
      const matchAction = (actionVal === 'All' || item.action === actionVal);
      const matchStatus = (statusVal === 'All' || item.status === statusVal);
      return matchDate && matchSearch && matchDept && matchModule && matchAction && matchStatus;
    });

    filtered.forEach(log => {
      const row = `"${log.id}","${log.dateTimeStr}","${log.actor}","${log.role}","${log.department}","${log.module}","${log.action}","${log.desc.replace(/"/g, '""')}","${log.ip}","${log.status}"`;
      csvContent += row + "\r\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    const ext = (type === 'Excel') ? 'csv' : 'csv';
    link.setAttribute("download", `Civentral_Audit_Logs_${new Date().toISOString().split('T')[0]}.${ext}`);
    document.body.appendChild(link);
    
    link.click();
    document.body.removeChild(link);

    if (window.showToast) {
      window.showToast(`Export Success`, `Exported ${filtered.length} audit logs as ${type} file.`);
    }
  },

  copyModalPayload() {
    const payloadText = document.getElementById('modalPayloadText').textContent;
    navigator.clipboard.writeText(payloadText).then(() => {
      if (window.showToast) {
        window.showToast("Payload Copied", "JSON payload details copied to clipboard.");
      }
    }).catch(err => {
      console.error('Copy failed: ', err);
    });
  },

  registerListeners() {
    document.addEventListener('click', (event) => {
      const container = document.getElementById('exportDropdownContainer');
      if (container && !container.contains(event.target)) {
        this.hideExportDropdown();
      }

      const modal = document.getElementById('logDetailsModal');
      const card = document.getElementById('modalCard');
      if (modal && !modal.classList.contains('hidden') && card && !card.contains(event.target)) {
        const row = event.target.closest('#auditTableBody tr');
        if (!row && window.civAudit.userActivities.modal) {
          window.civAudit.userActivities.modal.closeLogDetailsModal();
        }
      }
    });

    // Make functions globally available for HTML inline handlers
    window.toggleExportDropdown = (e) => this.toggleExportDropdown(e);
    window.mockExport = (type, e) => this.mockExport(type, e);
    window.copyModalPayload = () => this.copyModalPayload();
    window.openLogDetailsModal = (row) => window.civAudit.userActivities.modal.openLogDetailsModal(row);
    window.closeLogDetailsModal = () => window.civAudit.userActivities.modal.closeLogDetailsModal();
    window.applyFilters = () => window.civAudit.userActivities.filters.applyFilters();
    window.resetFilters = () => window.civAudit.userActivities.filters.resetFilters();
  }
};
