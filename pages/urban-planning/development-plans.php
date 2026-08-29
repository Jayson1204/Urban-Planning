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
        <span class="text-brand-dark">Development Plans</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-map-location-dot text-brand-dark"></i>
        Development Plans
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Register and manage the city's urban development plans, including plan type, coverage, timeline, and budget.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreatePlanModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Add Development Plan</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Plans</span>
        <h3 id="statTotalPlans" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-map text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-400"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Draft</span>
        <h3 id="statDraftPlans" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-slate-100 group-hover:text-slate-700 transition duration-350">
        <i class="fa-solid fa-pen-ruler text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-cyan-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Active</span>
        <h3 id="statActivePlans" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-cyan-50 group-hover:text-cyan-700 transition duration-350">
        <i class="fa-solid fa-drafting-compass text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Completed</span>
        <h3 id="statCompletedPlans" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
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
      <input type="text" id="planSearchInput" placeholder="Search by plan code, title, or coverage area..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2">
      <input type="text" id="planBarangayFilter" placeholder="Filter by barangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium transition w-40">
      <select id="planTypeFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Types</option>
        <option value="Comprehensive Land Use Plan">Comprehensive Land Use Plan</option>
        <option value="Zoning Plan">Zoning Plan</option>
        <option value="Infrastructure Plan">Infrastructure Plan</option>
        <option value="Development Framework">Development Framework</option>
        <option value="Other">Other</option>
      </select>
      <select id="planStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Plan Statuses</option>
        <option value="Draft">Draft</option>
        <option value="Active">Active</option>
        <option value="Completed">Completed</option>
        <option value="Archived">Archived</option>
      </select>
      <select id="rowStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Statuses</option>
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
            <th class="px-6 py-4 w-1/4">Plan</th>
            <th class="px-6 py-4">Type</th>
            <th class="px-6 py-4">Coverage</th>
            <th class="px-6 py-4">Timeline</th>
            <th class="px-6 py-4">Plan Status</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="developmentPlansTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="planPaginationText">Showing 0 to 0 of 0 plans</div>
      <div id="planPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT DEVELOPMENT PLAN MODAL -->
<div id="planModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="planModalIcon" class="fa-solid fa-map-location-dot text-brand-medium"></i>
        <h3 id="planModalTitle" class="font-extrabold text-sm tracking-tight uppercase">Add Development Plan</h3>
      </div>
      <button onclick="closeModal('planModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="planForm" onsubmit="handleSaveDevelopmentPlan(event)">
      <input type="hidden" id="planIdRef">
      <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Plan Identification</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-1 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Plan Code</label>
              <input type="text" id="planCode" required placeholder="DP-0001" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="sm:col-span-2 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Plan Title</label>
              <input type="text" id="planTitle" required placeholder="e.g. Caloocan North Comprehensive Land Use Plan" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Plan Type</label>
              <select id="planType" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Comprehensive Land Use Plan">Comprehensive Land Use Plan</option>
                <option value="Zoning Plan">Zoning Plan</option>
                <option value="Infrastructure Plan">Infrastructure Plan</option>
                <option value="Development Framework">Development Framework</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Plan Status</label>
              <select id="planStatus" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Draft">Draft</option>
                <option value="Active">Active</option>
                <option value="Completed">Completed</option>
                <option value="Archived">Archived</option>
              </select>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Coverage & Ownership</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Barangay</label>
              <input type="text" id="planBarangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="sm:col-span-2 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Coverage Area</label>
              <input type="text" id="planCoverageArea" placeholder="e.g. Barangays 1-10, North District" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="mt-3 space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Lead Department</label>
            <input type="text" id="planLeadDepartment" placeholder="e.g. City Planning and Development Office" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Timeline & Budget</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Start Date</label>
              <input type="date" id="planStartDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">End Date</label>
              <input type="date" id="planEndDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Budget Allocation (₱)</label>
              <input type="number" step="0.01" min="0" id="planBudget" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Description</h5>
          <textarea id="planDescription" rows="2" placeholder="Optional notes about this plan..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('planModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" id="planSaveBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">Save Plan</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. VIEW DEVELOPMENT PLAN MODAL -->
<div id="viewPlanModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-circle-info text-brand-medium"></i>
        <h3 class="font-extrabold text-sm tracking-tight uppercase">Development Plan Details</h3>
      </div>
      <button onclick="closeModal('viewPlanModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">

      <div class="border-b border-slate-100 pb-3 space-y-1">
        <h4 id="viewPlanCode" class="font-extrabold text-sm text-slate-900">&mdash;</h4>
        <p id="viewPlanMeta" class="text-xs text-slate-500 leading-relaxed">&mdash;</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Coverage</span>
          <p id="viewPlanCoverage" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Budget Allocation</span>
          <p id="viewPlanBudget" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Timeline</span>
          <p id="viewPlanTimeline" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Lead Department</span>
          <p id="viewPlanDepartment" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
      </div>

      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Description</h5>
        <div id="viewPlanDescription" class="border border-slate-200/60 rounded-xl px-4 py-3 text-xs text-slate-600 leading-relaxed">No description on file.</div>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Plan Documents</h5>
          <button type="button" onclick="openPlanDocumentUpload()" class="text-[10px] font-bold text-brand-dark hover:underline cursor-pointer">
            <i class="fa-solid fa-upload mr-1"></i>Upload
          </button>
        </div>
        <div id="viewPlanDocuments" class="border border-slate-200/60 rounded-xl overflow-hidden text-xs divide-y divide-slate-100">
          <div class="px-4 py-3 text-slate-400">No documents uploaded yet.</div>
        </div>
      </div>

      <form id="planDocumentUploadForm" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2.5" onsubmit="handleUploadPlanDocument(event)">
        <input type="hidden" id="uploadPlanId">
        <div class="grid grid-cols-2 gap-2.5">
          <select id="planDocumentType" required class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full bg-white focus:outline-none focus:border-brand-medium">
            <option value="">Document Type</option>
            <option value="Zoning Map">Zoning Map</option>
            <option value="Budget Breakdown">Budget Breakdown</option>
            <option value="Environmental Assessment">Environmental Assessment</option>
            <option value="Public Consultation Record">Public Consultation Record</option>
            <option value="Other">Other</option>
          </select>
          <input type="file" id="planDocumentFile" required accept=".pdf,.jpg,.jpeg,.png,.xlsx,.docx" class="text-[11px] w-full">
        </div>
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Save Document</button>
      </form>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end">
      <button onclick="closeModal('viewPlanModal')" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold cursor-pointer transition">Close</button>
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
