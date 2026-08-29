document.addEventListener('DOMContentLoaded', () => {
    const TYPE = window.CIVENTRAL_REPORT_TYPE;
    if (!TYPE) return;

    const basePath = window.civentralBasePath || '../../';
    const API_URL = basePath + 'api/employee/reports.php';

    const CONFIGS = {
        resident: {
            filterFields: { search: 'reportSearch', barangay: 'reportBarangay', status: 'reportStatus', date_from: 'reportDateFrom', date_to: 'reportDateTo' },
            statMap: [['reportStatTotal', 'total'], ['reportStatActive', 'active'], ['reportStatArchived', 'archived'], ['reportStatExtra', 'households_covered']],
            emptyIcon: 'fa-people-roof',
            emptyText: 'No residents matched your filters.',
            renderRow: (r) => {
                const name = escapeHtml([r.first_name, r.middle_name, r.last_name, r.suffix].filter(Boolean).join(' '));
                const isActive = r.status === 'Active';
                const statusClass = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200';
                return `
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-3.5 font-bold text-slate-800">${name}</td>
                        <td class="px-6 py-3.5">${escapeHtml(r.household_barangay || r.barangay) || '&mdash;'}${r.household_number ? ` <span class="text-slate-400 font-mono text-[10px]">(HH ${escapeHtml(r.household_number)})</span>` : ''}</td>
                        <td class="px-6 py-3.5">${escapeHtml(r.contact_number) || '&mdash;'}</td>
                        <td class="px-6 py-3.5"><span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${statusClass}">${r.status}</span></td>
                        <td class="px-6 py-3.5 text-slate-500">${(r.created_at || '').substring(0, 10)}</td>
                    </tr>`;
            },
        },
        housing: {
            filterFields: { search: 'reportSearch', barangay: 'reportBarangay', occupancy_status: 'reportOccupancyStatus', status: 'reportStatus', date_from: 'reportDateFrom', date_to: 'reportDateTo' },
            statMap: [['reportStatTotal', 'total'], ['reportStatActive', 'vacant'], ['reportStatExtra', 'occupied'], ['reportStatArchived', 'archived']],
            emptyIcon: 'fa-house-chimney',
            emptyText: 'No housing units matched your filters.',
            renderRow: (r) => {
                const isActive = r.status === 'Active';
                const statusClass = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200';
                return `
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-3.5 font-bold text-slate-800">${escapeHtml(r.unit_code)}<div class="text-[10px] text-slate-400 font-medium">${escapeHtml(r.project_name || '')}</div></td>
                        <td class="px-6 py-3.5">${escapeHtml(r.barangay) || '&mdash;'}</td>
                        <td class="px-6 py-3.5">${r.occupancy_status}</td>
                        <td class="px-6 py-3.5"><span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${statusClass}">${r.status}</span></td>
                        <td class="px-6 py-3.5 text-slate-500">${(r.created_at || '').substring(0, 10)}</td>
                    </tr>`;
            },
        },
        project: {
            filterFields: { search: 'reportSearch', barangay: 'reportBarangay', project_type: 'reportProjectType', project_status: 'reportProjectStatus', status: 'reportStatus', date_from: 'reportDateFrom', date_to: 'reportDateTo' },
            statMap: [['reportStatTotal', 'total'], ['reportStatActive', 'planned'], ['reportStatExtra', 'ongoing'], ['reportStatCompleted', 'completed']],
            emptyIcon: 'fa-diagram-project',
            emptyText: 'No urban projects matched your filters.',
            renderRow: (r) => {
                const budget = r.budget ? Number(r.budget).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' }) : '&mdash;';
                return `
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-3.5 font-bold text-slate-800">${escapeHtml(r.project_code)}<div class="text-[10px] text-slate-400 font-medium">${escapeHtml(r.project_title)}</div></td>
                        <td class="px-6 py-3.5">${escapeHtml(r.barangay) || '&mdash;'}<div class="text-[10px] text-slate-400">${escapeHtml(r.project_type || '')}</div></td>
                        <td class="px-6 py-3.5">${r.project_status}</td>
                        <td class="px-6 py-3.5">${budget}</td>
                        <td class="px-6 py-3.5 text-slate-500">${(r.created_at || '').substring(0, 10)}</td>
                    </tr>`;
            },
        },
        survey: {
            filterFields: { search: 'reportSearch', condition_rating: 'reportConditionRating', status: 'reportStatus', date_from: 'reportDateFrom', date_to: 'reportDateTo' },
            statMap: [['reportStatTotal', 'total'], ['reportStatActive', 'active'], ['reportStatArchived', 'archived']],
            emptyIcon: 'fa-clipboard-list',
            emptyText: 'No survey results matched your filters.',
            renderRow: (r) => {
                const isActive = r.status === 'Active';
                const statusClass = isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-150' : 'bg-slate-50 text-slate-500 border-slate-200';
                return `
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-3.5 font-bold text-slate-800">${escapeHtml(r.form_code || '')}<div class="text-[10px] text-slate-400 font-medium">${escapeHtml(r.form_title || '')}</div></td>
                        <td class="px-6 py-3.5">${escapeHtml(r.subject_name) || '&mdash;'}</td>
                        <td class="px-6 py-3.5">${r.condition_rating || '&mdash;'}</td>
                        <td class="px-6 py-3.5"><span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${statusClass}">${r.status}</span></td>
                        <td class="px-6 py-3.5 text-slate-500">${r.survey_date || '&mdash;'}</td>
                    </tr>`;
            },
        },
    };

    const config = CONFIGS[TYPE];
    if (!config) return;

    let currentPage = 1;
    let searchDebounce = null;

    function collectFilters() {
        const filters = {};
        Object.entries(config.filterFields).forEach(([param, elId]) => {
            const el = document.getElementById(elId);
            if (el && el.value) filters[param] = el.value;
        });
        return filters;
    }

    function buildQuery(extra = {}) {
        const params = new URLSearchParams({ type: TYPE, ...collectFilters(), ...extra });
        return params.toString();
    }

    function renderStats(stats) {
        config.statMap.forEach(([elId, key]) => {
            const el = document.getElementById(elId);
            if (el) el.innerText = stats[key] || 0;
        });
    }

    function renderTable(listing) {
        const tbody = document.getElementById('reportTableBody');
        const paginationText = document.getElementById('reportPaginationText');
        if (!tbody) return;

        if (!listing.data.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-semibold">
                        <i class="fa-solid ${config.emptyIcon} text-3xl mb-3 block opacity-60"></i>
                        ${config.emptyText}
                    </td>
                </tr>`;
            if (paginationText) paginationText.innerText = 'Showing 0 to 0 of 0 records';
            const paginationControls = document.getElementById('reportPaginationControls');
            if (paginationControls) paginationControls.innerHTML = '';
            return;
        }

        tbody.innerHTML = listing.data.map(config.renderRow).join('');

        const { page, per_page, total, total_pages } = listing;
        const from = total === 0 ? 0 : (page - 1) * per_page + 1;
        const to = Math.min(page * per_page, total);
        if (paginationText) paginationText.innerText = `Showing ${from} to ${to} of ${total} records`;

        const container = document.getElementById('reportPaginationControls');
        if (container) {
            const prevDisabled = page <= 1;
            const nextDisabled = page >= total_pages;
            container.innerHTML = `
                <button data-page="${page - 1}" ${prevDisabled ? 'disabled' : ''} class="report-page-btn px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
                    <i class="fa-solid fa-chevron-left text-[9px]"></i>
                </button>
                <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
                <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
                <button data-page="${page + 1}" ${nextDisabled ? 'disabled' : ''} class="report-page-btn px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
                    <i class="fa-solid fa-chevron-right text-[9px]"></i>
                </button>`;
            container.querySelectorAll('.report-page-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    currentPage = parseInt(btn.dataset.page, 10);
                    loadReport();
                });
            });
        }
    }

    async function loadReport() {
        try {
            const response = await fetch(`${API_URL}?${buildQuery({ page: currentPage, per_page: 10 })}`);
            const result = await response.json();
            if (result.status !== 'success') return;
            renderStats(result.data.stats);
            renderTable(result.data.listing);
        } catch (err) {
            console.error('Error loading report:', err);
        }
    }

    Object.values(config.filterFields).forEach((elId) => {
        const el = document.getElementById(elId);
        if (!el) return;
        const eventName = el.tagName === 'SELECT' || el.type === 'date' ? 'change' : 'input';
        el.addEventListener(eventName, () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                currentPage = 1;
                loadReport();
            }, 300);
        });
    });

    const exportBtn = document.getElementById('reportExportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            window.location.href = `${API_URL}?${buildQuery({ export: 'csv' })}`;
        });
    }

    loadReport();
});
