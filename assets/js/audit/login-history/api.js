window.civAudit = window.civAudit || {};
window.civAudit.loginHistory = window.civAudit.loginHistory || {};

window.civAudit.loginHistory.api = {
  allLoginLogs: [],

  async fetchLoginHistory() {
    const tbody = document.getElementById('loginTableBody');
    if (tbody) {
      tbody.innerHTML = `
        <tr id="loadingRow">
          <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">
            <i class="fa-solid fa-spinner fa-spin text-2xl mb-3 block text-[#0f172a]"></i>
            Fetching real-time login history audit logs from database...
          </td>
        </tr>
      `;
    }

    try {
      const basePath = (typeof window.civentralBasePath !== 'undefined') ? window.civentralBasePath : '../../';
      const res = await fetch(basePath + 'api/employee/login-history.php');
      const json = await res.json();
      if (json.status === 'success') {
        if (json.metrics) {
          const sucEl = document.getElementById('successfulCount');
          const failEl = document.getElementById('failedCount');
          const actEl = document.getElementById('activeCount');
          const lockEl = document.getElementById('lockCount');
          if (sucEl) sucEl.innerText = json.metrics.successfulCount || 0;
          if (failEl) failEl.innerText = json.metrics.failedCount || 0;
          if (actEl) actEl.innerText = json.metrics.activeCount || 0;
          if (lockEl) lockEl.innerText = json.metrics.lockCount || 0;
        }
        if (json.departments && window.civAudit.loginHistory.ui) {
          window.civAudit.loginHistory.ui.populateDepartments(json.departments);
        }
        if (json.data) {
          this.allLoginLogs = json.data || [];
          if (window.civAudit.loginHistory.filters) {
            window.civAudit.loginHistory.filters.applyFilters();
          }
        }
      } else {
        if (window.showToast) {
          window.showToast('Notice', json.message || 'Unable to load login history logs.');
        }
        if (window.civAudit.loginHistory.ui) {
          window.civAudit.loginHistory.ui.renderPaginatedTable([], 1);
        }
      }
    } catch (err) {
      console.error('Error fetching login history:', err);
      if (window.showToast) {
        window.showToast('Error', 'Failed to connect to authentication audit server.');
      }
      if (window.civAudit.loginHistory.ui) {
        window.civAudit.loginHistory.ui.renderPaginatedTable([], 1);
      }
    }
  }
};
