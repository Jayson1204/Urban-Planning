// Infrastructure Records Module Bootstrap
window.loadCiventralScript('assets/js/infrastructure-records/api.js');
window.loadCiventralScript('assets/js/infrastructure-records/table.js');
window.loadCiventralScript('assets/js/infrastructure-records/modal.js');
window.loadCiventralScript('assets/js/infrastructure-records/filters.js');
window.loadCiventralScript('assets/js/infrastructure-records/events.js', () => {
    if (document.getElementById('infraTableBody') && typeof fetchInfraRecords === 'function') {
        fetchInfraRecords(1);
        fetchInfraStats();
    }
});
