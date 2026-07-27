window.civAudit = window.civAudit || {};
window.civAudit.dataChanges = window.civAudit.dataChanges || {};

window.civAudit.dataChanges.api = {
  auditLogsData: [],
  availableModules: [],

  async fetchMutationLogs() {
    const tbody = document.getElementById('mutationTableBody');
    if (tbody) {
      tbody.innerHTML = `
        <tr id="loadingRow">
          <td colspan="9" class="py-12 text-center text-slate-400 font-semibold">
            <i class="fa-solid fa-spinner fa-spin text-2xl mb-3 block text-[#0f172a]"></i>
            Loading real-time data mutation audit logs from database...
          </td>
        </tr>
      `;
    }

    try {
      const response = await fetch('../../api/employee/audit-logs.php');
      const result = await response.json();

      if (result.status === 'success' && Array.isArray(result.data)) {
        this.auditLogsData = result.data;
        this.availableModules = result.modules || [];

        if (window.civAudit.dataChanges.ui) {
          window.civAudit.dataChanges.ui.populateModuleDropdown();
          window.civAudit.dataChanges.ui.renderMutationLogs(this.auditLogsData);
        }
      } else {
        if (window.showToast) {
          window.showToast('Notice', result.message || 'Unable to load audit logs.');
        }
        if (window.civAudit.dataChanges.ui) {
          window.civAudit.dataChanges.ui.renderMutationLogs([]);
        }
      }
    } catch (err) {
      console.error('Error fetching audit logs:', err);
      if (window.showToast) {
        window.showToast('Error', 'Failed to connect to audit logs database.');
      }
      if (window.civAudit.dataChanges.ui) {
        window.civAudit.dataChanges.ui.renderMutationLogs([]);
      }
    }
  }
};
