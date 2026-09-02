document.addEventListener('DOMContentLoaded', () => {
    const badgeEls = document.querySelectorAll('[data-overall-count]');
    if (!badgeEls.length) return;

    const basePath = window.civentralBasePath || '../../';
    const API_URL = basePath + 'api/employee/reports.php';

    const PRIMARY_METRIC = {
        resident: 'total', housing: 'total', application: 'total',
        project: 'total', survey: 'total', user: 'total', activity: 'total',
    };
    const PRIMARY_LABEL = {
        resident: 'residents', housing: 'housing units', application: 'applications',
        project: 'projects', survey: 'survey results', user: 'users', activity: 'log entries',
    };

    function setBadge(type, text) {
        document.querySelectorAll(`[data-overall-count="${type}"]`).forEach((el) => {
            el.innerText = text;
        });
    }

    async function loadSummary() {
        const metaBar = document.getElementById('overallMetaBar');

        try {
            const response = await fetch(`${API_URL}?type=overall`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message || 'Failed to load summary');

            const { summary, generated_at } = result.data;

            Object.keys(PRIMARY_METRIC).forEach((type) => {
                const stats = summary[type];
                if (!stats) {
                    setBadge(type, type === 'user' ? 'Unavailable -- could not reach user directory' : 'No data available');
                    return;
                }
                const value = stats[PRIMARY_METRIC[type]] || 0;
                setBadge(type, `${value} total ${PRIMARY_LABEL[type]}`);
            });

            if (metaBar) {
                const generated = new Date(generated_at).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
                metaBar.innerHTML = `<i class="fa-solid fa-clock text-[10px] mr-1 opacity-60"></i>Generated ${escapeHtml(generated)} &middot; Consolidated totals across all 7 report categories`;
            }
        } catch (err) {
            console.error('Error loading overall reports summary:', err);
            Object.keys(PRIMARY_METRIC).forEach((type) => setBadge(type, 'Could not load totals'));
            if (metaBar) metaBar.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-[10px] mr-1 text-rose-500"></i>Could not load summary totals. Please refresh the page.';
        }
    }

    const exportBtn = document.getElementById('overallExportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            window.location.href = `${API_URL}?type=overall&export=csv`;
        });
    }

    loadSummary();
});
