document.addEventListener("DOMContentLoaded", () => {
    let occupancyChart, projectStatusChart, conditionChart;

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
    const CONDITION_COLORS = {
        'Excellent': '#10B981',
        'Good': '#0D9488',
        'Fair': '#F59E0B',
        'Poor': '#EA580C',
        'Critical': '#DC2626',
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

    function renderBar(canvasId, chartRef, rows, labelKey, colorMap) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;
        if (chartRef) chartRef.destroy();
        const colors = getThemeColors();

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: rows.map(r => r[labelKey]),
                datasets: [{
                    label: 'Total',
                    data: rows.map(r => parseInt(r.total, 10)),
                    backgroundColor: rows.map(r => colorMap[r[labelKey]] || '#94A3B8'),
                    borderRadius: 6,
                    maxBarThickness: 36,
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
                        <p class="font-bold text-slate-700 truncate text-[11px]">${r.label || 'Untitled'}</p>
                        <p class="text-[9px] text-slate-450 font-medium">${r.type}</p>
                    </div>
                </div>
                <span class="text-[9px] font-black text-slate-400 shrink-0">${(r.event_date || '').substring(0, 10)}</span>
            </div>
        `).join('');
    }

    function updateKpiBar(barId, labelId, value) {
        const bar = document.getElementById(barId);
        const label = document.getElementById(labelId);
        if (bar) bar.style.width = `${Math.min(100, Math.max(0, value))}%`;
        if (label) label.innerText = `${value}%`;
    }

    async function loadAnalytics() {
        try {
            const response = await fetch('../../api/employee/analytics.php');
            const result = await response.json();
            if (result.status !== 'success') return;

            const { summary, kpis, charts, recent_activity } = result.data;

            document.getElementById('statTotalResidents').innerText = summary.total_residents || 0;
            document.getElementById('statTotalHouseholds').innerText = summary.total_households || 0;
            document.getElementById('statTotalHousingUnits').innerText = summary.total_housing_units || 0;
            document.getElementById('statTotalUrbanProjects').innerText = summary.total_urban_projects || 0;
            document.getElementById('statTotalSurveyResults').innerText = summary.total_survey_results || 0;
            document.getElementById('statTotalSurveyAssignments').innerText = summary.total_survey_assignments || 0;

            updateKpiBar('kpiHousingOccupancyBar', 'kpiHousingOccupancyLabel', kpis.housing_occupancy_rate);
            updateKpiBar('kpiSurveyCompletionBar', 'kpiSurveyCompletionLabel', kpis.survey_completion_rate);
            updateKpiBar('kpiProjectCompletionBar', 'kpiProjectCompletionLabel', kpis.project_completion_rate);

            occupancyChart = renderDoughnut('housingOccupancyChart', occupancyChart, charts.housing_occupancy, 'occupancy_status', OCCUPANCY_COLORS);
            projectStatusChart = renderDoughnut('urbanProjectStatusChart', projectStatusChart, charts.urban_project_status, 'project_status', PROJECT_STATUS_COLORS);
            conditionChart = renderBar('surveyConditionChart', conditionChart, charts.survey_condition, 'condition_rating', CONDITION_COLORS);

            renderActivityFeed(recent_activity || []);
        } catch (err) {
            console.error('Error loading analytics:', err);
        }
    }

    loadAnalytics();

    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            setTimeout(loadAnalytics, 150);
        });
    }
});
