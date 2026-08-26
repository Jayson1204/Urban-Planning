// Subdivision & Building Review (Permit Applications) Module Bootstrap
window.loadCiventralScript('assets/js/permit-applications/api.js');
window.loadCiventralScript('assets/js/permit-applications/table.js');
window.loadCiventralScript('assets/js/permit-applications/modal.js');
window.loadCiventralScript('assets/js/permit-applications/filters.js');
window.loadCiventralScript('assets/js/permit-applications/events.js', () => {
    if (document.getElementById('paTableBody') && typeof fetchApplications === 'function') {
        fetchApplications(1);
        fetchApplicationStats();
    }
});
