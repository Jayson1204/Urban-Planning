// Field Survey Results Module Bootstrap
window.loadCiventralScript('assets/js/field-survey-results/api.js');
window.loadCiventralScript('assets/js/field-survey-results/table.js');
window.loadCiventralScript('assets/js/field-survey-results/modal.js');
window.loadCiventralScript('assets/js/field-survey-results/filters.js');
window.loadCiventralScript('assets/js/field-survey-results/events.js', () => {
    // Only execute if we are actually on the survey results page
    if (document.getElementById('resultsTableBody') && typeof fetchResults === 'function') {
        fetchResults(1);
        fetchResultStats();
        loadAssignmentOptions();
    }
});
