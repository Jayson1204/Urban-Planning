<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
$basePathResolver = $basePath ?? '../';
?>

<link rel="stylesheet" href="<?php echo $basePathResolver; ?>assets/css/dashboard-analytics.css">
<link rel="stylesheet" href="<?php echo $basePathResolver; ?>assets/css/reports-print.css">

<main class="flex-1 p-6 md:p-8 w-full space-y-8 overflow-y-auto">

  <!-- Breadcrumb & Page Header -->
  <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Analytics</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Overall Analytics</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-chart-line text-brand-dark"></i>
        Overall Analytics
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        A system-wide, real-time view of Housing, Applications, Urban Planning, User, and Location data across all capstone modules.
      </p>
    </div>
    <div class="shrink-0 flex items-center gap-2">
      <button id="analyticsPrintBtn" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition">
        <i class="fa-solid fa-print text-[10px]"></i>
        <span>Print / Save PDF</span>
      </button>
      <button id="analyticsExportBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-file-csv text-[10px]"></i>
        <span>Export Barangay CSV</span>
      </button>
    </div>
  </div>

  <!-- Print-only heading -->
  <div class="hidden print:block">
    <h1 class="text-xl font-black text-slate-900">Overall Analytics Report</h1>
    <p class="text-xs text-slate-500">Generated <?php echo date('F j, Y g:i A'); ?></p>
  </div>

  <!-- Time Filter Bar -->
  <div class="no-print flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mr-1">Time Range</span>
      <button data-range="all" class="analytics-range-btn px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer">All Time</button>
      <button data-range="today" class="analytics-range-btn px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer">Today</button>
      <button data-range="week" class="analytics-range-btn px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer">This Week</button>
      <button data-range="month" class="analytics-range-btn px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer">This Month</button>
      <button data-range="year" class="analytics-range-btn px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer">This Year</button>
      <button data-range="custom" class="analytics-range-btn px-3 py-2 rounded-xl text-xs font-bold border transition cursor-pointer">Custom</button>
    </div>
    <div id="analyticsCustomRange" class="hidden flex flex-wrap items-center gap-2">
      <input type="date" id="analyticsDateFrom" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
      <span class="text-slate-400 text-xs">to</span>
      <input type="date" id="analyticsDateTo" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
      <button id="analyticsApplyCustom" class="bg-brand-dark hover:bg-brand-medium text-white font-bold px-4 py-2.5 rounded-xl text-xs cursor-pointer transition">Apply</button>
    </div>
    <div id="analyticsRangeLabel" class="text-[11px] font-bold text-slate-400">Showing all-time data</div>
  </div>

  <!-- Fetch error banner -->
  <div id="analyticsErrorBanner" class="hidden bg-rose-50 border border-rose-150 text-rose-700 rounded-2xl px-5 py-4 text-xs font-bold flex items-center justify-between gap-4">
    <span><i class="fa-solid fa-triangle-exclamation mr-2"></i>Could not load analytics data. Please check your connection and try again.</span>
    <button id="analyticsRetryBtn" class="bg-white border border-rose-200 hover:bg-rose-100 px-3 py-1.5 rounded-lg cursor-pointer transition">Retry</button>
  </div>

  <!-- ===================== OVERALL SUMMARY ===================== -->
  <section class="space-y-4">
    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
      <i class="fa-solid fa-gauge-high text-brand-dark"></i> Overall Summary
    </h2>
    <div id="summaryCardsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <!-- Cards injected by JS -->
    </div>

    <!-- Recent Activity -->
    <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
      <div>
        <h3 class="font-extrabold text-slate-800 text-xs sm:text-sm tracking-tight">Recent Activity</h3>
        <p class="text-[10px] text-slate-400 font-medium">Latest records added across capstone modules</p>
      </div>
      <div id="analyticsActivityFeed" class="divide-y divide-slate-100">
        <div class="px-2 py-6 text-center text-slate-400 text-xs">Loading recent activity...</div>
      </div>
    </div>
  </section>

  <!-- ===================== HOUSING ANALYTICS ===================== -->
  <section class="space-y-4">
    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
      <i class="fa-solid fa-house-chimney text-brand-dark"></i> Housing Analytics
    </h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Housing Applications by Status</h3>
          <p class="text-[10px] text-slate-400 font-medium">Applicant / Qualified / Awarded / Disqualified / Cancelled</p>
        </div>
        <div class="relative h-56 w-full"><canvas id="housingByStatusChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Occupied vs Vacant Units</h3>
          <p class="text-[10px] text-slate-400 font-medium">Current housing unit inventory by occupancy status</p>
        </div>
        <div class="relative h-56 w-full"><canvas id="housingOccupancyChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Housing Units by Barangay</h3>
          <p class="text-[10px] text-slate-400 font-medium">Top barangays by housing unit count</p>
        </div>
        <div class="relative h-64 w-full"><canvas id="housingByBarangayChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Housing Applications Over Time</h3>
          <p class="text-[10px] text-slate-400 font-medium">Monthly application volume (demand trend)</p>
        </div>
        <div class="relative h-64 w-full"><canvas id="housingApplicationsOverTimeChart"></canvas></div>
      </div>
    </div>
  </section>

  <!-- ===================== APPLICATION ANALYTICS ===================== -->
  <section class="space-y-4">
    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
      <i class="fa-solid fa-file-lines text-brand-dark"></i> Application Analytics
    </h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-slate-400 block">Total</span>
        <h3 id="appStatTotal" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-amber-600 block">Pending</span>
        <h3 id="appStatPending" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-emerald-600 block">Approved</span>
        <h3 id="appStatApproved" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-rose-600 block">Rejected</span>
        <h3 id="appStatRejected" class="text-xl font-black text-slate-900">0</h3>
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Applications by Month</h3>
          <p class="text-[10px] text-slate-400 font-medium">Volume of housing applications received per month</p>
        </div>
        <div class="relative h-64 w-full"><canvas id="applicationsByMonthChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Approval vs Rejection Trend</h3>
          <p class="text-[10px] text-slate-400 font-medium">Pending, approved, and rejected applications per month</p>
        </div>
        <div class="relative h-64 w-full"><canvas id="approvalTrendChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 lg:col-span-2">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Distribution by Applicant Category</h3>
          <p class="text-[10px] text-slate-400 font-medium">Informal Settler / Calamity Victim / Senior Citizen / PWD / Government Employee / Other</p>
        </div>
        <div class="relative h-56 w-full max-w-md mx-auto"><canvas id="applicationsByCategoryChart"></canvas></div>
      </div>
    </div>
  </section>

  <!-- ===================== URBAN PLANNING ANALYTICS ===================== -->
  <section class="space-y-4">
    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
      <i class="fa-solid fa-map-location-dot text-brand-dark"></i> Urban Planning Analytics
    </h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-slate-400 block">Total</span>
        <h3 id="projStatTotal" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-slate-500 block">Planned</span>
        <h3 id="projStatPlanned" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-brand-medium block">Ongoing</span>
        <h3 id="projStatOngoing" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-emerald-600 block">Completed</span>
        <h3 id="projStatCompleted" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-amber-600 block">Delayed</span>
        <h3 id="projStatDelayed" class="text-xl font-black text-slate-900">0</h3>
      </div>
      <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
        <span class="text-[10px] font-black uppercase text-rose-600 block">Cancelled</span>
        <h3 id="projStatCancelled" class="text-xl font-black text-slate-900">0</h3>
      </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Projects by Status</h3>
          <p class="text-[10px] text-slate-400 font-medium">Lifecycle stage distribution</p>
        </div>
        <div class="relative h-56 w-full"><canvas id="projectsByStatusChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Projects Over Time</h3>
          <p class="text-[10px] text-slate-400 font-medium">Monthly project creation trend</p>
        </div>
        <div class="relative h-56 w-full"><canvas id="projectsOverTimeChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Projects by Barangay</h3>
          <p class="text-[10px] text-slate-400 font-medium">Top barangays by project count</p>
        </div>
        <div class="relative h-64 w-full"><canvas id="projectsByBarangayChart"></canvas></div>
      </div>
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Projects by Type</h3>
          <p class="text-[10px] text-slate-400 font-medium">Road / Drainage / Public Building / Utility / Park / Other</p>
        </div>
        <div class="relative h-64 w-full"><canvas id="projectsByTypeChart"></canvas></div>
      </div>
    </div>
  </section>

  <!-- ===================== USER ANALYTICS ===================== -->
  <section class="space-y-4">
    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
      <i class="fa-solid fa-users-gear text-brand-dark"></i> User Analytics
    </h2>
    <div id="userAnalyticsUnavailable" class="hidden bg-amber-50 border border-amber-150 text-amber-700 rounded-2xl px-5 py-4 text-xs font-bold">
      <i class="fa-solid fa-circle-info mr-2"></i>User analytics are unavailable right now &mdash; could not reach the shared user directory.
    </div>
    <div id="userAnalyticsContent" class="space-y-6">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
          <span class="text-[10px] font-black uppercase text-slate-400 block">Registered Users</span>
          <h3 id="userStatTotal" class="text-xl font-black text-slate-900">0</h3>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
          <span class="text-[10px] font-black uppercase text-brand-medium block">Staff / Admin</span>
          <h3 id="userStatStaffAdmin" class="text-xl font-black text-slate-900">0</h3>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
          <span class="text-[10px] font-black uppercase text-slate-500 block">Other Roles</span>
          <h3 id="userStatOther" class="text-xl font-black text-slate-900">0</h3>
        </div>
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4 shadow-xs">
          <span class="text-[10px] font-black uppercase text-emerald-600 block">Registered Residents</span>
          <h3 id="userStatResidents" class="text-xl font-black text-slate-900">0</h3>
        </div>
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
          <div>
            <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Users by Role</h3>
            <p class="text-[10px] text-slate-400 font-medium">Registered staff/employee accounts by role</p>
          </div>
          <div class="relative h-64 w-full"><canvas id="usersByRoleChart"></canvas></div>
        </div>
        <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
          <div>
            <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">User Registration Trend</h3>
            <p class="text-[10px] text-slate-400 font-medium">New staff accounts registered per month</p>
          </div>
          <div class="relative h-64 w-full"><canvas id="userRegistrationTrendChart"></canvas></div>
        </div>
        <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 lg:col-span-2">
          <div>
            <h3 class="font-extrabold text-slate-800 text-xs tracking-tight">Resident Registration Trend</h3>
            <p class="text-[10px] text-slate-400 font-medium">New residents added to the directory per month</p>
          </div>
          <div class="relative h-56 w-full"><canvas id="residentRegistrationTrendChart"></canvas></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== LOCATION ANALYTICS ===================== -->
  <section class="space-y-4">
    <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
      <i class="fa-solid fa-location-dot text-brand-dark"></i> Location Analytics
    </h2>
    <div id="locationTopBarangays" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
      <!-- Top 5 barangay cards injected by JS -->
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
              <th class="px-6 py-4 location-sort-th cursor-pointer select-none" data-key="barangay">Barangay <i class="fa-solid fa-sort text-[8px] opacity-50"></i></th>
              <th class="px-6 py-4 location-sort-th cursor-pointer select-none" data-key="residents">Residents <i class="fa-solid fa-sort text-[8px] opacity-50"></i></th>
              <th class="px-6 py-4 location-sort-th cursor-pointer select-none" data-key="housing_units">Housing Units <i class="fa-solid fa-sort text-[8px] opacity-50"></i></th>
              <th class="px-6 py-4 location-sort-th cursor-pointer select-none" data-key="applications">Applications <i class="fa-solid fa-sort text-[8px] opacity-50"></i></th>
              <th class="px-6 py-4 location-sort-th cursor-pointer select-none" data-key="projects">Urban Projects <i class="fa-solid fa-sort text-[8px] opacity-50"></i></th>
              <th class="px-6 py-4 location-sort-th cursor-pointer select-none" data-key="total">Total <i class="fa-solid fa-sort text-[8px] opacity-50"></i></th>
              <th class="px-6 py-4 location-sort-th cursor-pointer select-none" data-key="percent">% of Total <i class="fa-solid fa-sort text-[8px] opacity-50"></i></th>
            </tr>
          </thead>
          <tbody id="locationTableBody" class="divide-y divide-slate-100/80 text-xs"></tbody>
        </table>
      </div>
      <div class="no-print bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
        <div id="locationPaginationText">Showing 0 to 0 of 0 barangays</div>
        <div id="locationPaginationControls" class="flex items-center space-x-1"></div>
      </div>
    </div>
  </section>

</main>

<!-- Load ChartJS Library & Analytics Rendering Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php $analyticsJsVer = @filemtime(__DIR__ . '/../../assets/js/analytics/analytics.js') ?: time(); ?>
<script src="<?php echo $basePathResolver; ?>assets/js/analytics/analytics.js?v=<?php echo $analyticsJsVer; ?>"></script>

<?php include '../../includes/footer.php'; ?>
