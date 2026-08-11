// Urban Planning Module Bootstrap
window.loadCiventralScript('assets/js/urban-planning/api.js');
window.loadCiventralScript('assets/js/urban-planning/table.js');
window.loadCiventralScript('assets/js/urban-planning/modal.js');
window.loadCiventralScript('assets/js/urban-planning/filters.js');
window.loadCiventralScript('assets/js/urban-planning/events.js', () => {
    // Only execute if we are actually on the development plans page
    if (document.getElementById('developmentPlansTableBody') && typeof fetchDevelopmentPlans === 'function') {
        fetchDevelopmentPlans(1);
        fetchPlanStats();
    }
});
