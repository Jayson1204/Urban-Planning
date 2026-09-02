<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/reports-print.css">

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto">

  <!-- Breadcrumb & Page Header -->
  <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Reports</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">User / Staff Reports</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-users-gear text-brand-dark"></i>
        User / Staff Reports
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Filterable listing of registered staff/employee accounts for reporting, export, and printing. Sourced from the shared production user directory.
      </p>
    </div>
    <div class="shrink-0 flex items-center gap-2">
      <button onclick="window.print()" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition">
        <i class="fa-solid fa-print text-[10px]"></i>
        <span>Print / Save PDF</span>
      </button>
      <button id="reportExportBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-file-csv text-[10px]"></i>
        <span>Export CSV</span>
      </button>
    </div>
  </div>

  <!-- Print-only heading -->
  <div class="hidden print:block">
    <h1 class="text-xl font-black text-slate-900">User / Staff Report</h1>
    <p class="text-xs text-slate-500">Generated <?php echo date('F j, Y g:i A'); ?></p>
  </div>

  <!-- Report Meta: generated date/time, filtered vs total count, applied filters -->
  <div id="reportMetaBar" class="text-[11px] font-semibold text-slate-500 bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5"></div>

  <!-- Fetch error banner (shown if the shared user directory can't be reached) -->
  <div id="reportUnavailableBanner" class="hidden bg-amber-50 border border-amber-150 text-amber-700 rounded-2xl px-5 py-4 text-xs font-bold">
    <i class="fa-solid fa-circle-info mr-2"></i><span id="reportUnavailableText">Could not reach the shared user directory. Please try again shortly.</span>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Users</span>
        <h3 id="reportStatTotal" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-users text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Active</span>
        <h3 id="reportStatActive" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-circle-check text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-400"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Inactive</span>
        <h3 id="reportStatExtra" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-user-slash text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-purple-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Admin Roles</span>
        <h3 id="reportStatArchived" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-user-shield text-sm"></i></div>
    </div>
  </div>

  <!-- Filters -->
  <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="reportSearch" placeholder="Search by name or email..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <input type="text" id="reportRole" placeholder="Role" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium transition w-32">
      <input type="text" id="reportStatus" placeholder="Status" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium transition w-28">
      <input type="date" id="reportDateFrom" title="Registered from" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
      <input type="date" id="reportDateTo" title="Registered to" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
    </div>
  </div>

  <!-- Datatable -->
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
            <th class="px-6 py-4 w-1/3">Name / Email</th>
            <th class="px-6 py-4">Role / Department</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Registered</th>
            <th class="px-6 py-4">Last Login</th>
          </tr>
        </thead>
        <tbody id="reportTableBody" class="divide-y divide-slate-100/80 text-xs"></tbody>
      </table>
    </div>
    <div class="no-print bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="reportPaginationText">Showing 0 to 0 of 0 records</div>
      <div id="reportPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<script>
  window.CIVENTRAL_REPORT_TYPE = 'user';
</script>
<?php $reportsJsVer = @filemtime(__DIR__ . '/../../assets/js/reports/reports.js') ?: time(); ?>
<script src="<?php echo $basePath; ?>assets/js/reports/reports.js?v=<?php echo $reportsJsVer; ?>"></script>

<?php include '../../includes/footer.php'; ?>
