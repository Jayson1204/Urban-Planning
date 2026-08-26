// Housing Projects Module Bootstrap
window.loadCiventralScript('assets/js/housing-projects/api.js');
window.loadCiventralScript('assets/js/housing-projects/table.js');
window.loadCiventralScript('assets/js/housing-projects/modal.js');
window.loadCiventralScript('assets/js/housing-projects/filters.js');
window.loadCiventralScript('assets/js/housing-projects/events.js', () => {
    if (document.getElementById('hpTableBody') && typeof fetchHousingProjectsList === 'function') {
        loadHpBarangayOptions();
        fetchHousingProjectsList(1);
    }
});
