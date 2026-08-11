// Urban Projects Module Bootstrap
window.loadCiventralScript('assets/js/urban-projects/api.js');
window.loadCiventralScript('assets/js/urban-projects/table.js');
window.loadCiventralScript('assets/js/urban-projects/modal.js');
window.loadCiventralScript('assets/js/urban-projects/filters.js');
window.loadCiventralScript('assets/js/urban-projects/events.js', () => {
    if (document.getElementById('projectsTableBody') && typeof fetchProjects === 'function') {
        fetchProjects(1);
        fetchProjectStats();
    }
});
