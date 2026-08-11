// Household Management Module Bootstrap
window.loadCiventralScript('assets/js/household/api.js');
window.loadCiventralScript('assets/js/household/table.js');
window.loadCiventralScript('assets/js/household/modal.js');
window.loadCiventralScript('assets/js/household/filters.js');
window.loadCiventralScript('assets/js/household/events.js', () => {
    if (document.getElementById('householdsTableBody') && typeof fetchHouseholds === 'function') {
        fetchHouseholds(1);
        fetchHouseholdStats();
    }
});
