
(function() {
    const basePath = (typeof window.civentralBasePath !== 'undefined') ? window.civentralBasePath : '../';

    // Cache-busting version for dynamically loaded scripts. Bump this whenever a
    // bridge-loaded JS file is changed so browsers fetch the new copy instead of a
    // stale cached one (XAMPP serves static .js with a long cache lifetime).
    const ASSET_VERSION = '2026-08-04-2';

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
    window.loadCiventralScript('assets/js/housing/app.js');
})();
