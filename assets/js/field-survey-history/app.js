// Field Survey History Module Bootstrap
window.loadCiventralScript('assets/js/field-survey-history/api.js');
window.loadCiventralScript('assets/js/field-survey-history/table.js');
window.loadCiventralScript('assets/js/field-survey-history/modal.js');
window.loadCiventralScript('assets/js/field-survey-history/filters.js');
window.loadCiventralScript('assets/js/field-survey-history/events.js', () => {
    // Only execute if we are actually on the survey history page
    if (document.getElementById('historyTimeline') && typeof toggleHistorySubjectFields === 'function') {
        toggleHistorySubjectFields();
    }
});
