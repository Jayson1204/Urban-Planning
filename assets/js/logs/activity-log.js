document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('activityTableBody');
    if (!tableBody) return;

    const basePath = window.civentralBasePath || '../../';
    const API_URL = basePath + 'api/employee/activity-logs.php';

    const ACTION_BADGES = {
        Create: 'bg-emerald-50 text-emerald-700 border-emerald-150',
        Update: 'bg-cyan-50 text-cyan-700 border-cyan-150',
        Archive: 'bg-amber-50 text-amber-700 border-amber-150',
        Restore: 'bg-cyan-50 text-cyan-700 border-cyan-150',
        Delete: 'bg-rose-50 text-rose-700 border-rose-150',
        Approve: 'bg-emerald-50 text-emerald-700 border-emerald-150',
        Reject: 'bg-rose-50 text-rose-700 border-rose-150',
    };

    let currentPage = 1;
    let searchDebounce = null;

    function collectFilters() {
        const filters = {};
        const search = document.getElementById('activitySearch').value.trim();
        const module = document.getElementById('activityModule').value;
        const action = document.getElementById('activityAction').value;
        const dateFrom = document.getElementById('activityDateFrom').value;
        const dateTo = document.getElementById('activityDateTo').value;
        if (search) filters.search = search;
        if (module) filters.module = module;
        if (action) filters.action_filter = action;
        if (dateFrom) filters.date_from = dateFrom;
        if (dateTo) filters.date_to = dateTo;
        return filters;
    }

    function buildQuery(extra = {}) {
        const params = new URLSearchParams({ ...collectFilters(), ...extra });
        return params.toString();
    }

    function renderStats(stats) {
        document.getElementById('activityStatTotal').innerText = stats.total || 0;
        document.getElementById('activityStatToday').innerText = stats.today || 0;
        document.getElementById('activityStatCreates').innerText = stats.creates || 0;
        document.getElementById('activityStatArchives').innerText = stats.archives || 0;
    }

    function formatDate(value) {
        if (!value) return '&mdash;';
        const d = new Date(value.replace(' ', 'T'));
        if (isNaN(d.getTime())) return value;
        return d.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
    }

    function renderRow(row) {
        const badgeClass = ACTION_BADGES[row.action] || 'bg-slate-50 text-slate-500 border-slate-200';
        return `
            <tr class="hover:bg-slate-50/50 transition">
                <td class="px-6 py-3.5 text-slate-500">${formatDate(row.created_at)}</td>
                <td class="px-6 py-3.5 font-bold text-slate-800">${row.actor_name}</td>
                <td class="px-6 py-3.5">${row.module}</td>
                <td class="px-6 py-3.5"><span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full border ${badgeClass}">${row.action}</span></td>
                <td class="px-6 py-3.5">
                    <div class="font-semibold text-slate-700">${row.target_label || row.target_table}</div>
                    ${row.description ? `<div class="text-[10px] text-slate-400 mt-0.5">${row.description}</div>` : ''}
                </td>
            </tr>`;
    }

    function renderTable(listing) {
        const paginationText = document.getElementById('activityPaginationText');

        if (!listing.data.length) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-semibold">
                        <i class="fa-solid fa-clock-rotate-left text-3xl mb-3 block opacity-60"></i>
                        No activity recorded yet.
                    </td>
                </tr>`;
            paginationText.innerText = 'Showing 0 to 0 of 0 records';
            document.getElementById('activityPaginationControls').innerHTML = '';
            return;
        }

        tableBody.innerHTML = listing.data.map(renderRow).join('');

        const { page, per_page, total, total_pages } = listing;
        const from = total === 0 ? 0 : (page - 1) * per_page + 1;
        const to = Math.min(page * per_page, total);
        paginationText.innerText = `Showing ${from} to ${to} of ${total} records`;

        const container = document.getElementById('activityPaginationControls');
        const prevDisabled = page <= 1;
        const nextDisabled = page >= total_pages;
        container.innerHTML = `
            <button data-page="${page - 1}" ${prevDisabled ? 'disabled' : ''} class="activity-page-btn px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${prevDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
                <i class="fa-solid fa-chevron-left text-[9px]"></i>
            </button>
            <button class="px-3 py-1.5 rounded-lg bg-brand-light border border-brand-border text-brand-dark font-extrabold">${page}</button>
            <span class="text-slate-400 px-1">of ${Math.max(total_pages, 1)}</span>
            <button data-page="${page + 1}" ${nextDisabled ? 'disabled' : ''} class="activity-page-btn px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 transition ${nextDisabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'}">
                <i class="fa-solid fa-chevron-right text-[9px]"></i>
            </button>`;
        container.querySelectorAll('.activity-page-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                currentPage = parseInt(btn.dataset.page, 10);
                loadActivity();
            });
        });
    }

    async function loadStats() {
        try {
            const response = await fetch(`${API_URL}?action=stats`);
            const result = await response.json();
            if (result.status === 'success') renderStats(result.data);
        } catch (err) {
            console.error('Error loading activity log stats:', err);
        }
    }

    async function loadActivity() {
        try {
            const response = await fetch(`${API_URL}?${buildQuery({ page: currentPage, per_page: 15 })}`);
            const result = await response.json();
            if (result.status !== 'success') return;
            renderTable(result);
        } catch (err) {
            console.error('Error loading activity log:', err);
        }
    }

    function reload() {
        currentPage = 1;
        loadStats();
        loadActivity();
    }

    ['activityModule', 'activityAction', 'activityDateFrom', 'activityDateTo'].forEach((id) => {
        document.getElementById(id).addEventListener('change', reload);
    });
    document.getElementById('activitySearch').addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(reload, 300);
    });
    document.getElementById('activityRefreshBtn').addEventListener('click', reload);

    loadStats();
    loadActivity();
});
