// Field Survey Assignments Module Bootstrap
window.loadCiventralScript('assets/js/field-survey-assignments/api.js');
window.loadCiventralScript('assets/js/field-survey-assignments/table.js');
window.loadCiventralScript('assets/js/field-survey-assignments/modal.js');
window.loadCiventralScript('assets/js/field-survey-assignments/filters.js');
window.loadCiventralScript('assets/js/field-survey-assignments/events.js', () => {
    // Only execute if we are actually on the survey assignments page
    if (document.getElementById('assignmentsTableBody') && typeof fetchAssignments === 'function') {
        fetchAssignments(1);
        fetchAssignmentStats();
        loadFormOptions();
    }
});
