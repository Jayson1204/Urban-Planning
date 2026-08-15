document.addEventListener("DOMContentLoaded", () => {
    let recordsChart, occupancyChart, projectStatusChart;

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

    function renderDoughnut(canvasId, chartRef, rows, labelKey, colorMap) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;
        if (chartRef) chartRef.destroy();
        const colors = getThemeColors();

        if (!rows.length) {
            return new Chart(canvas.getContext('2d'), {
                type: 'doughnut',
                data: { labels: ['No data'], datasets: [{ data: [1], backgroundColor: ['#E2E8F0'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '70%' }
            });
        }

        return new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: rows.map(r => r[labelKey]),
                datasets: [{
                    data: rows.map(r => parseInt(r.total, 10)),
                    backgroundColor: rows.map(r => colorMap[r[labelKey]] || '#94A3B8'),
                    borderWidth: 3,
                    borderColor: colors.borderColor,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 9, weight: '700' }, boxWidth: 10, color: colors.legendColor, padding: 10 }
                    }
                },
                cutout: '70%'
            }
        });
    }

    function renderRecordsChart(summary) {
        const canvas = document.getElementById('dashRecordsChart');
        if (!canvas) return null;
        if (recordsChart) recordsChart.destroy();
        const colors = getThemeColors();

        const rows = [
            { label: 'Residents', total: summary.total_residents || 0, color: '#1E517B' },
            { label: 'Households', total: summary.total_households || 0, color: '#0D9488' },
            { label: 'Housing Units', total: summary.total_housing_units || 0, color: '#10B981' },
            { label: 'Urban Projects', total: summary.total_urban_projects || 0, color: '#7C3AED' },
            { label: 'Survey Assignments', total: summary.total_survey_assignments || 0, color: '#F59E0B' },
            { label: 'Survey Results', total: summary.total_survey_results || 0, color: '#EA580C' },
        ];

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: rows.map(r => r.label),
                datasets: [{
                    label: 'Active Records',
                    data: rows.map(r => r.total),
                    backgroundColor: rows.map(r => r.color),
                    borderRadius: 6,
                    maxBarThickness: 44,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
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
        const container = document.getElementById('dashActivityFeed');
        if (!container) return;
        if (!rows.length) {
            container.innerHTML = '<div class="px-2 py-6 text-center text-slate-400 text-xs">No recent activity yet.</div>';
            return;
        }
        container.innerHTML = rows.map(r => `
            <div class="py-3.5 flex items-center justify-between gap-4 text-xs transition hover:bg-slate-50/50 dark:hover:bg-slate-850/50 px-2 rounded-lg -mx-2">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-8.5 w-8.5 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0 border border-slate-100 dark:border-slate-700">
                        <i class="fa-solid ${activityIcon(r.type)} text-xs"></i>
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <p class="font-bold text-slate-700 dark:text-slate-200 truncate text-[11px]">${r.label || 'Untitled'}</p>
                        <p class="text-[9px] text-slate-450 dark:text-slate-400 font-medium">${r.type}</p>
                    </div>
                </div>
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-550 shrink-0">${(r.event_date || '').substring(0, 10)}</span>
            </div>
        `).join('');
    }

    function updateKpiBar(barId, labelId, value) {
        const bar = document.getElementById(barId);
        const label = document.getElementById(labelId);
        if (bar) bar.style.width = `${Math.min(100, Math.max(0, value))}%`;
        if (label) label.innerText = `${value}%`;
    }

    function handleSkeletonTransition() {
        const skeleton = document.getElementById('dashboardSkeleton');
        const realContent = document.getElementById('dashboardRealContent');
        if (!skeleton || !realContent) return;

        realContent.classList.remove('hidden');
        requestAnimationFrame(() => {
            skeleton.classList.add('opacity-0', 'pointer-events-none');
            realContent.classList.remove('opacity-0', 'translate-y-2');
            realContent.classList.add('opacity-100', 'translate-y-0');
            setTimeout(() => { skeleton.classList.add('hidden'); }, 500);
        });
    }

    async function loadDashboard() {
        const basePath = window.civentralBasePath || '../';
        try {
            const response = await fetch(basePath + 'api/employee/analytics.php');
            const result = await response.json();
            if (result.status !== 'success') return;

            const { summary, kpis, charts, recent_activity } = result.data;

            document.getElementById('dashTotalResidents').innerText = summary.total_residents || 0;
            document.getElementById('dashTotalHouseholds').innerText = summary.total_households || 0;
            document.getElementById('dashTotalHousingUnits').innerText = summary.total_housing_units || 0;
            document.getElementById('dashTotalUrbanProjects').innerText = summary.total_urban_projects || 0;
            document.getElementById('dashTotalSurveyResults').innerText = summary.total_survey_results || 0;
            document.getElementById('dashTotalSurveyAssignments').innerText = summary.total_survey_assignments || 0;
            document.getElementById('dashHousingOccupancyRate').innerText = kpis.housing_occupancy_rate;
            document.getElementById('dashProjectCompletionRate').innerText = kpis.project_completion_rate;

            updateKpiBar('dashKpiHousingBar', 'dashKpiHousingLabel', kpis.housing_occupancy_rate);
            updateKpiBar('dashKpiSurveyBar', 'dashKpiSurveyLabel', kpis.survey_completion_rate);
            updateKpiBar('dashKpiProjectBar', 'dashKpiProjectLabel', kpis.project_completion_rate);

            recordsChart = renderRecordsChart(summary);
            occupancyChart = renderDoughnut('dashHousingOccupancyChart', occupancyChart, charts.housing_occupancy, 'occupancy_status', OCCUPANCY_COLORS);
            projectStatusChart = renderDoughnut('dashProjectStatusChart', projectStatusChart, charts.urban_project_status, 'project_status', PROJECT_STATUS_COLORS);

            renderActivityFeed(recent_activity || []);
        } catch (err) {
            console.error('Error loading dashboard analytics:', err);
        } finally {
            setTimeout(handleSkeletonTransition, 400);
        }
    }

    loadDashboard();

    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            setTimeout(loadDashboard, 150);
        });
    }
});
