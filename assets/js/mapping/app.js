// Barangay Mapping Module Bootstrap
window.loadCiventralScript('assets/js/mapping/api.js');
window.loadCiventralScript('assets/js/mapping/map.js');
window.loadCiventralScript('assets/js/mapping/events.js', () => {
    if (document.getElementById('barangayMap') && typeof initBarangayMappingModule === 'function') {
        initBarangayMappingModule();
    }
});
