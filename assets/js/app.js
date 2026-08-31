
// Shared HTML-escaping helper. Module render functions build table/modal markup with
// innerHTML template literals, so any user-submitted free-text field (names, addresses,
// remarks, etc.) MUST be passed through this before interpolation, or a value like
// '<img src=x onerror=...>' stored in that field executes in the next viewer's browser.
function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

(function() {
    const basePath = (typeof window.civentralBasePath !== 'undefined') ? window.civentralBasePath : '../';

    // Cache-busting version for dynamically loaded scripts. Bump this whenever a
    // bridge-loaded JS file is changed so browsers fetch the new copy instead of a
    // stale cached one (XAMPP serves static .js with a long cache lifetime).
    const ASSET_VERSION = '2026-08-31-1';

    // Global loader
    window.loadCiventralScript = function(src, callback = null) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            const sep = src.indexOf('?') === -1 ? '?' : '&';
            script.src = basePath + src + sep + 'v=' + ASSET_VERSION;
            script.async = false;
            script.onload = () => {
                if (callback) callback();
                resolve();
            };
            script.onerror = (err) => {
                reject(err);
            };
            document.body.appendChild(script);
        });
    };

    // LOAD MODULE
    window.loadCiventralScript('assets/js/header/app.js');
    window.loadCiventralScript('assets/js/department/app.js');
    window.loadCiventralScript('assets/js/profile/app.js');
    window.loadCiventralScript('assets/js/resident/app.js');
    window.loadCiventralScript('assets/js/household/app.js');
    window.loadCiventralScript('assets/js/housing/app.js');
    window.loadCiventralScript('assets/js/housing-beneficiaries/app.js');
    window.loadCiventralScript('assets/js/housing-occupancy/app.js');
    window.loadCiventralScript('assets/js/housing-relocations/app.js');
    window.loadCiventralScript('assets/js/urban-planning/app.js');
    window.loadCiventralScript('assets/js/zoning-clearances/app.js');
    window.loadCiventralScript('assets/js/permit-applications/app.js');
    window.loadCiventralScript('assets/js/mapping/app.js');
    window.loadCiventralScript('assets/js/subdivisions/app.js');
    window.loadCiventralScript('assets/js/housing-projects/app.js');
    window.loadCiventralScript('assets/js/urban-projects/app.js');
    window.loadCiventralScript('assets/js/infrastructure-records/app.js');
    window.loadCiventralScript('assets/js/field-survey-forms/app.js');
    window.loadCiventralScript('assets/js/field-survey-assignments/app.js');
    window.loadCiventralScript('assets/js/field-survey-results/app.js');
    window.loadCiventralScript('assets/js/field-survey-history/app.js');
    window.loadCiventralScript('assets/js/ai-assistant/app.js');
})();
