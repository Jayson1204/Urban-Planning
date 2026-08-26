// Barangay Mapping Module Bootstrap
window.loadCiventralScript('assets/js/mapping/api.js');
window.loadCiventralScript('assets/js/mapping/map.js');
window.loadCiventralScript('assets/js/mapping/events.js', () => {
    // Leaflet loads from an external CDN <script> tag in mapping.php, whose fetch time
    // can race against these locally-served bridge scripts, so wait for the DOM and
    // Leaflet's global to both be ready instead of a one-shot check that can silently
    // skip initialization and leave the skeleton loader stuck.
    function tryInitBarangayMap() {
        if (typeof L === 'undefined') {
            setTimeout(tryInitBarangayMap, 50);
            return;
        }
        if (document.getElementById('barangayMap') && typeof initBarangayMappingModule === 'function') {
            initBarangayMappingModule();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInitBarangayMap);
    } else {
        tryInitBarangayMap();
    }
});
