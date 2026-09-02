document.addEventListener("DOMContentLoaded", () => {
    const basePath = window.civentralBasePath || '../../';
    const API_URL = basePath + 'api/employee/analytics.php';

    const charts = {};
    let locationRows = [];
    let locationSort = { key: 'total', dir: 'desc' };
    let locationPage = 1;
    const LOCATION_PAGE_SIZE = 10;

    let currentRange = 'all';
    let customFrom = '';
    let customTo = '';

    function getThemeColors() {
        const isDark = document.documentElement.classList.contains('dark');
        return {
            gridColor: isDark ? '#1e293b' : '#F1F5F9',
            ticksColor: isDark ? '#94A3B8' : '#64748B',
            legendColor: isDark ? '#cbd5e1' : '#475569',
            borderColor: isDark ? '#0f172a' : '#ffffff',
        };
    }

    const OCCUPANCY_COLORS = {
        'Vacant': '#10B981',
        'Occupied': '#0D9488',
        'Reserved': '#7C3AED',
        'Under Maintenance': '#F59E0B',
    };
    const PROJECT_STATUS_COLORS = {
        'Planned': '#64748B',
        'Ongoing': '#1E517B',
        'Completed': '#10B981',
        'Delayed': '#F59E0B',
        'Cancelled': '#DC2626',
    };
    const BENEFICIARY_STATUS_COLORS = {
        'Applicant': '#64748B',
        'Qualified': '#1E517B',
        'Awarded': '#10B981',
        'Disqualified': '#DC2626',
        'Cancelled': '#94A3B8',
    };
    const PALETTE = ['#1E517B', '#0D9488', '#7C3AED', '#D97706', '#DC2626', '#0EA5E9', '#65A30D', '#DB2777'];

    function colorFor(map, key, index) {
        return map[key] || PALETTE[index % PALETTE.length];
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function formatMonthLabel(ym) {
        if (!ym || !ym.includes('-')) return ym || '';
        const [y, m] = ym.split('-');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const idx = parseInt(m, 10) - 1;
        return `${months[idx] || m} ${y}`;
    }

    function destroyChart(id) {
        if (charts[id]) {
            charts[id].destroy();
            delete charts[id];
        }
    }

    function emptyChart(canvas, type) {
        return new Chart(canvas.getContext('2d'), {
            type: type === 'doughnut' ? 'doughnut' : 'bar',
            data: { labels: ['No data'], datasets: [{ data: [1], backgroundColor: ['#E2E8F0'], borderWidth: 0 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: type === 'doughnut' ? {} : { x: { display: false }, y: { display: false } },
                cutout: type === 'doughnut' ? '70%' : undefined,
            }
        });
    }

    function renderDoughnut(canvasId, rows, labelKey, colorMap) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        destroyChart(canvasId);
        const colors = getThemeColors();

        if (!rows || !rows.length) {
            charts[canvasId] = emptyChart(canvas, 'doughnut');
            return;
        }

        charts[canvasId] = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: rows.map(r => r[labelKey]),
                datasets: [{
                    data: rows.map(r => parseInt(r.total, 10) || 0),
                    backgroundColor: rows.map((r, i) => colorFor(colorMap, r[labelKey], i)),
                    borderWidth: 3,
                    borderColor: colors.borderColor,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { font: { size: 9, weight: '700' }, boxWidth: 10, color: colors.legendColor, padding: 10 } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed}` } },
                },
                cutout: '70%'
            }
        });
    }

    function renderBar(canvasId, rows, labelKey, colorMap, horizontal) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        destroyChart(canvasId);
        const colors = getThemeColors();

        if (!rows || !rows.length) {
            charts[canvasId] = emptyChart(canvas, 'bar');
            return;
        }

        // colorMap may be a single hex string (one solid color for a ranking/volume
        // bar chart, reads cleaner than a rainbow) or a {label: color} map.
        const isSingleColor = typeof colorMap === 'string';

        charts[canvasId] = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: rows.map(r => r[labelKey]),
                datasets: [{
                    label: 'Total',
                    data: rows.map(r => parseInt(r.total, 10) || 0),
                    backgroundColor: isSingleColor ? colorMap : rows.map((r, i) => colorFor(colorMap || {}, r[labelKey], i)),
                    borderRadius: 6,
                    maxBarThickness: 36,
                }]
            },
            options: {
                indexAxis: horizontal ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => `Total: ${ctx.parsed[horizontal ? 'x' : 'y']}` } } },
                scales: {
                    x: { grid: { display: !horizontal }, ticks: { font: { size: 9, weight: '600' }, color: colors.ticksColor, precision: horizontal ? 0 : undefined } },
                    y: { beginAtZero: true, grid: { color: colors.gridColor }, ticks: { font: { size: 9, weight: '600' }, precision: 0, color: colors.ticksColor } }
                }
            }
        });
    }

    function renderLine(canvasId, rows, labelKey, series) {
        // series: [{ key, label, color }] for a single or multi-series line chart
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        destroyChart(canvasId);
        const colors = getThemeColors();

        if (!rows || !rows.length) {
            charts[canvasId] = emptyChart(canvas, 'line');
            return;
        }

        charts[canvasId] = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: rows.map(r => formatMonthLabel(r[labelKey])),
                datasets: series.map(s => ({
                    label: s.label,
                    data: rows.map(r => parseInt(r[s.key], 10) || 0),
                    borderColor: s.color,
                    backgroundColor: s.color + '22',
                    tension: 0.35,
                    fill: series.length === 1,
                    pointRadius: 3,
                    pointBackgroundColor: s.color,
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: series.length > 1, position: 'top', labels: { font: { size: 9, weight: '700' }, boxWidth: 10, color: colors.legendColor } },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9, weight: '600' }, color: colors.ticksColor } },
                    y: { beginAtZero: true, grid: { color: colors.gridColor }, ticks: { font: { size: 9, weight: '600' }, precision: 0, color: colors.ticksColor } }
                }
            }
        });
    }

    function activityIcon(type) {
        const map = {
            'Resident': 'fa-user',
            'Housing Unit': 'fa-house-chimney',
            'Urban Project': 'fa-map-location-dot',
            'Survey Assignment': 'fa-clipboard-list',
        };
        return map[type] || 'fa-circle-info';
    }

    function renderActivityFeed(rows) {
        const container = document.getElementById('analyticsActivityFeed');
        if (!container) return;
        if (!rows.length) {
            container.innerHTML = '<div class="px-2 py-6 text-center text-slate-400 text-xs">No recent activity yet.</div>';
            return;
        }
        container.innerHTML = rows.map(r => `
            <div class="py-3 flex items-center justify-between gap-4 text-xs px-2">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-8.5 w-8.5 rounded-lg bg-slate-50 text-slate-600 flex items-center justify-center shrink-0 border border-slate-100">
                        <i class="fa-solid ${activityIcon(r.type)} text-xs"></i>
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <p class="font-bold text-slate-700 truncate text-[11px]">${escapeHtml(r.label || 'Untitled')}</p>
                        <p class="text-[9px] text-slate-450 font-medium">${escapeHtml(r.type)}</p>
                    </div>
                </div>
                <span class="text-[9px] font-black text-slate-400 shrink-0">${(r.event_date || '').substring(0, 10)}</span>
            </div>
        `).join('');
    }

    function renderSummaryCards(summary, users) {
        const grid = document.getElementById('summaryCardsGrid');
        if (!grid) return;

        const cards = [
            { icon: 'fa-house-chimney', label: 'Total Housing Units', value: summary.total_housing_units, glow: 'teal' },
            { icon: 'fa-file-lines', label: 'Total Applications', value: summary.total_applications, glow: 'navy' },
            { icon: 'fa-hourglass-half', label: 'Pending Applications', value: summary.pending_applications, glow: 'amber' },
            { icon: 'fa-check', label: 'Approved Applications', value: summary.approved_applications, glow: 'teal' },
            { icon: 'fa-xmark', label: 'Rejected Applications', value: summary.rejected_applications, glow: 'purple' },
            { icon: 'fa-map-location-dot', label: 'Total Urban Projects', value: summary.total_urban_projects, glow: 'purple' },
            { icon: 'fa-diagram-project', label: 'Ongoing Projects', value: summary.ongoing_projects, glow: 'navy' },
            { icon: 'fa-circle-check', label: 'Completed Projects', value: summary.completed_projects, glow: 'teal' },
            { icon: 'fa-users-gear', label: 'Total Registered Users', value: users && users.status === 'ok' ? users.total_users : 'N/A', glow: 'amber' },
            { icon: 'fa-people-roof', label: 'Total Residents', value: summary.total_residents, glow: 'navy' },
        ];

        grid.innerHTML = cards.map(c => `
            <div class="glass-panel glow-card-${c.glow} rounded-2xl p-5 flex items-center justify-between">
                <div class="space-y-1 min-w-0">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-455 block truncate">${c.label}</span>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight">${c.value}</h3>
                </div>
                <div class="h-11 w-11 rounded-xl bg-brand-light text-brand-dark border border-brand-border/40 flex items-center justify-center shadow-xs shrink-0">
                    <i class="fa-solid ${c.icon} text-sm"></i>
                </div>
            </div>
        `).join('');
    }

    function renderTopBarangays(rows) {
        const container = document.getElementById('locationTopBarangays');
        if (!container) return;
        const top5 = rows.slice(0, 5);
        if (!top5.length) {
            container.innerHTML = '<div class="sm:col-span-5 px-2 py-6 text-center text-slate-400 text-xs bg-white border border-slate-200/80 rounded-2xl">No location data available yet.</div>';
            return;
        }
        container.innerHTML = top5.map((r, i) => `
            <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">#${i + 1} Barangay</span>
                <h3 class="text-sm font-black text-slate-900 truncate mt-0.5">${escapeHtml(r.barangay)}</h3>
                <p class="text-[10px] text-slate-500 font-bold mt-1">${r.total} records &middot; ${r.percent}%</p>
            </div>
        `).join('');
    }

    function sortLocationRows() {
        const { key, dir } = locationSort;
        const sorted = [...locationRows].sort((a, b) => {
            let av = a[key], bv = b[key];
            if (key === 'barangay') {
                av = (av || '').toLowerCase();
                bv = (bv || '').toLowerCase();
                return dir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
            }
            return dir === 'asc' ? av - bv : bv - av;
        });
        return sorted;
    }

    function renderLocationTable() {
        const tbody = document.getElementById('locationTableBody');
        const paginationText = document.getElementById('locationPaginationText');
        const paginationControls = document.getElementById('locationPaginationControls');
        if (!tbody) return;

        if (!locationRows.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-semibold">
                        <i class="fa-solid fa-location-dot text-3xl mb-3 block opacity-60"></i>
                        No location data matched the selected time range.
                    </td>
                </tr>`;
            if (paginationText) paginationText.innerText = 'Showing 0 to 0 of 0 barangays';
            if (paginationControls) paginationControls.innerHTML = '';
            return;
        }

        const sorted = sortLocationRows();
        const total = sorted.length;
        const totalPages = Math.max(1, Math.ceil(total / LOCATION_PAGE_SIZE));
        locationPage = Math.min(locationPage, totalPages);
        const start = (locationPage - 1) * LOCATION_PAGE_SIZE;
        const pageRows = sorted.slice(start, start + LOCATION_PAGE_SIZE);

        tbody.innerHTML = pageRows.map(r => `
            <tr class="hover:bg-slate-50/50 transition">
                <td class="px-6 py-3.5 font-bold text-slate-800">${escapeHtml(r.barangay)}</td>
                <td class="px-6 py-3.5">${r.residents}</td>
                <td class="px-6 py-3.5">${r.housing_units}</td>
                <td class="px-6 py-3.5">${r.applications}</td>
                <td class="px-6 py-3.5">${r.projects}</td>
                <td class="px-6 py-3.5 font-black text-slate-700">${r.total}</td>
                <td class="px-6 py-3.5">${r.percent}%</td>
            </tr>
        `).join('');

        const from = total === 0 ? 0 : start + 1;
        const to = Math.min(start + LOCATION_PAGE_SIZE, total);
        if (paginationText) paginationText.innerText = `Showing ${from} to ${to} of ${total} barangays`;

        if (paginationControls) {
            const prevDisabled = locationPage <= 1;
            const nextDisabled = locationPage >= totalPages;
            paginationControls.innerHTML = `
                <button data-page="${locationPage - 1}" ${prevDisabled ? 'disabled' : ''} class="location-page-btn px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
                    <i class="fa-solid fa-chevron-left text-[9px]"></i>
                </button>
                <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${locationPage}</button>
                <span class="text-slate-400 px-1">of ${totalPages}</span>
                <button data-page="${locationPage + 1}" ${nextDisabled ? 'disabled' : ''} class="location-page-btn px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                </button>`;
            paginationControls.querySelectorAll('.location-page-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    locationPage = parseInt(btn.dataset.page, 10);
                    renderLocationTable();
                });
            });
        }

        document.querySelectorAll('.location-sort-th').forEach(th => {
            const icon = th.querySelector('i');
            if (!icon) return;
            if (th.dataset.key === locationSort.key) {
                icon.className = `fa-solid fa-sort-${locationSort.dir === 'asc' ? 'up' : 'down'} text-[8px] text-brand-dark`;
            } else {
                icon.className = 'fa-solid fa-sort text-[8px] opacity-50';
            }
        });
    }

    document.querySelectorAll('.location-sort-th').forEach(th => {
        th.addEventListener('click', () => {
            const key = th.dataset.key;
            if (locationSort.key === key) {
                locationSort.dir = locationSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                locationSort = { key, dir: key === 'barangay' ? 'asc' : 'desc' };
            }
            locationPage = 1;
            renderLocationTable();
        });
    });

    function renderUserAnalytics(users) {
        const unavailable = document.getElementById('userAnalyticsUnavailable');
        const content = document.getElementById('userAnalyticsContent');
        if (!users || users.status !== 'ok') {
            if (unavailable) unavailable.classList.remove('hidden');
            if (content) content.classList.add('hidden');
            return;
        }
        if (unavailable) unavailable.classList.add('hidden');
        if (content) content.classList.remove('hidden');

        document.getElementById('userStatTotal').innerText = users.total_users || 0;
        document.getElementById('userStatStaffAdmin').innerText = users.staff_admin_count || 0;
        document.getElementById('userStatOther').innerText = users.other_count || 0;

        renderBar('usersByRoleChart', users.by_role, 'role', '#1E517B', true);
        renderLine('userRegistrationTrendChart', users.registration_trend, 'month', [{ key: 'total', label: 'New Users', color: '#1E517B' }]);
    }

    function buildQuery(extra) {
        const params = new URLSearchParams(extra || {});
        if (currentRange !== 'all') {
            const range = computeRange();
            if (range.from) params.set('date_from', range.from);
            if (range.to) params.set('date_to', range.to);
        }
        return params.toString();
    }

    function pad(n) { return n < 10 ? `0${n}` : `${n}`; }
    function toIso(d) { return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; }

    function computeRange() {
        const now = new Date();
        if (currentRange === 'today') {
            const s = toIso(now);
            return { from: s, to: s };
        }
        if (currentRange === 'week') {
            const day = now.getDay() === 0 ? 7 : now.getDay();
            const monday = new Date(now);
            monday.setDate(now.getDate() - day + 1);
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);
            return { from: toIso(monday), to: toIso(sunday) };
        }
        if (currentRange === 'month') {
            const first = new Date(now.getFullYear(), now.getMonth(), 1);
            const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            return { from: toIso(first), to: toIso(last) };
        }
        if (currentRange === 'year') {
            return { from: `${now.getFullYear()}-01-01`, to: `${now.getFullYear()}-12-31` };
        }
        if (currentRange === 'custom') {
            return { from: customFrom, to: customTo };
        }
        return { from: '', to: '' };
    }

    function updateRangeLabel() {
        const label = document.getElementById('analyticsRangeLabel');
        if (!label) return;
        if (currentRange === 'all') {
            label.innerText = 'Showing all-time data';
            return;
        }
        const range = computeRange();
        if (range.from && range.to) {
            label.innerText = `Showing ${range.from} to ${range.to}`;
        } else {
            label.innerText = 'Select a custom date range';
        }
    }

    function setActiveRangeButton() {
        document.querySelectorAll('.analytics-range-btn').forEach(btn => {
            const active = btn.dataset.range === currentRange;
            btn.classList.toggle('bg-brand-dark', active);
            btn.classList.toggle('text-white', active);
            btn.classList.toggle('border-brand-dark', active);
            btn.classList.toggle('bg-white', !active);
            btn.classList.toggle('text-slate-600', !active);
            btn.classList.toggle('border-slate-200', !active);
        });
    }

    async function loadAnalytics() {
        const errorBanner = document.getElementById('analyticsErrorBanner');
        if (errorBanner) errorBanner.classList.add('hidden');

        try {
            const qs = buildQuery();
            const response = await fetch(`${API_URL}${qs ? '?' + qs : ''}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message || 'Failed to load analytics');

            const { summary, housing, projects, users, residents, location, recent_activity } = result.data;

            renderSummaryCards(summary, users);
            renderActivityFeed(recent_activity || []);

            // Housing Analytics
            renderDoughnut('housingByStatusChart', housing.by_status, 'beneficiary_status', BENEFICIARY_STATUS_COLORS);
            renderDoughnut('housingOccupancyChart', housing.occupancy, 'occupancy_status', OCCUPANCY_COLORS);
            renderBar('housingByBarangayChart', housing.by_barangay, 'barangay', '#0D9488', true);
            renderLine('housingApplicationsOverTimeChart', housing.applications_over_time, 'month', [{ key: 'total', label: 'Applications', color: '#0D9488' }]);

            // Application Analytics
            document.getElementById('appStatTotal').innerText = summary.total_applications || 0;
            document.getElementById('appStatPending').innerText = summary.pending_applications || 0;
            document.getElementById('appStatApproved').innerText = summary.approved_applications || 0;
            document.getElementById('appStatRejected').innerText = summary.rejected_applications || 0;
            renderBar('applicationsByMonthChart', (housing.applications_over_time || []).map(r => ({ ...r, month: formatMonthLabel(r.month) })), 'month', '#1E517B');
            renderLine('approvalTrendChart', housing.applications_status_over_time, 'month', [
                { key: 'pending', label: 'Pending', color: '#D97706' },
                { key: 'approved', label: 'Approved', color: '#10B981' },
                { key: 'rejected', label: 'Rejected', color: '#DC2626' },
            ]);
            renderDoughnut('applicationsByCategoryChart', housing.by_category, 'category', {});

            // Urban Planning Analytics
            document.getElementById('projStatTotal').innerText = summary.total_urban_projects || 0;
            document.getElementById('projStatPlanned').innerText = summary.planned_projects || 0;
            document.getElementById('projStatOngoing').innerText = summary.ongoing_projects || 0;
            document.getElementById('projStatCompleted').innerText = summary.completed_projects || 0;
            document.getElementById('projStatDelayed').innerText = summary.delayed_projects || 0;
            document.getElementById('projStatCancelled').innerText = summary.cancelled_projects || 0;
            renderDoughnut('projectsByStatusChart', projects.by_status, 'project_status', PROJECT_STATUS_COLORS);
            renderLine('projectsOverTimeChart', projects.over_time, 'month', [{ key: 'total', label: 'Projects', color: '#7C3AED' }]);
            renderBar('projectsByBarangayChart', projects.by_barangay, 'barangay', '#7C3AED', true);
            renderDoughnut('projectsByTypeChart', projects.by_type, 'project_type', {});

            // User Analytics
            document.getElementById('userStatResidents').innerText = summary.total_residents || 0;
            renderUserAnalytics(users);
            renderLine('residentRegistrationTrendChart', residents.over_time, 'month', [{ key: 'total', label: 'New Residents', color: '#10B981' }]);

            // Location Analytics
            locationRows = location || [];
            locationPage = 1;
            // Backend already returns locationRows sorted by total desc; top-5 uses that
            // fixed ranking regardless of whatever column the table itself is sorted by.
            renderTopBarangays(locationRows);
            renderLocationTable();

        } catch (err) {
            console.error('Error loading analytics:', err);
            if (errorBanner) errorBanner.classList.remove('hidden');
        }
    }

    document.querySelectorAll('.analytics-range-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentRange = btn.dataset.range;
            const customBox = document.getElementById('analyticsCustomRange');
            if (customBox) customBox.classList.toggle('hidden', currentRange !== 'custom');
            setActiveRangeButton();
            updateRangeLabel();
            if (currentRange !== 'custom') {
                loadAnalytics();
            }
        });
    });

    const applyCustomBtn = document.getElementById('analyticsApplyCustom');
    if (applyCustomBtn) {
        applyCustomBtn.addEventListener('click', () => {
            customFrom = document.getElementById('analyticsDateFrom').value;
            customTo = document.getElementById('analyticsDateTo').value;
            if (!customFrom || !customTo) return;
            updateRangeLabel();
            loadAnalytics();
        });
    }

    const retryBtn = document.getElementById('analyticsRetryBtn');
    if (retryBtn) retryBtn.addEventListener('click', loadAnalytics);

    const exportBtn = document.getElementById('analyticsExportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const qs = buildQuery({ export: 'csv' });
            window.location.href = `${API_URL}?${qs}`;
        });
    }

    const printBtn = document.getElementById('analyticsPrintBtn');
    if (printBtn) printBtn.addEventListener('click', () => window.print());

    setActiveRangeButton();
    updateRangeLabel();
    loadAnalytics();

    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            setTimeout(loadAnalytics, 150);
        });
    }
});
