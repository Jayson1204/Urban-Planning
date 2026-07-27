window.civAudit = window.civAudit || {};
window.civAudit.userActivities = window.civAudit.userActivities || {};

window.civAudit.userActivities.api = {
  auditLogsData: [],
  availableDepartments: [],
  availableModules: [],

  async fetchAuditLogs() {
    const tbody = document.getElementById('auditTableBody');
    if (tbody) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="py-12 text-center text-slate-400 font-semibold">
            <i class="fa-solid fa-spinner fa-spin text-2xl mb-3 block text-slate-900"></i>
            Fetching real-time audit logs FROM DATABASE...
          </td>
        </tr>
      `;
    }

    try {
      const response = await fetch('../../api/employee/audit-logs.php');
      const result = await response.json();

      if (result.status === 'success' && Array.isArray(result.data)) {
        this.availableDepartments = result.departments || [];
        this.availableModules = result.modules || [];
        
        if (window.civAudit.userActivities.ui) {
          window.civAudit.userActivities.ui.populateFilterDropdowns();
        }

        this.auditLogsData = result.data.map(log => {
          const userObj = log.users || {};
          const firstName = userObj.first_name || '';
          const lastName = userObj.last_name || '';
          const actorName = `${firstName} ${lastName}`.trim() || userObj.email || 'System / Guest';
          
          const roleObj = userObj.roles || {};
          const roleName = roleObj.role_name || 'Staff';
          const rolePrefix = roleObj.role_prefix || 'STF';

          let roleColor = 'gray';
          if (roleName.includes('Super') || rolePrefix === 'SADM') {
            roleColor = 'purple';
          } else if (roleName.includes('Admin') || rolePrefix.includes('ADM')) {
            roleColor = 'blue';
          }

          const deptName = log.departments?.department_name || 'General Services';
          const moduleName = log.modules?.module_name || 'System Portal';

          const rawDate = log.created_at || new Date().toISOString();
          const dtObj = new Date(rawDate);
          const dateISO = dtObj.toISOString().split('T')[0];
          
          const timePart = dtObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
          const datePart = dtObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
          const dateTimeStr = `${datePart} at ${timePart}`;

          let payloadObj = log.context_json;
          if (typeof payloadObj === 'string') {
            try { payloadObj = JSON.parse(payloadObj); } catch(e) {}
          }
          if (!payloadObj) {
            payloadObj = {
              actor: { id: log.actor_user_id, email: userObj.email || null },
              target: { table: log.target_table || 'audit_logs', id: log.target_id || log.audit_id },
              network: { ip: log.ip_address || '127.0.0.1', method: log.request_method || 'POST', uri: log.request_uri || '' }
            };
          }

          return {
            id: `#ACT-${log.audit_id}`,
            audit_id: log.audit_id,
            date: dateISO,
            datePart: datePart,
            timePart: timePart,
            dateTimeStr: dateTimeStr,
            actor: actorName,
            role: roleName,
            roleColor: roleColor,
            department: deptName,
            module: moduleName,
            action: log.action || 'View',
            desc: log.description || 'Operational action logged in system audit trail.',
            ip: log.ip_address || '127.0.0.1',
            device: `${log.browser || 'Browser'} - ${log.operating_system || 'OS'}`,
            status: log.status || 'Success',
            payloadStr: JSON.stringify(payloadObj)
          };
        });

        if (window.civAudit.userActivities.filters) {
          window.civAudit.userActivities.filters.applyFilters();
        }
      } else {
        if (tbody) {
          tbody.innerHTML = `
            <tr>
              <td colspan="7" class="py-12 text-center text-rose-500 font-semibold">
                <i class="fa-solid fa-triangle-exclamation text-3xl mb-3 block"></i>
                Failed to load audit logs.
              </td>
            </tr>
          `;
        }
      }
    } catch (err) {
      console.error('Error fetching audit logs:', err);
      if (tbody) {
        tbody.innerHTML = `
          <tr>
            <td colspan="7" class="py-12 text-center text-rose-500 font-semibold">
              <i class="fa-solid fa-wifi text-3xl mb-3 block"></i>
              Connection error connecting to audit log server.
            </td>
          </tr>
        `;
      }
    }
  }
};
