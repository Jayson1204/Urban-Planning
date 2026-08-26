// Subdivisions Module Bootstrap
window.loadCiventralScript('assets/js/subdivisions/api.js');
window.loadCiventralScript('assets/js/subdivisions/table.js');
window.loadCiventralScript('assets/js/subdivisions/modal.js');
window.loadCiventralScript('assets/js/subdivisions/filters.js');
window.loadCiventralScript('assets/js/subdivisions/events.js', () => {
    if (document.getElementById('subdivisionsTableBody') && typeof fetchSubdivisionsList === 'function') {
        loadSubdivisionBarangayOptions();
        fetchSubdivisionsList(1);
    }
});
