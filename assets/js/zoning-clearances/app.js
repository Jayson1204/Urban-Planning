// Zoning Clearances Module Bootstrap
window.loadCiventralScript('assets/js/zoning-clearances/api.js');
window.loadCiventralScript('assets/js/zoning-clearances/table.js');
window.loadCiventralScript('assets/js/zoning-clearances/modal.js');
window.loadCiventralScript('assets/js/zoning-clearances/filters.js');
window.loadCiventralScript('assets/js/zoning-clearances/events.js', () => {
    if (document.getElementById('zcTableBody') && typeof fetchClearances === 'function') {
        fetchClearances(1);
        fetchClearanceStats();
    }
});
