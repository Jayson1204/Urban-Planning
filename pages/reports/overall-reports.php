<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
$basePathResolver = $basePath ?? '../';
?>

<link rel="stylesheet" href="<?php echo $basePathResolver; ?>assets/css/reports-print.css">

<main class="flex-1 p-6 md:p-8 w-full space-y-8 overflow-y-auto">

  <!-- Breadcrumb & Page Header -->
  <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Reports</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Overall Reports</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-file-invoice text-brand-dark"></i>
        Overall Reports
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Pick a category to generate a detailed, filterable report -- with summary totals, printing, and CSV export -- built from real system data.
      </p>
    </div>
    <div class="shrink-0 flex items-center gap-2">
      <button onclick="window.print()" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition">
        <i class="fa-solid fa-print text-[10px]"></i>
        <span>Print Summary</span>
      </button>
      <button id="overallExportBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-file-csv text-[10px]"></i>
        <span>Export Summary CSV</span>
      </button>
    </div>
  </div>

  <!-- Print-only heading -->
  <div class="hidden print:block">
    <h1 class="text-xl font-black text-slate-900">Overall Reports Summary</h1>
    <p class="text-xs text-slate-500">Generated <?php echo date('F j, Y g:i A'); ?></p>
  </div>

  <!-- Report Meta: generated date/time (consolidated summary across all report types) -->
  <div id="overallMetaBar" class="text-[11px] font-semibold text-slate-500 bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2.5">
    <i class="fa-solid fa-clock text-[10px] mr-1 opacity-60"></i>Loading summary totals&hellip;
  </div>

  <!-- Report Category Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

    <a href="<?php echo $basePathResolver; ?>pages/reports/housing-reports.php" class="group bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 hover:border-brand-border transition flex flex-col gap-4">
      <div class="h-12 w-12 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100/60 flex items-center justify-center">
        <i class="fa-solid fa-house-chimney text-lg"></i>
      </div>
      <div class="space-y-1">
        <h3 class="font-black text-slate-900 text-sm">Housing Report</h3>
        <p class="text-[11px] text-slate-500 leading-relaxed">Housing unit inventory: status, occupancy, type, and barangay distribution.</p>
        <p class="text-[10px] font-black text-slate-400" data-overall-count="housing">Loading totals&hellip;</p>
      </div>
      <span class="text-[10px] font-black uppercase text-brand-dark mt-auto flex items-center gap-1.5">Open Report <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i></span>
    </a>

    <a href="<?php echo $basePathResolver; ?>pages/reports/application-reports.php" class="group bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 hover:border-brand-border transition flex flex-col gap-4">
      <div class="h-12 w-12 rounded-xl bg-brand-light text-brand-dark border border-brand-border/40 flex items-center justify-center">
        <i class="fa-solid fa-file-lines text-lg"></i>
      </div>
      <div class="space-y-1">
        <h3 class="font-black text-slate-900 text-sm">Housing Application Report</h3>
        <p class="text-[11px] text-slate-500 leading-relaxed">Beneficiary applications with status, category, and approval/rejection totals.</p>
        <p class="text-[10px] font-black text-slate-400" data-overall-count="application">Loading totals&hellip;</p>
      </div>
      <span class="text-[10px] font-black uppercase text-brand-dark mt-auto flex items-center gap-1.5">Open Report <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i></span>
    </a>

    <a href="<?php echo $basePathResolver; ?>pages/reports/project-reports.php" class="group bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 hover:border-brand-border transition flex flex-col gap-4">
      <div class="h-12 w-12 rounded-xl bg-purple-50 text-purple-750 border border-purple-100/60 flex items-center justify-center">
        <i class="fa-solid fa-map-location-dot text-lg"></i>
      </div>
      <div class="space-y-1">
        <h3 class="font-black text-slate-900 text-sm">Urban Planning Project Report</h3>
        <p class="text-[11px] text-slate-500 leading-relaxed">Projects by lifecycle status, location, timeline, and contractor.</p>
        <p class="text-[10px] font-black text-slate-400" data-overall-count="project">Loading totals&hellip;</p>
      </div>
      <span class="text-[10px] font-black uppercase text-brand-dark mt-auto flex items-center gap-1.5">Open Report <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i></span>
    </a>

    <a href="<?php echo $basePathResolver; ?>pages/reports/resident-reports.php" class="group bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 hover:border-brand-border transition flex flex-col gap-4">
      <div class="h-12 w-12 rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-100/60 flex items-center justify-center">
        <i class="fa-solid fa-people-roof text-lg"></i>
      </div>
      <div class="space-y-1">
        <h3 class="font-black text-slate-900 text-sm">Resident / Beneficiary Report</h3>
        <p class="text-[11px] text-slate-500 leading-relaxed">Registered residents by barangay, household, and status.</p>
        <p class="text-[10px] font-black text-slate-400" data-overall-count="resident">Loading totals&hellip;</p>
      </div>
      <span class="text-[10px] font-black uppercase text-brand-dark mt-auto flex items-center gap-1.5">Open Report <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i></span>
    </a>

    <a href="<?php echo $basePathResolver; ?>pages/reports/user-reports.php" class="group bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 hover:border-brand-border transition flex flex-col gap-4">
      <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-705 border border-amber-100/60 flex items-center justify-center">
        <i class="fa-solid fa-users-gear text-lg"></i>
      </div>
      <div class="space-y-1">
        <h3 class="font-black text-slate-900 text-sm">User / Staff Report</h3>
        <p class="text-[11px] text-slate-500 leading-relaxed">Registered staff accounts by role, department, and status.</p>
        <p class="text-[10px] font-black text-slate-400" data-overall-count="user">Loading totals&hellip;</p>
      </div>
      <span class="text-[10px] font-black uppercase text-brand-dark mt-auto flex items-center gap-1.5">Open Report <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i></span>
    </a>

    <a href="<?php echo $basePathResolver; ?>pages/reports/activity-reports.php" class="group bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:-translate-y-0.5 hover:border-brand-border transition flex flex-col gap-4">
      <div class="h-12 w-12 rounded-xl bg-slate-100 text-slate-600 border border-slate-200/60 flex items-center justify-center">
        <i class="fa-solid fa-clock-rotate-left text-lg"></i>
      </div>
      <div class="space-y-1">
        <h3 class="font-black text-slate-900 text-sm">Activity / Transaction Report</h3>
        <p class="text-[11px] text-slate-500 leading-relaxed">Who did what, when, across every local module -- from the activity log.</p>
        <p class="text-[10px] font-black text-slate-400" data-overall-count="activity">Loading totals&hellip;</p>
      </div>
      <span class="text-[10px] font-black uppercase text-brand-dark mt-auto flex items-center gap-1.5">Open Report <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i></span>
    </a>

  </div>

  <div class="no-print bg-slate-50 border border-slate-200/80 rounded-2xl px-5 py-4 text-[11px] text-slate-500 leading-relaxed">
    <i class="fa-solid fa-circle-info mr-1.5 opacity-60"></i>
    Every report is filterable by date range, barangay, status, and category where applicable, and supports CSV export and Print / Save as PDF. Looking for Field Survey records? See <a href="<?php echo $basePathResolver; ?>pages/reports/survey-reports.php" class="text-brand-dark font-bold hover:underline">Survey Reports</a> in the Reports menu.
  </div>

</main>

<?php $overallSummaryJsVer = @filemtime(__DIR__ . '/../../assets/js/reports/overall-summary.js') ?: time(); ?>
<script src="<?php echo $basePathResolver; ?>assets/js/reports/overall-summary.js?v=<?php echo $overallSummaryJsVer; ?>"></script>

<?php include '../../includes/footer.php'; ?>
