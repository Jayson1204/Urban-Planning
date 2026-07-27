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
        <span class="text-slate-900">User Activities Audit System</span>
      </div>
      <h1 class="text-2xl font-black text-slate-950 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-clock-rotate-left text-[#0f172a]"></i>
        User Activities Audit System
      </h1>
      <p class="text-xs text-slate-500 max-w-3xl leading-relaxed font-medium">
        Monitor and review decentralized operational actions, security mutations, and system transactions across all municipal divisions.
      </p>
    </div>
    
    <!-- Action Buttons (Top Right) -->
    <div class="flex items-center gap-3 shrink-0">
      <div class="relative inline-block text-left" id="exportDropdownContainer">
        <button id="exportDropdownBtn" onclick="toggleExportDropdown(event)" 
          class="inline-flex items-center justify-center gap-2.5 px-4 py-2.5 border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 font-bold rounded-xl text-xs tracking-wide transition shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#0f172a]/20">
          <i class="fa-solid fa-download text-slate-400"></i>
          <span>Export Log</span>
          <i class="fa-solid fa-chevron-down text-[9px] text-slate-400"></i>
        </button>
        <!-- Dropdown Card -->
        <div id="exportDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-lg py-1 z-50 text-xs text-slate-600 transition-all transform scale-95 origin-top-right">
          <a href="#" onclick="mockExport('CSV', event)" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition font-bold">
            <i class="fa-solid fa-file-csv text-emerald-500 text-sm"></i>
            <span>Download CSV</span>
          </a>
          <a href="#" onclick="mockExport('Excel', event)" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-slate-50 text-slate-700 transition font-bold">
            <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i>
            <span>Download Excel</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Comprehensive Activity Filter Panel -->
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

    <!-- Filter Fields Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Date Picker -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterDate">Date Range Picker</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-calendar text-xs"></i>
          </span>
          <input type="date" id="filterDate" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition">
        </div>
      </div>

      <!-- Search User -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterSearch">Search User / Description</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
          </span>
          <input type="text" id="filterSearch" oninput="applyFilters()" placeholder="e.g. Joshua Suruiz, #ACT-90812..."
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition placeholder-slate-400">
        </div>
      </div>

      <!-- Department Selector -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterDepartment">Department Selector</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-building text-xs"></i>
          </span>
          <select id="filterDepartment" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition cursor-pointer">
            <option value="All">All Departments</option>
            <option value="Education & Scholarship">Education & Scholarship</option>
            <option value="Health & Sanitation">Health & Sanitation</option>
            <option value="Citizen Management">Citizen Management</option>
            <option value="Treasury">Revenue Collection & Treasury</option>
          </select>
          <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
          </span>
        </div>
      </div>

      <!-- Module Filter -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterModule">Module Filter</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-cubes text-xs"></i>
          </span>
          <select id="filterModule" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition cursor-pointer">
            <option value="All">All Modules</option>
            <option value="User Management">User Management</option>
            <option value="Scholarship">Scholarship</option>
            <option value="Citizen Management">Citizen Management</option>
          </select>
          <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
          </span>
        </div>
      </div>

      <!-- Action Type Selector -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterAction">Action Type Selector</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-bolt text-xs"></i>
          </span>
          <select id="filterAction" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition cursor-pointer">
            <option value="All">All Actions</option>
            <option value="Create">Create</option>
            <option value="Update">Update</option>
            <option value="Delete">Delete</option>
            <option value="Approve">Approve</option>
            <option value="Reject">Reject</option>
          </select>
          <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
          </span>
        </div>
      </div>

      <!-- Status Switcher -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterStatus">Status Switcher</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-shield-halved text-xs"></i>
          </span>
          <select id="filterStatus" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition cursor-pointer">
            <option value="All">All Statuses</option>
            <option value="Success">Success</option>
            <option value="Failed">Failed</option>
          </select>
          <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- System Audit Trail Datatable Card -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto w-full">
      <table class="w-full text-left border-collapse min-w-[1000px]">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200">
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Activity ID</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Date & Time</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Actor Identity</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Scope Location</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Action & Details</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Metadata Context</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Status</th>
          </tr>
        </thead>
        <tbody id="auditTableBody" class="divide-y divide-slate-100 text-xs">
          <!-- Row 1 -->
          <!-- Dynamic No Results Row -->
          <tr id="noResultsRow" class="hidden">
            <td colspan="7" class="py-12 text-center text-slate-400">
              <div class="flex flex-col items-center justify-center space-y-2">
                <i class="fa-solid fa-folder-open text-3xl text-slate-300"></i>
                <p class="text-xs font-bold">No matching audit logs found</p>
                <p class="text-[10px] font-semibold text-slate-400">Try adjusting your filters or resetting them to defaults.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Detail Modal -->
  <div id="logDetailsModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 transition-all duration-300">
    <div id="modalCard" class="bg-white border border-slate-200 shadow-2xl rounded-2xl w-full max-w-xl overflow-hidden flex flex-col transform scale-95 opacity-0 transition-all duration-300">
      
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-slate-200/80 bg-slate-50 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-xl bg-slate-905 flex items-center justify-center text-white bg-slate-900 text-sm shadow-sm">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <div>
            <h3 class="text-sm font-black text-slate-900">Audit Log Details</h3>
            <span id="modalActId" class="font-mono text-[10px] font-black text-slate-400 uppercase">#ACT-90812</span>
          </div>
        </div>
        <button onclick="closeLogDetailsModal()" class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 flex items-center justify-center transition focus:outline-none cursor-pointer">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>

      <!-- Modal Content (Receipt layout) -->
      <div class="p-6 space-y-5 overflow-y-auto max-h-[70vh] custom-scrollbar">
        <!-- Status Indicator Banner -->
        <div id="modalStatusBanner" class="p-4 rounded-xl flex items-center gap-3">
          <div id="modalStatusIconContainer" class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0">
            <!-- Dynamic Icon -->
          </div>
          <div>
            <h4 id="modalStatusTitle" class="text-xs font-bold">Transaction Success</h4>
            <p id="modalStatusMsg" class="text-[10px] leading-relaxed font-semibold">The action was verified and committed successfully in the municipal portal.</p>
          </div>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-2 gap-y-4 gap-x-6 border-b border-slate-100 pb-5 text-xs">
          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Actor Identity</span>
            <div id="modalActorName" class="font-bold text-slate-900">Joshua Suruiz</div>
            <div id="modalActorRole" class="mt-1">
              <!-- Dynamic badge -->
            </div>
          </div>
          
          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Date & Time</span>
            <div id="modalDateTime" class="font-bold text-slate-900">Jul 18, 2026 at 09:20 AM</div>
            <div class="text-[10px] text-slate-400 font-semibold mt-1">LGU Caloocan Local Time</div>
          </div>

          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Department Branch</span>
            <div id="modalDepartment" class="font-bold text-slate-900">Education & Scholarship</div>
          </div>

          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Module Target</span>
            <div id="modalModule" class="font-bold text-slate-900">User Management</div>
          </div>

          <div class="col-span-2">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Action Performed</span>
            <div class="flex items-center gap-2 mt-1">
              <span id="modalActionBadge" class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase">Create</span>
              <span id="modalActionDesc" class="font-extrabold text-slate-900 leading-snug">Created Department Admin Account</span>
            </div>
          </div>

          <div class="col-span-2 grid grid-cols-2 gap-4 bg-slate-50 border border-slate-200/60 p-3.5 rounded-xl">
            <div>
              <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">IP Address</span>
              <span id="modalIp" class="font-mono font-bold text-slate-700 text-xs">192.168.1.45</span>
            </div>
            <div>
              <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Device & Browser</span>
              <span id="modalDevice" class="font-bold text-slate-700 text-xs">Chrome - Windows</span>
            </div>
          </div>
        </div>

        <!-- Technical Metadata Payload (JSON) -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">Technical Context Payload</span>
            <button onclick="copyModalPayload()" class="text-[10px] font-bold text-[#0f172a] hover:text-[#1e3a8a] transition flex items-center gap-1 focus:outline-none cursor-pointer">
              <i class="fa-solid fa-copy"></i>
              Copy JSON
            </button>
          </div>
          <pre class="bg-slate-900 text-slate-200 p-4 rounded-xl text-[10px] font-mono overflow-x-auto leading-relaxed border border-slate-800 shadow-inner max-h-48 custom-scrollbar"><code id="modalPayloadText">{}</code></pre>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-slate-200/80 bg-slate-50 flex items-center justify-end gap-3">
        <button onclick="closeLogDetailsModal()" class="px-4 py-2 border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold rounded-xl text-xs tracking-wide transition cursor-pointer focus:outline-none">
          Close Details
        </button>
        <button onclick="window.print()" class="px-4 py-2 bg-[#0f172a] hover:bg-[#1e3a8a] text-white font-bold rounded-xl text-xs tracking-wide transition flex items-center gap-1.5 cursor-pointer focus:outline-none shadow-sm">
          <i class="fa-solid fa-print"></i>
          Print Log Entry
        </button>
      </div>

    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

</main>
<script src="../../assets/js/audit/user-activities.js"></script>

<?php include '../../includes/footer.php'; ?>
