// Housing Relocations Module Bootstrap
window.loadCiventralScript('assets/js/housing-relocations/api.js');
window.loadCiventralScript('assets/js/housing-relocations/table.js');
window.loadCiventralScript('assets/js/housing-relocations/modal.js');
window.loadCiventralScript('assets/js/housing-relocations/filters.js');
window.loadCiventralScript('assets/js/housing-relocations/events.js', () => {
    if (document.getElementById('relocationsTableBody') && typeof fetchRelocations === 'function') {
        fetchRelocations(1);
        fetchRelocationStats();
    }
});
