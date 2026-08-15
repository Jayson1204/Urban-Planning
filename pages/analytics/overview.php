<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
$basePathResolver = $basePath ?? '../';
?>

<link rel="stylesheet" href="<?php echo $basePathResolver; ?>assets/css/dashboard-analytics.css">

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto">

  <!-- Breadcrumb & Page Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Analytics</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Overview</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-chart-line text-brand-dark"></i>
        Program Analytics
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        A consolidated view of Resident, Housing, Urban Planning, and Field Survey data across all capstone modules.
      </p>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="glass-panel glow-card-navy rounded-2xl p-5 flex items-center justify-between">
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-455">Total Residents</span>
        <h3 id="statTotalResidents" class="text-2xl font-black text-slate-800 tracking-tight">0</h3>
        <p class="text-[9px] text-slate-400 font-medium"><span id="statTotalHouseholds">0</span> households on file</p>
      </div>
      <div class="h-11 w-11 rounded-xl bg-brand-light text-brand-dark border border-brand-border/40 flex items-center justify-center shadow-xs">
        <i class="fa-solid fa-people-roof text-sm"></i>
      </div>
    </div>

    <div class="glass-panel glow-card-teal rounded-2xl p-5 flex items-center justify-between">
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-455">Housing Units</span>
        <h3 id="statTotalHousingUnits" class="text-2xl font-black text-slate-800 tracking-tight">0</h3>
        <p class="text-[9px] text-slate-400 font-medium">Active socialized-housing inventory</p>
      </div>
      <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100/60 flex items-center justify-center shadow-xs">
        <i class="fa-solid fa-house-chimney text-sm"></i>
      </div>
    </div>

    <div class="glass-panel glow-card-purple rounded-2xl p-5 flex items-center justify-between">
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-455">Urban Projects</span>
        <h3 id="statTotalUrbanProjects" class="text-2xl font-black text-slate-800 tracking-tight">0</h3>
        <p class="text-[9px] text-slate-400 font-medium">Active development projects</p>
      </div>
      <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-750 border border-purple-100/60 flex items-center justify-center shadow-xs">
        <i class="fa-solid fa-map-location-dot text-sm"></i>
      </div>
    </div>

    <div class="glass-panel glow-card-amber rounded-2xl p-5 flex items-center justify-between">
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-455">Survey Results</span>
        <h3 id="statTotalSurveyResults" class="text-2xl font-black text-slate-800 tracking-tight">0</h3>
        <p class="text-[9px] text-slate-400 font-medium"><span id="statTotalSurveyAssignments">0</span> assignments logged</p>
      </div>
      <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-705 border border-amber-100/60 flex items-center justify-center shadow-xs">
        <i class="fa-solid fa-clipboard-list text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Charts & Activity Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left Column (2/3) -->
    <div class="lg:col-span-2 space-y-6">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
          <div>
            <h3 class="font-extrabold text-slate-800 text-xs sm:text-sm tracking-tight flex items-center gap-2">
              <i class="fa-solid fa-chart-pie text-brand-dark"></i> Housing Occupancy
            </h3>
            <p class="text-[10px] text-slate-400 font-medium">Active units by occupancy status</p>
          </div>
          <div class="relative h-48 w-full flex items-center justify-center">
            <canvas id="housingOccupancyChart"></canvas>
          </div>
        </div>

        <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
          <div>
            <h3 class="font-extrabold text-slate-800 text-xs sm:text-sm tracking-tight flex items-center gap-2">
              <i class="fa-solid fa-diagram-project text-brand-dark"></i> Urban Project Status
            </h3>
            <p class="text-[10px] text-slate-400 font-medium">Active projects by lifecycle stage</p>
          </div>
          <div class="relative h-48 w-full flex items-center justify-center">
            <canvas id="urbanProjectStatusChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Recent Activity Feed -->
      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="font-extrabold text-slate-800 text-xs sm:text-sm tracking-tight">Recent Activity</h3>
            <p class="text-[10px] text-slate-400 font-medium">Latest records added across capstone modules</p>
          </div>
        </div>
        <div id="analyticsActivityFeed" class="divide-y divide-slate-100">
          <div class="px-2 py-6 text-center text-slate-400 text-xs">Loading recent activity...</div>
        </div>
      </div>
    </div>

    <!-- Right Column (1/3) -->
    <div class="space-y-6">

      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs sm:text-sm tracking-tight flex items-center gap-2">
            <i class="fa-solid fa-clipboard-check text-brand-dark"></i> Survey Condition Ratings
          </h3>
          <p class="text-[10px] text-slate-400 font-medium">Distribution of recorded field survey findings</p>
        </div>
        <div class="relative h-56 w-full">
          <canvas id="surveyConditionChart"></canvas>
        </div>
      </div>

      <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4">
        <div>
          <h3 class="font-extrabold text-slate-800 text-xs sm:text-sm tracking-tight">Program KPIs</h3>
          <p class="text-[10px] text-slate-400 font-medium">Completion rates across active records</p>
        </div>
        <div class="space-y-4 pt-1">
          <div class="space-y-1.5">
            <div class="flex justify-between text-[10px] font-black uppercase text-slate-500">
              <span>Housing Occupancy Rate</span>
              <span id="kpiHousingOccupancyLabel" class="text-brand-dark">0%</span>
            </div>
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
              <div id="kpiHousingOccupancyBar" class="h-full bg-gradient-to-r from-brand-medium to-brand-dark rounded-full" style="width: 0%"></div>
            </div>
          </div>

          <div class="space-y-1.5">
            <div class="flex justify-between text-[10px] font-black uppercase text-slate-500">
              <span>Survey Completion Rate</span>
              <span id="kpiSurveyCompletionLabel" class="text-amber-650">0%</span>
            </div>
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
              <div id="kpiSurveyCompletionBar" class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full" style="width: 0%"></div>
            </div>
          </div>

          <div class="space-y-1.5">
            <div class="flex justify-between text-[10px] font-black uppercase text-slate-500">
              <span>Urban Project Completion Rate</span>
              <span id="kpiProjectCompletionLabel" class="text-emerald-600">0%</span>
            </div>
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
              <div id="kpiProjectCompletionBar" class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full" style="width: 0%"></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

</main>

<!-- Load ChartJS Library & Analytics Rendering Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo $basePathResolver; ?>assets/js/analytics/analytics.js"></script>

<?php include '../../includes/footer.php'; ?>
