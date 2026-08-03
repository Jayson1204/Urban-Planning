// Resident Module Bootstrap
window.loadCiventralScript('assets/js/resident/api.js');
window.loadCiventralScript('assets/js/resident/table.js');
window.loadCiventralScript('assets/js/resident/modal.js');
window.loadCiventralScript('assets/js/resident/filters.js');
window.loadCiventralScript('assets/js/resident/events.js', () => {
    // Only execute if we are actually on the resident directory page
    if (document.getElementById('residentsTableBody') && typeof fetchResidents === 'function') {
        fetchResidents(1);
        fetchResidentStats();
    }
});
