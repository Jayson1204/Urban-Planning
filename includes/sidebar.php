    <?php
      $currentPage = basename($_SERVER['PHP_SELF']);

      $usermanagementPages = [
        'user-directory.php',
        'create-account.php',
        'account-status.php'
      ];

      $rolesmanagementPages = [
        'roles-management.php',
        'permissions.php',
        'module-management.php',
        'resource-management.php',
        'resourcemanagement.php',
        'action-management.php',
        'actionmanagement.php',
        'access-control.php'
      ];

      $departmentmanagementPages = [
        'departments.php'
      ];
      $citizenPages = [
        'citizen-directory.php',
        'citizen-account.php'
      ];
      $auditPages = [
        'user-activities.php',
        'login-history.php',
        'data-changes.php'
      ];
      $urbanPlanningPages = [
        'development-plans.php',
        'zoning-clearances.php',
        'permit-applications.php',
        'urban-projects.php',
        'infrastructure-records.php',
        'mapping.php',
        'subdivisions.php',
        'housing-projects.php'
      ];
      $residentPages = [
        'resident-directory.php',
        'household-management.php'
      ];
      $housingPages = [
        'housing-units.php',
        'beneficiaries.php',
        'occupancy.php',
        'relocation-records.php'
      ];
      $fieldSurveyPages = [
        'forms.php',
        'assignments.php',
        'results.php',
        'history.php'
      ];
      $reportsPages = [
        'overall-reports.php',
        'resident-reports.php',
        'housing-reports.php',
        'application-reports.php',
        'project-reports.php',
        'survey-reports.php',
        'user-reports.php',
        'activity-reports.php'
      ];
      $aiAssistantPages = [
        'chat.php'
      ];
      $activityLogPages = [
        'activity.php'
      ];
      $analyticsPages = [
        'overview.php'
      ];

      $isSuperAdmin = !empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']);
      $userGrantedRes = $headerUser['granted_resources'] ?? [];

      // Dynamic RBAC Permission Checker
      $hasResourceAccess = function($keywords) use ($isSuperAdmin, $userGrantedRes) {
          if ($isSuperAdmin) return true;
          if (empty($userGrantedRes)) return false;
          if (is_string($keywords)) $keywords = [$keywords];
          foreach ($userGrantedRes as $resName) {
              $resLower = strtolower($resName);
              foreach ($keywords as $kw) {
                  if (strpos($resLower, strtolower($kw)) !== false) return true;
              }
          }
          return false;
      };
    ?>

    <div id="sidebarBackdrop" onclick="closeMobileSidebar()" class="hidden fixed inset-x-0 bottom-0 top-20 bg-slate-950/50 z-20 lg:hidden"></div>

    <aside id="sidebar" class="fixed lg:sticky top-20 left-0 -translate-x-full lg:translate-x-0 bg-brand-light text-slate-600 w-72 h-[calc(100vh-5rem)] flex flex-col justify-between transition-all duration-300 border-r border-brand-border/60 z-40 lg:z-30 shrink-0 shadow-2xl lg:shadow-sm">

      <div class="flex flex-col h-full overflow-hidden">
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">

          <div class="sidebar-divider px-1 pb-3 mb-2 border-b">
            <button onclick="toggleSidebar()" class="sidebar-collapse-btn hidden lg:flex w-full py-2 rounded-xl border items-center justify-center focus:outline-none transition cursor-pointer shadow-xs" title="Collapse Menu Panel">
              <i id="toggleArrow" class="fa-solid fa-chevron-left text-xs"></i>
            </button>
            <button onclick="closeMobileSidebar()" class="sidebar-collapse-btn flex lg:hidden w-full py-2 rounded-xl border items-center justify-center gap-2 focus:outline-none transition cursor-pointer shadow-xs" title="Close Menu">
              <i class="fa-solid fa-xmark text-xs"></i>
              <span class="text-[10px] font-bold uppercase tracking-wider">Close Menu</span>
            </button>
          </div>

          <a href="<?php echo $basePath ?? '../'; ?>pages/dashboard.php" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo $currentPage == 'dashboard.php' ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
            <i class="fa-solid fa-gauge-high text-sm <?php echo $currentPage == 'dashboard.php' ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
            <span class="sidebar-text truncate">Dashboard</span>
          </a>

          <?php
          // Gated by production's shared Role & Permission system, like every other module.
          // Reuses the 'program analytics' resource the API already required (the page was
          // previously unreachable -- it had no sidebar entry at all).
          $canAccessAnalytics = $isSuperAdmin || $hasResourceAccess(['program analytics', 'overall analytics', 'analytics']);
          if ($canAccessAnalytics):
          ?>
          <a href="<?php echo $basePath ?? '../'; ?>pages/analytics/overview.php" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $analyticsPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
            <i class="fa-solid fa-chart-line text-sm <?php echo in_array($currentPage, $analyticsPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
            <span class="sidebar-text truncate">Overall Analytics</span>
          </a>
          <?php endif; ?>

          <span class="sidebar-text text-[9px] font-bold tracking-widest text-slate-400 uppercase block px-3 mb-2 mt-2">Urban Planning</span>

          <?php
          // Gated by production's shared Role & Permission system, like every other module:
          // create the module/resource in Module Management, grant it to a role in Permission Builder.
          $canAccessResidentMgmt = $isSuperAdmin || $hasResourceAccess(['resident management', 'resident directory', 'resident']);
          if ($canAccessResidentMgmt):
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('residentDropdown', 'residentChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $residentPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-people-roof text-sm <?php echo in_array($currentPage, $residentPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">Resident Management</span>
              </div>

              <div class="dropdown-right">
                  <i id="residentChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $residentPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="residentDropdown" class="<?php echo in_array($currentPage, $residentPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/resident/resident-directory.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'resident-directory.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-address-card text-[10px] <?php echo $currentPage == 'resident-directory.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Resident Directory</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/resident/household-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'household-management.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-house-chimney text-[10px] <?php echo $currentPage == 'household-management.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Household Management</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php
          // Same reasoning as Resident Management above.
          $canAccessHousingMgmt = $isSuperAdmin || $hasResourceAccess(['housing management', 'housing units', 'housing']);
          if ($canAccessHousingMgmt):
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('housingDropdown', 'housingChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $housingPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-house-chimney text-sm <?php echo in_array($currentPage, $housingPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">Housing Management</span>
              </div>

              <div class="dropdown-right">
                  <i id="housingChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $housingPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="housingDropdown" class="<?php echo in_array($currentPage, $housingPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/housing/housing-units.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'housing-units.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-house text-[10px] <?php echo $currentPage == 'housing-units.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Housing Units</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/housing/beneficiaries.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'beneficiaries.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-hand-holding-heart text-[10px] <?php echo $currentPage == 'beneficiaries.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Beneficiaries</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/housing/occupancy.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'occupancy.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-house-user text-[10px] <?php echo $currentPage == 'occupancy.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Occupancy</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/housing/relocation-records.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'relocation-records.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-truck-moving text-[10px] <?php echo $currentPage == 'relocation-records.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Relocation Records</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php
          // Same reasoning as Resident Management above.
          $canAccessFieldSurvey = $isSuperAdmin || $hasResourceAccess(['field survey management', 'field survey', 'survey forms']);
          if ($canAccessFieldSurvey):
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('fieldSurveyDropdown', 'fieldSurveyChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $fieldSurveyPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-clipboard-list text-sm <?php echo in_array($currentPage, $fieldSurveyPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">Field Survey</span>
              </div>

              <div class="dropdown-right">
                  <i id="fieldSurveyChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $fieldSurveyPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="fieldSurveyDropdown" class="<?php echo in_array($currentPage, $fieldSurveyPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/field-survey/forms.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'forms.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-clipboard-list text-[10px] <?php echo $currentPage == 'forms.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Survey Forms</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/field-survey/assignments.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'assignments.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-clipboard-user text-[10px] <?php echo $currentPage == 'assignments.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Survey Assignments</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/field-survey/results.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'results.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-circle-check text-[10px] <?php echo $currentPage == 'results.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Survey Results</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/field-survey/history.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'history.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-timeline text-[10px] <?php echo $currentPage == 'history.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Survey History</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php
          // Same reasoning as Resident Management above.
          $canAccessUrbanPlanning = $isSuperAdmin || $hasResourceAccess(['urban planning', 'development plans', 'zoning clearance', 'subdivision', 'building permit', 'permit applications']);
          if ($canAccessUrbanPlanning):
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('urbanPlanningDropdown', 'urbanPlanningChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $urbanPlanningPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-map-location-dot text-sm <?php echo in_array($currentPage, $urbanPlanningPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">Urban Planning</span>
              </div>

              <div class="dropdown-right">
                  <i id="urbanPlanningChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $urbanPlanningPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="urbanPlanningDropdown" class="<?php echo in_array($currentPage, $urbanPlanningPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/development-plans.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'development-plans.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-lines text-[10px] <?php echo $currentPage == 'development-plans.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Development Plans</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/zoning-clearances.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'zoning-clearances.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-stamp text-[10px] <?php echo $currentPage == 'zoning-clearances.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Zoning Clearances</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/permit-applications.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'permit-applications.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-building-shield text-[10px] <?php echo $currentPage == 'permit-applications.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Subdivision &amp; Building Review</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/urban-projects.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'urban-projects.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-diagram-project text-[10px] <?php echo $currentPage == 'urban-projects.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Urban Projects</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/infrastructure-records.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'infrastructure-records.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-road text-[10px] <?php echo $currentPage == 'infrastructure-records.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Infrastructure Records</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/mapping.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'mapping.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-map-location-dot text-[10px] <?php echo $currentPage == 'mapping.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Barangay Map</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/subdivisions.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'subdivisions.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-city text-[10px] <?php echo $currentPage == 'subdivisions.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Subdivisions</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/urban-planning/housing-projects.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'housing-projects.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-building-shield text-[10px] <?php echo $currentPage == 'housing-projects.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Housing Projects</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php
          // Same reasoning as Resident Management above.
          $canAccessReports = $isSuperAdmin || $hasResourceAccess(['reports', 'report management']);
          if ($canAccessReports):
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('reportsDropdown', 'reportsChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $reportsPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-file-invoice text-sm <?php echo in_array($currentPage, $reportsPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">Reports</span>
              </div>

              <div class="dropdown-right">
                  <i id="reportsChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $reportsPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="reportsDropdown" class="<?php echo in_array($currentPage, $reportsPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/overall-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'overall-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-invoice text-[10px] <?php echo $currentPage == 'overall-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Overall Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/resident-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'resident-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-people-roof text-[10px] <?php echo $currentPage == 'resident-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Resident Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/housing-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'housing-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-house-chimney text-[10px] <?php echo $currentPage == 'housing-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Housing Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/application-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'application-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-lines text-[10px] <?php echo $currentPage == 'application-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Application Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/project-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'project-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-diagram-project text-[10px] <?php echo $currentPage == 'project-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Project Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/survey-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'survey-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-clipboard-list text-[10px] <?php echo $currentPage == 'survey-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Survey Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/user-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'user-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-users-gear text-[10px] <?php echo $currentPage == 'user-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>User Reports</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/reports/activity-reports.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'activity-reports.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-clock-rotate-left text-[10px] <?php echo $currentPage == 'activity-reports.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Activity Reports</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php
          // Same reasoning as Resident Management above.
          $canAccessAiAssistant = $isSuperAdmin || $hasResourceAccess(['ai planning assistant', 'ai assistant', 'ai planning']);
          if ($canAccessAiAssistant):
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('aiAssistantDropdown', 'aiAssistantChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $aiAssistantPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-robot text-sm <?php echo in_array($currentPage, $aiAssistantPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">AI Assistant</span>
              </div>

              <div class="dropdown-right">
                  <i id="aiAssistantChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $aiAssistantPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="aiAssistantDropdown" class="<?php echo in_array($currentPage, $aiAssistantPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/ai-assistant/chat.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'chat.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-comments text-[10px] <?php echo $currentPage == 'chat.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Chat</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php
          // Local-only log: separate from the shared remote Audit Trail below, which has no
          // visibility into this capstone's own modules. Same manual Module Management step.
          $canAccessActivityLog = $isSuperAdmin || $hasResourceAccess(['activity logs', 'activity log']);
          if ($canAccessActivityLog):
          ?>
          <a href="<?php echo $basePath ?? '../'; ?>pages/logs/activity.php" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $activityLogPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
            <i class="fa-solid fa-clock-rotate-left text-sm <?php echo in_array($currentPage, $activityLogPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
            <span class="sidebar-text truncate">Activity Log</span>
          </a>
          <?php endif; ?>

          <span class="sidebar-text text-[9px] font-bold tracking-widest text-slate-400 uppercase block px-3 mb-2 mt-4">Main Controls</span>

          <?php
          $canAccessUserMgmt = $isSuperAdmin || $hasResourceAccess(['user directory', 'user account', 'users account', 'account status', 'user', 'account', 'employee']);
          if ($canAccessUserMgmt):
          ?>
          <div class="space-y-1">
           <button onclick="toggleDropdown('userDropdown', 'userChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $usermanagementPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
             <div class="flex items-center space-x-3">
                <i class="fa-solid fa-users-gear text-sm <?php echo in_array($currentPage, $usermanagementPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                <span class="sidebar-text truncate">User Management</span>
             </div>
             <div class="dropdown-right">
                <i id="userChevron"
                   class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $usermanagementPages) ? 'rotate-180' : ''; ?>"></i>
             </div>
            </button>
            <div id="userDropdown" class="<?php echo in_array($currentPage, $usermanagementPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/usermanagement/user-directory.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'user-directory.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-user-pen text-[10px] <?php echo $currentPage == 'user-directory.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>User Directory</span></a>

              <?php if ($isSuperAdmin || $hasResourceAccess(['users account', 'user account', 'create account'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/usermanagement/create-account.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'create-account.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-user-plus text-[10px] <?php echo $currentPage == 'create-account.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Create Staff Accounts</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['account status', 'status control', 'status'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/usermanagement/account-status.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'account-status.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-user-check text-[10px] <?php echo $currentPage == 'account-status.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Activate/Deactivate</span></a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php
          $canAccessRoleMgmt = $isSuperAdmin || $hasResourceAccess(['role', 'permission', 'module', 'resource', 'access control']);
          if ($canAccessRoleMgmt):
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('roleDropdown', 'roleChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $rolesmanagementPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
              <div class="flex items-center space-x-3">
                  <i class="fa-solid fa-user-shield text-sm <?php echo in_array($currentPage, $rolesmanagementPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                  <span class="sidebar-text truncate">Role & Permissions</span>
              </div>
              <div class="dropdown-right">
                  <i id="roleChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $rolesmanagementPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>
            <div id="roleDropdown" class="<?php echo in_array($currentPage, $rolesmanagementPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <?php if ($isSuperAdmin || $hasResourceAccess(['roles'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/roles-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'roles-management.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-users text-[10px] <?php echo $currentPage == 'roles-management.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Roles</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['module management'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/module-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'module-management.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-cubes text-[10px] <?php echo $currentPage == 'module-management.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Module Management</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['resource management'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/resource-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (in_array($currentPage, ['resource-management.php', 'resourcemanagement.php'])) ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-file-lines text-[10px] <?php echo (in_array($currentPage, ['resource-management.php', 'resourcemanagement.php'])) ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Resource Management</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['action management'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/action-management.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (in_array($currentPage, ['action-management.php', 'actionmanagement.php'])) ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-bolt text-[10px] <?php echo (in_array($currentPage, ['action-management.php', 'actionmanagement.php'])) ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Action Management</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['permission builder'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/permissions.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'permissions.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-key text-[10px] <?php echo $currentPage == 'permissions.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Permission Builder</span></a>
              <?php endif; ?>

              <?php if ($isSuperAdmin || $hasResourceAccess(['role permission matrix'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/rolespermission/access-control.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'access-control.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-shield-halved text-[10px] <?php echo $currentPage == 'access-control.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Role Permission Matrix</span></a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php
          $canAccessDeptMgmt = $hasResourceAccess(['department', 'position', 'sitemap', 'department management']);
          if ($canAccessDeptMgmt):
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('deptDropdown', 'deptChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $departmentmanagementPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-sitemap text-sm <?php echo in_array($currentPage, $departmentmanagementPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Department Management</span>
              </div>
                <div class="dropdown-right">
                    <i id="deptChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $departmentmanagementPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="deptDropdown" class="<?php echo in_array($currentPage, $departmentmanagementPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/department/departments.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'departments.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-building text-[10px] <?php echo $currentPage == 'departments.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Departments</span></a>
            </div>
          </div>
          <?php endif; ?>

          <?php
          $canAccessCitizenMgmt = $hasResourceAccess(['citizen', 'kyc', 'verification']);
          if ($canAccessCitizenMgmt):
          ?>
          <div class="space-y-1">
            <button onclick="toggleDropdown('citizenDropdown', 'citizenChevron')" class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $citizenPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-address-book text-sm <?php echo in_array($currentPage, $citizenPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                    <span class="sidebar-text truncate">Citizen Management</span>
              </div>
                <div class="dropdown-right">
                    <i id="citizenChevron"
                       class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $citizenPages) ? 'rotate-180' : ''; ?>"></i>
                </div>
            </button>

            <div id="citizenDropdown" class="<?php echo in_array($currentPage, $citizenPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <?php if ($hasResourceAccess('citizen directory')): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen/citizen-directory.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'citizen-directory.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-id-card text-[10px] <?php echo $currentPage == 'citizen-directory.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Citizen Directory</span></a>
              <?php endif; ?>

              <?php if ($hasResourceAccess(['citizen account', 'kyc', 'verification'])): ?>
              <a href="<?php echo $basePath ?? '../'; ?>pages/citizen/citizen-account.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'citizen-account.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-database text-[10px] <?php echo $currentPage == 'citizen-account.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Citizen Account</span></a>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php
          $canAccessAuditLogs = $hasResourceAccess(['audit', 'activity', 'log', 'change', 'history']);
          if ($canAccessAuditLogs):
          ?>
          <div class="space-y-1">
           <button
                  onclick="toggleDropdown('auditDropdown', 'auditChevron')"
                  class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs tracking-wide transition group cursor-pointer <?php echo in_array($currentPage, $auditPages) ? 'bg-white text-brand-dark border border-brand-border font-bold shadow-xs' : 'hover:bg-white dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-brand-dark dark:hover:text-[#86B6F6] border border-transparent font-semibold'; ?>">

                  <div class="flex items-center space-x-3">
                      <i class="fa-solid fa-clock-rotate-left text-sm <?php echo in_array($currentPage, $auditPages) ? 'text-brand-medium' : 'text-slate-400'; ?> group-hover:text-brand-medium transition"></i>
                      <span class="sidebar-text truncate">Audit Logs System</span>
              </div>

              <div class="dropdown-right">
                  <i id="auditChevron"
                    class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200 <?php echo in_array($currentPage, $auditPages) ? 'rotate-180' : ''; ?>"></i>
              </div>
            </button>

            <div id="auditDropdown" class="<?php echo in_array($currentPage, $auditPages) ? '' : 'hidden'; ?> pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
              <a href="<?php echo $basePath ?? '../'; ?>pages/audit/user-activities.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'user-activities.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-chart-line text-[10px] <?php echo $currentPage == 'user-activities.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>User Activities</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/audit/login-history.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'login-history.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-history text-[10px] <?php echo $currentPage == 'login-history.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Login History</span></a>
              <a href="<?php echo $basePath ?? '../'; ?>pages/audit/data-changes.php" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo $currentPage == 'data-changes.php' ? 'text-brand-medium font-black bg-white border border-brand-border/40 shadow-xs' : 'text-slate-500 hover:text-brand-dark'; ?>"><i class="fa-solid fa-pen-to-square text-[10px] <?php echo $currentPage == 'data-changes.php' ? 'text-brand-medium' : 'opacity-50'; ?>"></i> <span>Data Changes</span></a>
            </div>
          </div>
          <?php endif; ?>
        </nav>

        <div class="p-4 border-t shrink-0 sidebar-footer">
          <a href="#" onclick="openLogoutModal(event)" class="sidebar-logout-btn flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-bold tracking-wide transition group cursor-pointer">
            <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
            <span class="sidebar-text truncate">Logout</span>
          </a>
        </div>
      </div>
    </aside>
