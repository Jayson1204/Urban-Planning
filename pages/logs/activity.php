<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto">

  <!-- Breadcrumb & Page Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Logs</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Activity Log</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-clock-rotate-left text-brand-dark"></i>
        Activity Log
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Who created, updated, or archived a record across Resident, Housing, Urban Planning, and Field Survey management.
      </p>
    </div>
    <div class="shrink-0">
      <button id="activityRefreshBtn" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition">
        <i class="fa-solid fa-rotate-right text-[10px]"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Entries</span>
        <h3 id="activityStatTotal" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-list text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-cyan-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Today</span>
        <h3 id="activityStatToday" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-calendar-day text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Creates</span>
        <h3 id="activityStatCreates" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-circle-plus text-sm"></i></div>
    </div>
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Archives / Deletes</span>
        <h3 id="activityStatArchives" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450"><i class="fa-solid fa-box-archive text-sm"></i></div>
    </div>
  </div>

  <!-- Filters -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="activitySearch" placeholder="Search by actor, record, or description..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <select id="activityModule" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Modules</option>
        <option value="Resident Management">Resident Management</option>
        <option value="Housing Management">Housing Management</option>
        <option value="Urban Planning">Urban Planning</option>
        <option value="Field Survey">Field Survey</option>
      </select>
      <select id="activityAction" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Actions</option>
        <option value="Create">Create</option>
        <option value="Update">Update</option>
        <option value="Archive">Archive</option>
        <option value="Restore">Restore</option>
        <option value="Delete">Delete</option>
        <option value="Approve">Approve</option>
        <option value="Reject">Reject</option>
      </select>
      <input type="date" id="activityDateFrom" title="From date" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
      <input type="date" id="activityDateTo" title="To date" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition">
    </div>
  </div>

  <!-- Datatable -->
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
            <th class="px-6 py-4">Date/Time</th>
            <th class="px-6 py-4">Actor</th>
            <th class="px-6 py-4">Module</th>
            <th class="px-6 py-4">Action</th>
            <th class="px-6 py-4 w-1/3">Record</th>
          </tr>
        </thead>
        <tbody id="activityTableBody" class="divide-y divide-slate-100/80 text-xs"></tbody>
      </table>
    </div>
    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="activityPaginationText">Showing 0 to 0 of 0 records</div>
      <div id="activityPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<?php $activityLogJsVer = @filemtime(__DIR__ . '/../../assets/js/logs/activity-log.js') ?: time(); ?>
<script src="<?php echo $basePath; ?>assets/js/logs/activity-log.js?v=<?php echo $activityLogJsVer; ?>"></script>

<?php include '../../includes/footer.php'; ?>
