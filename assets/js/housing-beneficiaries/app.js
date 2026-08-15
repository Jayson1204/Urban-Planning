// Housing Beneficiaries Module Bootstrap
window.loadCiventralScript('assets/js/housing-beneficiaries/api.js');
window.loadCiventralScript('assets/js/housing-beneficiaries/table.js');
window.loadCiventralScript('assets/js/housing-beneficiaries/modal.js');
window.loadCiventralScript('assets/js/housing-beneficiaries/documents.js');
window.loadCiventralScript('assets/js/housing-beneficiaries/filters.js');
window.loadCiventralScript('assets/js/housing-beneficiaries/events.js', () => {
    if (document.getElementById('beneficiariesTableBody') && typeof fetchBeneficiaries === 'function') {
        fetchBeneficiaries(1);
        fetchBeneficiaryStats();
    }
});
