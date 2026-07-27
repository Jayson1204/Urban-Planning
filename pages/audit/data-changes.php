<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto bg-slate-50">

  <!-- Breadcrumb & Page Header -->
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-200/80 pb-5">
    <div class="space-y-1.5">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Audit Logs</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-slate-900">Data Mutation Logs</span>
      </div>
      <h1 class="text-2xl font-black text-slate-950 tracking-tight flex items-center gap-2.5 mt-4">
        <i class="fa-solid fa-database text-[#0f172a]"></i>
        Data Mutation & Records Audit
      </h1>
      <p class="text-xs text-slate-500 max-w-3xl leading-relaxed font-medium">
        Track structural row edits, history changes, and delta record mutations to maximize data accountability.
      </p>
    </div>

    <!-- Non-Destructive Action Controls -->
    <div class="flex items-center gap-3 shrink-0">
      <button onclick="refreshLogs()" class="inline-flex items-center justify-center gap-2 px-3 py-2.5 border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 font-bold rounded-xl text-xs tracking-wide transition shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#0f172a]/20">
        <i class="fa-solid fa-rotate text-slate-400"></i>
        <span>Refresh Log</span>
      </button>

      <button onclick="window.print()" class="inline-flex items-center justify-center gap-2 px-3 py-2.5 border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 font-bold rounded-xl text-xs tracking-wide transition shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#0f172a]/20">
        <i class="fa-solid fa-print text-slate-400"></i>
        <span>Print Log</span>
      </button>

      <!-- Export Dropdown -->
      <div class="relative inline-block text-left" id="exportDropdownContainer">
        <button id="exportDropdownBtn" onclick="toggleExportDropdown(event)" 
          class="inline-flex items-center justify-center gap-2 px-3.5 py-2.5 border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 font-bold rounded-xl text-xs tracking-wide transition shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#0f172a]/20">
          <i class="fa-solid fa-file-export text-slate-400"></i>
          <span>Export Logs</span>
          <i class="fa-solid fa-chevron-down text-[9px] text-slate-400"></i>
        </button>
        <!-- Dropdown Card -->
        <div id="exportDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 text-xs text-slate-600 transition-all transform scale-95 origin-top-right">
          <a href="#" onclick="exportLogs('PDF', event)" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition font-bold">
            <i class="fa-solid fa-file-pdf text-red-500 text-sm"></i>
            <span>Export to PDF</span>
          </a>
          <a href="#" onclick="exportLogs('Excel', event)" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition font-bold">
            <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i>
            <span>Export to Excel</span>
          </a>
          <a href="#" onclick="exportLogs('CSV', event)" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition font-bold">
            <i class="fa-solid fa-file-csv text-emerald-500 text-sm"></i>
            <span>Download CSV</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Policy Information Banner -->
  <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex items-start gap-3.5 text-xs text-blue-900 leading-relaxed font-semibold">
    <div class="h-6 w-6 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 text-blue-700">
      <i class="fa-solid fa-circle-info"></i>
    </div>
    <div>
      <h4 class="font-bold text-blue-950">Read-Only Immutable Mutation Trail</h4>
      <p class="text-[10px] text-blue-800 mt-0.5">
        To maintain absolute municipal portal compliance and trust parameters, the log record database is permanently protected against administrative deletes, structural updates, or edits.
      </p>
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-5 md:p-6 space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2 text-slate-900">
        <i class="fa-solid fa-sliders text-xs text-slate-400"></i>
        <h3 class="text-xs font-bold uppercase tracking-wider">Search & Filter Controls</h3>
      </div>
      <button onclick="resetFilters()" class="text-xs font-bold text-[#0f172a] hover:text-[#1e3a8a] transition flex items-center gap-1.5 focus:outline-none cursor-pointer">
        <i class="fa-solid fa-rotate-right text-[10px]"></i>
        Reset Filters
      </button>
    </div>

    <!-- Filters Fields Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Search Query -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterSearch">Search Query</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
          </span>
          <input type="text" id="filterSearch" oninput="applyFilters()" placeholder="e.g. Record ID, User, Action, Description..."
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition placeholder-slate-400">
        </div>
      </div>

      <!-- Date Range Picker -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterDate">Date Picker</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-calendar text-xs"></i>
          </span>
          <input type="date" id="filterDate" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition">
        </div>
      </div>

      <!-- Affected Module Selector -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterModule">Affected Module</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-cubes text-xs"></i>
          </span>
          <select id="filterModule" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition cursor-pointer">
            <option value="All">All Modules</option>
          </select>
          <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Data Mutation Trail Table -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto w-full">
      <table class="w-full text-left border-collapse min-w-[1200px]">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200">
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Change ID</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Timestamp</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Actor</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Coordinates</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Action / Field</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Target Entity</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Result Status</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Description / Justification</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Action</th>
          </tr>
        </thead>
        <tbody id="mutationTableBody" class="divide-y divide-slate-100 text-xs">
          <!-- Dynamically populated from MySQL Database via assets/js/audit/data-changes.js -->
        </tbody>
      </table>
    </div>
  </div>

  <!-- Snapshot Inspection Modal -->
  <div id="mutationDetailsModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 transition-all duration-300">
    <div id="modalCard" class="bg-white border border-slate-200 shadow-2xl rounded-2xl w-full max-w-3xl overflow-hidden flex flex-col transform scale-95 opacity-0 transition-all duration-300">
      
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-slate-200/80 bg-slate-50 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-xl bg-slate-900 flex items-center justify-center text-white text-sm shadow-sm">
            <i class="fa-solid fa-code-compare"></i>
          </div>
          <div>
            <h3 class="text-sm font-black text-slate-900">Record Delta Visualizer</h3>
            <span id="modalMutId" class="font-mono text-[10px] font-black text-slate-400 uppercase">#MUT-1001</span>
          </div>
        </div>
        <button onclick="closeMutationModal()" class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 flex items-center justify-center transition focus:outline-none cursor-pointer">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-5 overflow-y-auto max-h-[75vh] custom-scrollbar text-xs">
        
        <!-- Summary Context Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-50 border border-slate-200/60 p-4 rounded-xl">
          <div>
            <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Audit Actor</span>
            <span id="modalActor" class="font-bold text-slate-800">System User</span>
          </div>
          <div>
            <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Timestamp</span>
            <span id="modalTime" class="font-bold text-slate-800 font-mono">Jul 26, 2026 / 01:00 AM</span>
          </div>
          <div>
            <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Affected Module</span>
            <span id="modalModule" class="font-bold text-slate-800">System</span>
          </div>
          <div>
            <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Record Identifier</span>
            <span id="modalRecord" class="font-bold text-slate-800 font-mono">REC-CORE</span>
          </div>
        </div>

        <!-- Delta comparison visualizer boxes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Old Value block -->
          <div class="bg-rose-50/50 border border-rose-200/60 rounded-xl p-4 space-y-1.5">
            <span class="text-[8px] font-black uppercase tracking-wider text-rose-600 block">Original Parameter State</span>
            <div class="flex items-baseline gap-2">
              <span id="modalFieldOldLabel" class="text-[10px] font-bold text-slate-500">Action:</span>
              <span id="modalOldValue" class="text-xs font-bold text-rose-700 line-through bg-rose-50 border border-rose-100 px-2 py-0.5 rounded">Previous State</span>
            </div>
          </div>
          
          <!-- New Value block -->
          <div class="bg-emerald-50/50 border border-emerald-200 rounded-xl p-4 space-y-1.5">
            <span class="text-[8px] font-black uppercase tracking-wider text-emerald-700 block">Committed Mutation State</span>
            <div class="flex items-baseline gap-2">
              <span id="modalFieldNewLabel" class="text-[10px] font-bold text-slate-500">Action:</span>
              <span id="modalNewValue" class="text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded">Success</span>
            </div>
          </div>
        </div>

        <!-- Split Screen Code Comparison -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Left side: Original JSON -->
          <div class="space-y-1.5">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">Original Record Snapshot</span>
            <pre class="bg-slate-900 text-slate-200 p-4 rounded-xl text-[10px] font-mono overflow-x-auto border border-slate-800 shadow-inner max-h-48 custom-scrollbar"><code id="modalOldJson">{}</code></pre>
          </div>

          <!-- Right side: Modified JSON -->
          <div class="space-y-1.5">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">Committed Audit Context Snapshot</span>
            <pre class="bg-slate-900 text-slate-200 p-4 rounded-xl text-[10px] font-mono overflow-x-auto border border-slate-800 shadow-inner max-h-48 custom-scrollbar"><code id="modalNewJson">{}</code></pre>
          </div>
        </div>

        <!-- Justification Description -->
        <div class="bg-slate-50 border border-slate-200/60 p-4 rounded-xl space-y-1">
          <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block">Mutation Justification</span>
          <p id="modalReason" class="text-[11px] font-bold text-slate-700 leading-relaxed">Description provided in audit log entry.</p>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-slate-200/80 bg-slate-50 flex items-center justify-end gap-3">
        <button onclick="closeMutationModal()" class="px-4 py-2 border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold rounded-xl text-xs tracking-wide transition cursor-pointer focus:outline-none">
          Close Details
        </button>
      </div>

    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

</main>

<script src="../../assets/js/audit/data-changes.js"></script>

<?php include '../../includes/footer.php'; ?>
