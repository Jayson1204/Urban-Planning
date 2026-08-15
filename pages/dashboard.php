<?php 
include '../includes/header.php';
include '../includes/sidebar.php';

$isSuperAdmin = !empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']);
$basePathResolver = $basePath ?? '../';
?>

<!-- Load Custom Dashboard Stylesheet -->
<link rel="stylesheet" href="<?php echo $basePathResolver; ?>assets/css/dashboard-analytics.css">

    <main class="flex-1 p-6 md:p-8 max-w-7xl mx-auto space-y-6 overflow-y-auto">
      
      <!-- DASHBOARD LOADING SKELETON -->
      <div id="dashboardSkeleton" class="space-y-6 transition-all duration-500 opacity-100 pointer-events-auto">
        
        <!-- 4 Metric Cards Skeleton -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <?php for($i = 0; $i < 4; $i++): ?>
          <div class="glass-panel rounded-2xl p-5 flex items-center justify-between dark:bg-slate-900/85 dark:border-slate-800/80">
            <div class="space-y-2.5 w-full">
              <div class="skeleton-loader h-3 w-32 rounded-md"></div>
              <div class="flex items-center gap-2">
                <div class="skeleton-loader h-7 w-24 rounded-lg"></div>
                <div class="skeleton-loader h-4 w-12 rounded-full"></div>
              </div>
              <div class="skeleton-loader h-2.5 w-40 rounded-md"></div>
            </div>
            <div class="skeleton-loader h-11 w-11 rounded-xl shrink-0 ml-3"></div>
          </div>
          <?php endfor; ?>
        </div>

        <!-- Charts & Activity Grid Skeleton -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Left Column Skeleton (2/3) -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Area Chart Skeleton -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                <div class="space-y-2">
                  <div class="skeleton-loader h-4 w-56 rounded-md"></div>
                  <div class="skeleton-loader h-3 w-72 rounded-md"></div>
                </div>
                <div class="skeleton-loader h-7 w-32 rounded-lg"></div>
              </div>
              <div class="skeleton-loader h-72 w-full rounded-xl"></div>
            </div>

            <!-- Activity Feed Skeleton -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div class="flex items-center justify-between">
                <div class="space-y-2">
                  <div class="skeleton-loader h-4 w-40 rounded-md"></div>
                  <div class="skeleton-loader h-3 w-60 rounded-md"></div>
                </div>
                <div class="skeleton-loader h-5 w-20 rounded-md"></div>
              </div>
              <div class="space-y-3 pt-2">
                <?php for($k = 0; $k < 3; $k++): ?>
                <div class="flex items-center justify-between gap-4 py-2 border-b border-slate-100/60 dark:border-slate-800/60 last:border-0">
                  <div class="flex items-center gap-3 w-full">
                    <div class="skeleton-loader h-8.5 w-8.5 rounded-lg shrink-0"></div>
                    <div class="space-y-1.5 w-full">
                      <div class="skeleton-loader h-3.5 w-48 rounded-md"></div>
                      <div class="skeleton-loader h-2.5 w-64 rounded-md"></div>
                    </div>
                  </div>
                  <div class="skeleton-loader h-3 w-16 rounded-md shrink-0"></div>
                </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>

          <!-- Right Column Skeleton (1/3) -->
          <div class="space-y-6">
            <!-- Doughnut Chart Skeleton -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div class="space-y-2">
                <div class="skeleton-loader h-4 w-40 rounded-md"></div>
                <div class="skeleton-loader h-3 w-52 rounded-md"></div>
              </div>
              <div class="skeleton-loader h-48 w-full rounded-xl"></div>
            </div>

            <!-- Radar Chart Skeleton -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div class="space-y-2">
                <div class="skeleton-loader h-4 w-40 rounded-md"></div>
                <div class="skeleton-loader h-3 w-52 rounded-md"></div>
              </div>
              <div class="skeleton-loader h-56 w-full rounded-xl"></div>
            </div>

            <!-- Progress KPIs Skeleton -->
            <div class="glass-panel rounded-2xl p-6 space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div class="space-y-2">
                <div class="skeleton-loader h-4 w-44 rounded-md"></div>
                <div class="skeleton-loader h-3 w-56 rounded-md"></div>
              </div>
              <div class="space-y-4 pt-2">
                <?php for($j = 0; $j < 3; $j++): ?>
                <div class="space-y-1.5">
                  <div class="flex justify-between">
                    <div class="skeleton-loader h-3 w-32 rounded-md"></div>
                    <div class="skeleton-loader h-3 w-8 rounded-md"></div>
                  </div>
                  <div class="skeleton-loader h-2 w-full rounded-full"></div>
                </div>
                <?php endfor; ?>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- DASHBOARD REAL CONTENT (Initially hidden with opacity-0) -->
      <div id="dashboardRealContent" class="space-y-6 hidden opacity-0 transition-all duration-700 ease-out transform translate-y-2">
        
        <!-- Glassmorphic Glowing Widgets Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

          <!-- Widget 1: Residents -->
          <div class="glass-panel glow-card-navy rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80">
            <div class="space-y-1">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-455 dark:text-slate-400">Total Residents</span>
              <h3 id="dashTotalResidents" class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">0</h3>
              <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium"><span id="dashTotalHouseholds">0</span> households on file</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-brand-light dark:bg-slate-800 text-brand-dark dark:text-brand-medium border border-brand-border/40 dark:border-slate-700/60 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-brand-dark group-hover:text-white dark:group-hover:bg-brand-medium">
              <i class="fa-solid fa-people-roof text-sm"></i>
            </div>
          </div>

          <!-- Widget 2: Housing Units -->
          <div class="glass-panel glow-card-teal rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80">
            <div class="space-y-1">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-455 dark:text-slate-400">Housing Units</span>
              <h3 id="dashTotalHousingUnits" class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">0</h3>
              <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium"><span id="dashHousingOccupancyRate">0</span>% occupancy rate</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-100/60 dark:border-emerald-900/40 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-emerald-600 group-hover:text-white">
              <i class="fa-solid fa-house-chimney text-sm"></i>
            </div>
          </div>

          <!-- Widget 3: Urban Projects -->
          <div class="glass-panel glow-card-purple rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80">
            <div class="space-y-1">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-455 dark:text-slate-400">Urban Projects</span>
              <h3 id="dashTotalUrbanProjects" class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">0</h3>
              <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium"><span id="dashProjectCompletionRate">0</span>% completion rate</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-purple-50 dark:bg-purple-950/20 text-purple-750 dark:text-purple-400 border border-purple-100/60 dark:border-purple-900/40 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-purple-600 group-hover:text-white">
              <i class="fa-solid fa-map-location-dot text-sm"></i>
            </div>
          </div>

          <!-- Widget 4: Field Survey -->
          <div class="glass-panel glow-card-amber rounded-2xl p-5 flex items-center justify-between group cursor-pointer dark:bg-slate-900/85 dark:border-slate-800/80">
            <div class="space-y-1">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-455 dark:text-slate-400">Survey Results</span>
              <h3 id="dashTotalSurveyResults" class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">0</h3>
              <p class="text-[9px] text-slate-400 dark:text-slate-500 font-medium"><span id="dashTotalSurveyAssignments">0</span> assignments logged</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-amber-50 dark:bg-amber-950/20 text-amber-705 dark:text-amber-450 border border-amber-100/60 dark:border-amber-900/40 flex items-center justify-center shadow-xs transition duration-300 group-hover:bg-amber-600 group-hover:text-white">
              <i class="fa-solid fa-clipboard-list text-sm"></i>
            </div>
          </div>

        </div>

        <!-- Advanced Analytics Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          
          <!-- Area Chart Column (2/3 width) -->
          <div class="lg:col-span-2 space-y-6">
            
            <!-- Records Overview Bar Chart -->
            <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                  <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-chart-area text-brand-dark dark:text-brand-medium"></i> System Records Overview
                  </h3>
                  <p class="text-[10px] text-slate-400 dark:text-slate-550 font-medium">Active record counts across every capstone module</p>
                </div>
              </div>
              <div class="relative h-72 w-full">
                <canvas id="dashRecordsChart"></canvas>
              </div>
            </div>

            <!-- Recent Activities Feed -->
            <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight">Recent Activity Feed</h3>
                  <p class="text-[10px] text-slate-400 dark:text-slate-550 font-medium">Latest records added across residents, housing, urban planning, and field survey</p>
                </div>
              </div>
              <div id="dashActivityFeed" class="divide-y divide-slate-100 dark:divide-slate-800">
                <div class="px-2 py-6 text-center text-slate-400 text-xs">Loading recent activity...</div>
              </div>
            </div>
          </div>

          <!-- Right Column (1/3 width) -->
          <div class="space-y-6">

            <!-- Doughnut Chart: Housing Occupancy -->
            <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div>
                <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight flex items-center gap-2">
                  <i class="fa-solid fa-chart-pie text-brand-dark dark:text-brand-medium"></i> Housing Occupancy
                </h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-550 font-medium">Active units by occupancy status</p>
              </div>
              <div class="relative h-48 w-full flex items-center justify-center">
                <canvas id="dashHousingOccupancyChart"></canvas>
              </div>
            </div>

            <!-- Doughnut Chart: Urban Project Status -->
            <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div>
                <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight flex items-center gap-2">
                  <i class="fa-solid fa-diagram-project text-brand-dark dark:text-brand-medium"></i> Urban Project Status
                </h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-550 font-medium">Active projects by lifecycle stage</p>
              </div>
              <div class="relative h-56 w-full flex items-center justify-center">
                <canvas id="dashProjectStatusChart"></canvas>
              </div>
            </div>

            <!-- Program KPIs -->
            <div class="glass-panel rounded-2xl p-6 shadow-xs space-y-4 dark:bg-slate-900/85 dark:border-slate-800/80">
              <div>
                <h3 class="font-extrabold text-slate-800 dark:text-white text-xs sm:text-sm tracking-tight">Program KPIs</h3>
                <p class="text-[10px] text-slate-400 dark:text-slate-550 font-medium">Completion rates across active records</p>
              </div>
              <div class="space-y-4 pt-1">

                <div class="space-y-1.5">
                  <div class="flex justify-between text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">
                    <span>Housing Occupancy Rate</span>
                    <span id="dashKpiHousingLabel" class="text-brand-dark dark:text-brand-medium">0%</span>
                  </div>
                  <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div id="dashKpiHousingBar" class="h-full bg-gradient-to-r from-brand-medium to-brand-dark rounded-full" style="width: 0%"></div>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <div class="flex justify-between text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">
                    <span>Survey Completion Rate</span>
                    <span id="dashKpiSurveyLabel" class="text-amber-650 dark:text-amber-400">0%</span>
                  </div>
                  <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div id="dashKpiSurveyBar" class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full" style="width: 0%"></div>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <div class="flex justify-between text-[10px] font-black uppercase text-slate-500 dark:text-slate-400">
                    <span>Urban Project Completion Rate</span>
                    <span id="dashKpiProjectLabel" class="text-emerald-600 dark:text-emerald-400">0%</span>
                  </div>
                  <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div id="dashKpiProjectBar" class="h-full bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-full" style="width: 0%"></div>
                  </div>
                </div>

              </div>
            </div>

          </div>
        </div>
      </div>
    </main>

    <!-- Load ChartJS Library & External Analytics Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php $dashAnalyticsJsPath = __DIR__ . '/../assets/js/dashboard-analytics.js'; $dashAnalyticsJsVer = @filemtime($dashAnalyticsJsPath) ?: time(); ?>
    <script src="<?php echo $basePathResolver; ?>assets/js/dashboard-analytics.js?v=<?php echo $dashAnalyticsJsVer; ?>"></script>
    
<?php include '../includes/footer.php'; ?>