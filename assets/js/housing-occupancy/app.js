// Housing Occupancy Module Bootstrap
window.loadCiventralScript('assets/js/housing-occupancy/api.js');
window.loadCiventralScript('assets/js/housing-occupancy/table.js');
window.loadCiventralScript('assets/js/housing-occupancy/modal.js');
window.loadCiventralScript('assets/js/housing-occupancy/filters.js');
window.loadCiventralScript('assets/js/housing-occupancy/events.js', () => {
    if (document.getElementById('occupancyTableBody') && typeof fetchOccupancy === 'function') {
        fetchOccupancy(1);
        fetchOccupancyStats();
    }
});
