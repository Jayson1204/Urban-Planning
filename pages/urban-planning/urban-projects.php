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
        <span>Urban Planning</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Urban Projects</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-diagram-project text-brand-dark"></i>
        Urban Projects
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Track individual infrastructure and development projects, their budgets, timelines, and progress under each development plan.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreateProjectModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Add Urban Project</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Projects</span>
        <h3 id="statTotalProjects" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-diagram-project text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-400"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Planned</span>
        <h3 id="statPlannedProjects" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-slate-100 group-hover:text-slate-700 transition duration-350">
        <i class="fa-solid fa-clipboard-list text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-cyan-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Ongoing</span>
        <h3 id="statOngoingProjects" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-cyan-50 group-hover:text-cyan-700 transition duration-350">
        <i class="fa-solid fa-person-digging text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Completed</span>
        <h3 id="statCompletedProjects" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-circle-check text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Search and Filters Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="projectSearchInput" placeholder="Search by project code, title, or coverage..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <select id="projectTypeFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Types</option>
        <option value="Road">Road</option>
        <option value="Drainage">Drainage</option>
        <option value="Public Building">Public Building</option>
        <option value="Utility">Utility</option>
        <option value="Park/Open Space">Park/Open Space</option>
        <option value="Other">Other</option>
      </select>
      <select id="projectStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Project Statuses</option>
        <option value="Planned">Planned</option>
        <option value="Ongoing">Ongoing</option>
        <option value="Completed">Completed</option>
        <option value="Delayed">Delayed</option>
        <option value="Cancelled">Cancelled</option>
      </select>
      <select id="projectRowStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Records</option>
        <option value="Active">Active</option>
        <option value="Archived">Archived</option>
      </select>
    </div>
  </div>

  <!-- Datatable -->
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
            <th class="px-6 py-4 w-1/4">Project</th>
            <th class="px-6 py-4">Plan</th>
            <th class="px-6 py-4">Type</th>
            <th class="px-6 py-4">Budget</th>
            <th class="px-6 py-4">Project Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="projectsTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="projectsPaginationText">Showing 0 to 0 of 0 projects</div>
      <div id="projectsPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT URBAN PROJECT MODAL -->
<div id="projectModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="projectModalIcon" class="fa-solid fa-diagram-project text-brand-medium"></i>
        <h3 id="projectModalTitle" class="font-extrabold text-sm tracking-tight uppercase">Add Urban Project</h3>
      </div>
      <button onclick="closeModal('projectModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="projectForm" onsubmit="handleSaveProject(event)">
      <input type="hidden" id="projectIdRef">
      <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Project Identification</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-1 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Project Code</label>
              <input type="text" id="projectCode" required placeholder="UP-0001" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="sm:col-span-2 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Project Title</label>
              <input type="text" id="projectTitle" required placeholder="e.g. Barangay 176 Road Widening" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Project Type</label>
              <select id="projectType" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Road">Road</option>
                <option value="Drainage">Drainage</option>
                <option value="Public Building">Public Building</option>
                <option value="Utility">Utility</option>
                <option value="Park/Open Space">Park/Open Space</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Project Status</label>
              <select id="projectStatus" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Planned">Planned</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
                <option value="Delayed">Delayed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
          </div>
          <div class="mt-3 space-y-1.5 relative">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Development Plan (optional)</label>
            <input type="hidden" id="projectPlanId">
            <input type="text" id="projectPlanSearch" placeholder="Search plan by code or title..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            <div id="projectPlanResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
            <div id="projectSelectedPlanBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
              <span id="projectSelectedPlanLabel" class="text-[11px] font-bold text-brand-dark"></span>
              <button type="button" onclick="clearSelectedProjectPlan()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Coverage & Contractor</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Barangay</label>
              <input type="text" id="projectBarangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="sm:col-span-2 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Coverage Area</label>
              <input type="text" id="projectCoverageArea" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="mt-3 space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Contractor</label>
            <input type="text" id="projectContractor" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Timeline & Budget</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Start Date</label>
              <input type="date" id="projectStartDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Target Completion</label>
              <input type="date" id="projectTargetDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Actual Completion</label>
              <input type="date" id="projectActualDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="mt-3 space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Budget (₱)</label>
            <input type="number" step="0.01" min="0" id="projectBudget" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Description</h5>
          <textarea id="projectDescription" rows="2" placeholder="Optional notes about this project..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('projectModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" id="projectSaveBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">Save Project</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. VIEW URBAN PROJECT MODAL -->
<div id="viewProjectModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-circle-info text-brand-medium"></i>
        <h3 class="font-extrabold text-sm tracking-tight uppercase">Urban Project Details</h3>
      </div>
      <button onclick="closeModal('viewProjectModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
      <div class="border-b border-slate-100 pb-3 space-y-1">
        <h4 id="viewProjectTitle" class="font-extrabold text-sm text-slate-900">&mdash;</h4>
        <p id="viewProjectMeta" class="text-xs text-slate-500 leading-relaxed">&mdash;</p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Plan</span>
          <p id="viewProjectPlan" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Budget</span>
          <p id="viewProjectBudget" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Contractor</span>
          <p id="viewProjectContractor" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Timeline</span>
          <p id="viewProjectTimeline" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
      </div>
      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Description</h5>
        <div id="viewProjectDescription" class="border border-slate-200/60 rounded-xl px-4 py-3 text-xs text-slate-600 leading-relaxed">No description on file.</div>
      </div>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end">
      <button onclick="closeModal('viewProjectModal')" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold cursor-pointer transition">Close</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast" class="fixed bottom-4 right-4 z-50 bg-slate-900 text-white text-xs font-bold px-4 py-3.5 rounded-xl shadow-lg flex items-center gap-3 transform translate-y-4 opacity-0 pointer-events-none transition-all duration-300">
  <div class="h-5 w-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px]">
    <i class="fa-solid fa-check"></i>
  </div>
  <span id="toastMsg" class="tracking-wide">Action executed successfully.</span>
</div>

<?php include '../../includes/footer.php'; ?>
