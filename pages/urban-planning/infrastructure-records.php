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
        <span class="text-brand-dark">Infrastructure Records</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-road text-brand-dark"></i>
        Infrastructure Records
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Register and track the condition of the city's infrastructure assets, optionally linked to the urban project that built or maintains them.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreateInfraModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Add Infrastructure Record</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Records</span>
        <h3 id="statTotalInfra" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-road text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Good Condition</span>
        <h3 id="statGoodInfra" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-circle-check text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Needs Repair</span>
        <h3 id="statNeedsRepairInfra" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-amber-50 group-hover:text-amber-700 transition duration-350">
        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-cyan-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Under Construction</span>
        <h3 id="statUnderConstructionInfra" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-cyan-50 group-hover:text-cyan-700 transition duration-350">
        <i class="fa-solid fa-person-digging text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Search and Filters Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="infraSearchInput" placeholder="Search by name, location, or project code..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <select id="infraTypeFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Types</option>
        <option value="Road">Road</option>
        <option value="Bridge">Bridge</option>
        <option value="Drainage System">Drainage System</option>
        <option value="Water Supply">Water Supply</option>
        <option value="Street Lighting">Street Lighting</option>
        <option value="Public Facility">Public Facility</option>
        <option value="Other">Other</option>
      </select>
      <select id="infraConditionFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Conditions</option>
        <option value="Good">Good</option>
        <option value="Needs Repair">Needs Repair</option>
        <option value="Under Construction">Under Construction</option>
        <option value="Non-Functional">Non-Functional</option>
      </select>
      <select id="infraRowStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
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
            <th class="px-6 py-4 w-1/4">Infrastructure</th>
            <th class="px-6 py-4">Type</th>
            <th class="px-6 py-4">Linked Project</th>
            <th class="px-6 py-4">Condition</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="infraTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="infraPaginationText">Showing 0 to 0 of 0 records</div>
      <div id="infraPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT INFRASTRUCTURE RECORD MODAL -->
<div id="infraModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="infraModalIcon" class="fa-solid fa-road text-brand-medium"></i>
        <h3 id="infraModalTitle" class="font-extrabold text-sm tracking-tight uppercase">Add Infrastructure Record</h3>
      </div>
      <button onclick="closeModal('infraModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="infraForm" onsubmit="handleSaveInfra(event)">
      <input type="hidden" id="infraIdRef">
      <div class="p-6 space-y-3.5 max-h-[65vh] overflow-y-auto">
        <div class="space-y-1.5">
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Infrastructure Name</label>
          <input type="text" id="infraName" required placeholder="e.g. Barangay 176 Main Drainage Line" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Type</label>
            <select id="infraType" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
              <option value="Road">Road</option>
              <option value="Bridge">Bridge</option>
              <option value="Drainage System">Drainage System</option>
              <option value="Water Supply">Water Supply</option>
              <option value="Street Lighting">Street Lighting</option>
              <option value="Public Facility">Public Facility</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Condition</label>
            <select id="infraCondition" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
              <option value="Good">Good</option>
              <option value="Needs Repair">Needs Repair</option>
              <option value="Under Construction">Under Construction</option>
              <option value="Non-Functional">Non-Functional</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Barangay</label>
            <input type="text" id="infraBarangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          </div>
          <div class="space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Completion Date</label>
            <input type="date" id="infraCompletionDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Location Details</label>
          <input type="text" id="infraLocationDetails" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
        </div>
        <div class="space-y-1.5 relative">
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Linked Urban Project (optional)</label>
          <input type="hidden" id="infraProjectId">
          <input type="text" id="infraProjectSearch" placeholder="Search project by code or title..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          <div id="infraProjectResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
          <div id="infraSelectedProjectBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
            <span id="infraSelectedProjectLabel" class="text-[11px] font-bold text-brand-dark"></span>
            <button type="button" onclick="clearSelectedInfraProject()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
          </div>
        </div>
        <div class="space-y-1.5">
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Remarks</label>
          <textarea id="infraRemarks" rows="2" placeholder="Optional notes..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('infraModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" id="infraSaveBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">Save Record</button>
      </div>
    </form>
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
