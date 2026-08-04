// Housing Module Bootstrap
window.loadCiventralScript('assets/js/housing/api.js');
window.loadCiventralScript('assets/js/housing/table.js');
window.loadCiventralScript('assets/js/housing/modal.js');
window.loadCiventralScript('assets/js/housing/filters.js');
window.loadCiventralScript('assets/js/housing/events.js', () => {
    // Only execute if we are actually on the housing units page
    if (document.getElementById('housingUnitsTableBody') && typeof fetchHousingUnits === 'function') {
        fetchHousingUnits(1);
        fetchHousingStats();
    }
});
