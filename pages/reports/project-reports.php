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
        <span class="text-brand-dark">Project Reports</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-diagram-project text-brand-dark"></i>
        Project Reports
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Filterable listing of urban project records for reporting, export, and printing.
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
    <h1 class="text-xl font-black text-slate-900">Urban Project Report</h1>
    <p class="text-xs text-slate-500">Generated <?php echo date('F j, Y g:i A'); ?></p>
  </div>

  <!-- Report Meta: generated date/time, filtered vs total count, applied filters -->
  <div id="reportMetaBar" class="text-[11px] font-semibold text-slate-500 bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5"></div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Projects</span>
        <h3 id="reportStatTotal" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-map-location-dot text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-400"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Planned</span>
        <h3 id="reportStatActive" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-file-lines text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-medium"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Ongoing</span>
        <h3 id="reportStatExtra" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-person-digging text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Completed</span>
        <h3 id="reportStatCompleted" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-circle-check text-sm"></i></div>
    </div>
  </div>

  <!-- Filters -->
  <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="reportSearch" placeholder="Search by code, title, or coverage area..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <input type="text" id="reportBarangay" placeholder="Barangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium transition w-32">
      <select id="reportProjectType" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Types</option>
        <option value="Road">Road</option>
        <option value="Drainage">Drainage</option>
        <option value="Public Building">Public Building</option>
        <option value="Utility">Utility</option>
        <option value="Park/Open Space">Park/Open Space</option>
        <option value="Other">Other</option>
      </select>
      <select id="reportProjectStatus" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Lifecycle Stages</option>
        <option value="Planned">Planned</option>
        <option value="Ongoing">Ongoing</option>
        <option value="Completed">Completed</option>
        <option value="Delayed">Delayed</option>
        <option value="Cancelled">Cancelled</option>
      </select>
      <select id="reportStatus" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Record Statuses</option>
        <option value="Active">Active</option>
        <option value="Archived">Archived</option>
      </select>
      <input type="date" id="reportDateFrom" title="Created from" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
      <input type="date" id="reportDateTo" title="Created to" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
    </div>
  </div>

  <!-- Datatable -->
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
            <th class="px-6 py-4 w-1/3">Project</th>
            <th class="px-6 py-4">Barangay / Type</th>
            <th class="px-6 py-4">Lifecycle Status</th>
            <th class="px-6 py-4">Budget</th>
            <th class="px-6 py-4">Created</th>
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
  window.CIVENTRAL_REPORT_TYPE = 'project';
</script>
<?php $reportsJsVer = @filemtime(__DIR__ . '/../../assets/js/reports/reports.js') ?: time(); ?>
<script src="<?php echo $basePath; ?>assets/js/reports/reports.js?v=<?php echo $reportsJsVer; ?>"></script>

<?php include '../../includes/footer.php'; ?>
