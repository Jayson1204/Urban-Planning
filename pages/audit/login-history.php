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
        <span class="text-slate-900">Login History & Session Audit</span>
      </div>
      <h1 class="text-2xl font-black text-slate-950 tracking-tight flex items-center gap-2.5 mt-4">
        <i class="fa-solid fa-shield-halved text-[#0f172a]"></i>
        Login History & Authentication Audit
      </h1>
      <p class="text-xs text-slate-500 max-w-3xl leading-relaxed font-medium">
        Track real-time system entries, monitor session lifetimes, and catch unauthorized authentication compromises.
      </p>
    </div>
  </div>

  <!-- Quick-Glance Security Metrics Ribbon (4 Columns) -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <!-- Successful Logins -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden transition hover:shadow-md">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1.5">
        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Successful Logins</span>
        <div class="flex items-baseline space-x-2">
          <h3 class="text-2xl font-black text-slate-900 tracking-tight"><span id="successfulCount">0</span></h3>
          <span class="text-[9px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded flex items-center gap-0.5">
            🟢 Clean
          </span>
        </div>
      </div>
      <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-emerald-50 group-hover:text-emerald-700 group-hover:border-emerald-200/50 transition">
        <i class="fa-solid fa-circle-check text-base"></i>
      </div>
    </div>

    <!-- Failed Attempts -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden transition hover:shadow-md">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-rose-500"></div>
      <div class="space-y-1.5">
        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Failed Attempts</span>
        <div class="flex items-baseline space-x-2">
          <h3 class="text-2xl font-black text-slate-900 tracking-tight"><span id="failedCount">0</span></h3>
          <span class="text-[9px] font-bold text-rose-700 bg-rose-50 border border-rose-100 px-1.5 py-0.5 rounded flex items-center gap-0.5">
            🔴 Failed
          </span>
        </div>
      </div>
      <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-rose-50 group-hover:text-rose-700 group-hover:border-rose-200/50 transition">
        <i class="fa-solid fa-triangle-exclamation text-base"></i>
      </div>
    </div>

    <!-- Active Sessions -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden transition hover:shadow-md">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-blue-500"></div>
      <div class="space-y-1.5">
        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Active Sessions</span>
        <div class="flex items-baseline space-x-2">
          <h3 class="text-2xl font-black text-slate-900 tracking-tight"><span id="activeCount">0</span></h3>
          <span class="text-[9px] font-bold text-blue-700 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded flex items-center gap-1 animate-pulse">
            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span> Online
          </span>
        </div>
      </div>
      <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-blue-50 group-hover:text-blue-700 group-hover:border-blue-200/50 transition">
        <i class="fa-solid fa-user-clock text-base"></i>
      </div>
    </div>

    <!-- Account Locks -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden transition hover:shadow-md">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
      <div class="space-y-1.5">
        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Account Locks</span>
        <div class="flex items-baseline space-x-2">
          <h3 class="text-2xl font-black text-slate-900 tracking-tight"><span id="lockCount">0</span></h3>
          <span class="text-[9px] font-bold text-amber-700 bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded flex items-center gap-0.5">
            🔒 Locked
          </span>
        </div>
      </div>
      <div class="h-10 w-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-amber-50 group-hover:text-amber-700 group-hover:border-amber-200/50 transition">
        <i class="fa-solid fa-user-lock text-base"></i>
      </div>
    </div>
  </div>

  <!-- Audit Filtering Controls -->
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Search Username/Email -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterSearch">Search User / Email</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
          </span>
          <input type="text" id="filterSearch" oninput="applyFilters()" placeholder="e.g. joshua.suruiz..."
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

      <!-- Status Filter -->
      <div class="space-y-1.5">
        <label class="text-[10px] font-black uppercase tracking-wider text-slate-400 block" for="filterStatus">Login Status Filter</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-shield-halved text-xs"></i>
          </span>
          <select id="filterStatus" onchange="applyFilters()"
            class="w-full bg-white border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl pl-10 pr-4 py-2.5 appearance-none focus:outline-none focus:border-[#0f172a] focus:ring-2 focus:ring-[#0f172a]/10 transition cursor-pointer">
            <option value="All">All Attempt Types</option>
            <option value="Successful Login">Successful Login</option>
            <option value="Failed Login">Failed Login</option>
            <option value="Logout">Logout</option>
            <option value="Password Changed">Password Changed</option>
            <option value="Session Expired">Session Expired</option>
            <option value="Account Locked">Account Locked</option>
          </select>
          <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
          </span>
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
            <option value="Central IT">Central IT</option>
            <option value="Education">Education</option>
            <option value="Health">Health</option>
            <option value="Treasury">Treasury</option>
          </select>
          <span class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
            <i class="fa-solid fa-chevron-down text-[9px]"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Authentication Audit Trail Table -->
  <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto w-full">
      <table class="w-full text-left border-collapse min-w-[1100px]">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-200">
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Login ID</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Identity Details</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Timeline Triggers</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider text-center">Session Lifespan</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Authentication Status</th>
            <th class="py-3.5 px-5 text-[10px] font-black text-slate-400 uppercase tracking-wider">Network & Footprint</th>
          </tr>
        </thead>
        <tbody id="loginTableBody" class="divide-y divide-slate-100 text-xs">
          <!-- Dynamic No Results Row -->
          <tr id="noResultsRow" class="hidden">
            <td colspan="6" class="py-12 text-center text-slate-400">
              <div class="flex flex-col items-center justify-center space-y-2">
                <i class="fa-solid fa-user-slash text-3xl text-slate-300"></i>
                <p class="text-xs font-bold">No matching authentication logs found</p>
                <p class="text-[10px] font-semibold text-slate-400">Try adjusting your filters or resetting them to defaults.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Session Profile Inspector Modal -->
  <div id="sessionDetailsModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 transition-all duration-300">
    <div id="modalCard" class="bg-white border border-slate-200 shadow-2xl rounded-2xl w-full max-w-xl overflow-hidden flex flex-col transform scale-95 opacity-0 transition-all duration-300">
      
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-slate-200/80 bg-slate-50 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-xl bg-slate-950 flex items-center justify-center text-white text-sm shadow-sm bg-slate-900">
            <i class="fa-solid fa-key"></i>
          </div>
          <div>
            <h3 class="text-sm font-black text-slate-900">Session Profile Inspector</h3>
            <span id="modalLogId" class="font-mono text-[10px] font-black text-slate-400 uppercase">#LOG-45091</span>
          </div>
        </div>
        <button onclick="closeSessionModal()" class="h-8 w-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 flex items-center justify-center transition focus:outline-none cursor-pointer">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-5 overflow-y-auto max-h-[70vh] custom-scrollbar">
        <!-- Event Status Header Banner -->
        <div id="modalStatusBanner" class="p-4 rounded-xl flex items-center gap-3">
          <div id="modalStatusIconContainer" class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0">
            <!-- Dynamic Icon -->
          </div>
          <div>
            <h4 id="modalStatusTitle" class="text-xs font-bold">Successful Login</h4>
            <p id="modalStatusMsg" class="text-[10px] leading-relaxed font-semibold">Security token registered. Session initialized.</p>
          </div>
        </div>

        <!-- Identity and Timelines -->
        <div class="grid grid-cols-2 gap-y-4 gap-x-6 border-b border-slate-100 pb-5 text-xs">
          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Actor Account</span>
            <div id="modalActorName" class="font-bold text-slate-900">joshua.suruiz</div>
            <div id="modalActorEmail" class="text-[10px] text-slate-500 font-semibold mt-0.5">joshua@caloocan.gov.ph</div>
          </div>
          
          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Role / Department</span>
            <div id="modalRoleDept" class="font-bold text-slate-900">Superadmin | Central IT</div>
          </div>

          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Login Time</span>
            <div id="modalLoginTime" class="font-bold text-slate-900">Jul 18, 2026, 08:30 AM</div>
          </div>

          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Logout / Expire Time</span>
            <div id="modalLogoutTime" class="font-bold text-slate-900">—</div>
          </div>

          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Session Lifespan</span>
            <div id="modalLifespan" class="font-bold text-slate-900">Active</div>
          </div>

          <div>
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-1">Authentication State</span>
            <div id="modalStatusBadge" class="mt-1">
              <!-- status badge -->
            </div>
          </div>

          <div class="col-span-2 bg-slate-50 border border-slate-200/60 p-3.5 rounded-xl space-y-2">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Access Log Details</span>
            <p id="modalLogDetails" class="text-[10px] font-semibold text-slate-700 leading-relaxed">Successful MFA challenge verified using hardware security key (YubiKey 5C).</p>
          </div>

          <div class="col-span-2 grid grid-cols-3 gap-4 bg-slate-50 border border-slate-200/60 p-3.5 rounded-xl font-mono text-[10px]">
            <div>
              <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">IP Address</span>
              <span id="modalIp" class="font-bold text-slate-700">192.168.1.12</span>
            </div>
            <div>
              <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Device Platform</span>
              <span id="modalDevice" class="font-bold text-slate-700">Desktop - Chrome</span>
            </div>
            <div>
              <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 block mb-0.5">Approx. Location</span>
              <span id="modalLocation" class="font-bold text-slate-700">Caloocan City, PH</span>
            </div>
          </div>
        </div>

        <!-- Technical Payload (JSON) -->
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
        <button onclick="closeSessionModal()" class="px-4 py-2 border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold rounded-xl text-xs tracking-wide transition cursor-pointer focus:outline-none">
          Close Inspector
        </button>
      </div>

    </div>
  </div>

  <!-- Toast Notification Container -->
  <div id="toastContainer" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

</main>

<script>
// Filter interaction rules
function applyFilters() {
  const searchVal = document.getElementById('filterSearch').value.toLowerCase().trim();
  const dateVal = document.getElementById('filterDate').value;
  const statusVal = document.getElementById('filterStatus').value;
  const deptVal = document.getElementById('filterDepartment').value;

  const rows = document.querySelectorAll('#loginTableBody tr:not(#noResultsRow)');
  let matchCount = 0;

  rows.forEach(row => {
    const rowActor = row.getAttribute('data-actor').toLowerCase();
    const rowEmail = row.getAttribute('data-email').toLowerCase();
    const rowDate = row.getAttribute('data-date');
    const rowStatus = row.getAttribute('data-status');
    const rowDept = row.getAttribute('data-department');

    let searchMatch = true;
    if (searchVal) {
      searchMatch = rowActor.includes(searchVal) || rowEmail.includes(searchVal);
    }

    let dateMatch = true;
    if (dateVal) {
      dateMatch = (rowDate === dateVal);
    }

    let statusMatch = (statusVal === 'All' || rowStatus === statusVal);
    let deptMatch = (deptVal === 'All' || rowDept === deptVal);

    if (searchMatch && dateMatch && statusMatch && deptMatch) {
      row.style.display = '';
      matchCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const noResultsRow = document.getElementById('noResultsRow');
  if (matchCount === 0) {
    noResultsRow.classList.remove('hidden');
  } else {
    noResultsRow.classList.add('hidden');
  }
}

function resetFilters() {
  document.getElementById('filterSearch').value = '';
  document.getElementById('filterDate').value = '';
  document.getElementById('filterStatus').value = 'All';
  document.getElementById('filterDepartment').value = 'All';
  applyFilters();
  showToast("Filters Reset", "All authentication filter inputs have been returned to default.");
}

// Toast Alert System
function showToast(title, message) {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = "flex items-start gap-3 bg-white border border-slate-200 shadow-xl rounded-xl p-4 min-w-[320px] max-w-sm transition-all duration-300 transform translate-y-2 opacity-0";
  toast.innerHTML = `
    <div class="h-6 w-6 shrink-0 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-white">
      <i class="fa-solid fa-info text-[10px]"></i>
    </div>
    <div class="flex-1 space-y-0.5">
      <h4 class="text-xs font-bold text-slate-900">${title}</h4>
      <p class="text-[10px] font-semibold text-slate-500 leading-relaxed">${message}</p>
    </div>
    <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer">
      <i class="fa-solid fa-xmark text-[10px]"></i>
    </button>
  `;
  container.appendChild(toast);
  
  setTimeout(() => {
    toast.classList.remove('translate-y-2', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
  }, 10);

  setTimeout(() => {
    toast.classList.remove('translate-y-0', 'opacity-100');
    toast.classList.add('translate-y-2', 'opacity-0');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 4000);
}

// Modal logic
function openSessionModal(row) {
  const id = row.getAttribute('data-id');
  const actor = row.getAttribute('data-actor');
  const email = row.getAttribute('data-email');
  const role = row.getAttribute('data-role');
  const department = row.getAttribute('data-department');
  const loginTime = row.getAttribute('data-login-time');
  const logoutTime = row.getAttribute('data-logout-time');
  const lifespan = row.getAttribute('data-lifespan');
  const status = row.getAttribute('data-status');
  const ip = row.getAttribute('data-ip');
  const device = row.getAttribute('data-device');
  const location = row.getAttribute('data-location');
  const details = row.getAttribute('data-details');
  const payloadStr = row.getAttribute('data-payload');

  // Load details
  document.getElementById('modalLogId').innerText = id;
  document.getElementById('modalActorName').innerText = actor;
  document.getElementById('modalActorEmail').innerText = email;
  document.getElementById('modalRoleDept').innerText = `${role} | ${department}`;
  document.getElementById('modalLoginTime').innerText = loginTime;
  document.getElementById('modalLogoutTime').innerText = logoutTime;
  document.getElementById('modalLifespan').innerText = lifespan;
  document.getElementById('modalLogDetails').innerText = details;
  document.getElementById('modalIp').innerText = ip;
  document.getElementById('modalDevice').innerText = device;
  document.getElementById('modalLocation').innerText = location;

  // Code payload
  try {
    const parsedPayload = JSON.parse(payloadStr);
    document.getElementById('modalPayloadText').textContent = JSON.stringify(parsedPayload, null, 2);
  } catch (e) {
    document.getElementById('modalPayloadText').textContent = payloadStr;
  }

  // Setup Status Badge
  const statusBadge = document.getElementById('modalStatusBadge');
  statusBadge.innerText = status;
  let statusBadgeClasses = "inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ";
  if (status === 'Successful Login') {
    statusBadgeClasses += "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20";
  } else if (status === 'Failed Login') {
    statusBadgeClasses += "bg-rose-50 text-rose-700 ring-1 ring-rose-600/20";
  } else if (status === 'Account Locked') {
    statusBadgeClasses += "bg-red-100 text-red-900 ring-1 ring-red-800/30 font-black";
  } else if (status === 'Session Expired') {
    statusBadgeClasses += "bg-amber-50 text-amber-800 ring-1 ring-amber-600/20";
  } else if (status === 'Logout') {
    statusBadgeClasses += "bg-slate-100 text-slate-650 text-slate-650 ring-1 ring-slate-600/10";
  } else {
    statusBadgeClasses += "bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20";
  }
  statusBadge.className = statusBadgeClasses;

  // Setup Status Banner
  const statusBanner = document.getElementById('modalStatusBanner');
  const statusIconCont = document.getElementById('modalStatusIconContainer');
  const statusTitle = document.getElementById('modalStatusTitle');
  const statusMsg = document.getElementById('modalStatusMsg');

  if (status === 'Successful Login') {
    statusBanner.className = "p-4 rounded-xl flex items-center gap-3 bg-emerald-50 border border-emerald-100 text-emerald-800";
    statusIconCont.className = "h-8 w-8 rounded-lg flex items-center justify-center shrink-0 bg-emerald-100 text-emerald-700";
    statusIconCont.innerHTML = `<i class="fa-solid fa-circle-check text-base"></i>`;
    statusTitle.innerText = "Successful Login";
    statusMsg.innerText = "Auth session established successfully. Security keys verified.";
    statusMsg.className = "text-[10px] leading-relaxed font-semibold text-emerald-600 mt-0.5";
  } else if (status === 'Failed Login') {
    statusBanner.className = "p-4 rounded-xl flex items-center gap-3 bg-rose-50 border border-rose-100 text-rose-800";
    statusIconCont.className = "h-8 w-8 rounded-lg flex items-center justify-center shrink-0 bg-rose-100 text-rose-700";
    statusIconCont.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-base"></i>`;
    statusTitle.innerText = "Failed Login Attempt";
    statusMsg.innerText = "Authorization request rejected due to invalid credential verification keys.";
    statusMsg.className = "text-[10px] leading-relaxed font-semibold text-rose-600 mt-0.5";
  } else if (status === 'Account Locked') {
    statusBanner.className = "p-4 rounded-xl flex items-center gap-3 bg-red-950/15 border border-red-200/50 text-red-950";
    statusIconCont.className = "h-8 w-8 rounded-lg flex items-center justify-center shrink-0 bg-red-200 text-red-900";
    statusIconCont.innerHTML = `<i class="fa-solid fa-user-lock text-base"></i>`;
    statusTitle.innerText = "Account Locked";
    statusMsg.innerText = "User account has been locked due to excessive authentication failures.";
    statusMsg.className = "text-[10px] leading-relaxed font-semibold text-red-700 mt-0.5";
  } else if (status === 'Session Expired') {
    statusBanner.className = "p-4 rounded-xl flex items-center gap-3 bg-amber-50 border border-amber-100 text-amber-800";
    statusIconCont.className = "h-8 w-8 rounded-lg flex items-center justify-center shrink-0 bg-amber-100 text-amber-700";
    statusIconCont.innerHTML = `<i class="fa-solid fa-hourglass-end text-base"></i>`;
    statusTitle.innerText = "Session Expired";
    statusMsg.innerText = "Authentication credentials expired due to standard idle inactivity triggers.";
    statusMsg.className = "text-[10px] leading-relaxed font-semibold text-amber-600 mt-0.5";
  } else {
    statusBanner.className = "p-4 rounded-xl flex items-center gap-3 bg-slate-50 border border-slate-200 text-slate-800";
    statusIconCont.className = "h-8 w-8 rounded-lg flex items-center justify-center shrink-0 bg-slate-200 text-slate-600";
    statusIconCont.innerHTML = `<i class="fa-solid fa-circle-xmark text-base"></i>`;
    statusTitle.innerText = status;
    statusMsg.innerText = "Authentication trace event resolved successfully.";
    statusMsg.className = "text-[10px] leading-relaxed font-semibold text-slate-500 mt-0.5";
  }

  // Show Modal
  const modal = document.getElementById('sessionDetailsModal');
  const card = document.getElementById('modalCard');
  
  modal.classList.remove('hidden');
  setTimeout(() => {
    card.classList.remove('scale-95', 'opacity-0');
    card.classList.add('scale-100', 'opacity-100');
  }, 10);
}

function closeSessionModal() {
  const modal = document.getElementById('sessionDetailsModal');
  const card = document.getElementById('modalCard');
  
  card.classList.remove('scale-100', 'opacity-100');
  card.classList.add('scale-95', 'opacity-0');
  setTimeout(() => {
    modal.classList.add('hidden');
  }, 150);
}

// Click outside modal
document.getElementById('sessionDetailsModal').addEventListener('click', function(event) {
  const card = document.getElementById('modalCard');
  if (card && !card.contains(event.target)) {
    closeSessionModal();
  }
});

// Escape key to close modal
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeSessionModal();
  }
});

// Copy Payload
function copyModalPayload() {
  const payloadText = document.getElementById('modalPayloadText').textContent;
  navigator.clipboard.writeText(payloadText).then(() => {
    showToast("Payload Copied", "Session JSON payload details copied to clipboard.");
  }).catch(err => {
    console.error('Copy failed: ', err);
  });
}

// Fetch and render dynamic login history FROM DATABASE
async function fetchLoginHistory() {
  try {
    const res = await fetch('../../api/employee/login-history.php');
    const json = await res.json();
    if (json.status === 'success') {
      if (json.metrics) {
        document.getElementById('successfulCount').innerText = json.metrics.successfulCount || 0;
        document.getElementById('failedCount').innerText = json.metrics.failedCount || 0;
        document.getElementById('activeCount').innerText = json.metrics.activeCount || 0;
        document.getElementById('lockCount').innerText = json.metrics.lockCount || 0;
      }
      if (json.departments) {
        populateDepartments(json.departments);
      }
      if (json.data) {
        renderTableRows(json.data);
      }
    }
  } catch (err) {
    console.error('Error fetching login history:', err);
  }
}

function populateDepartments(departments) {
  const sel = document.getElementById('filterDepartment');
  if (!sel) return;
  const currentVal = sel.value;
  sel.innerHTML = '<option value="All">All Departments</option>';
  departments.forEach(dept => {
    const name = dept.department_name;
    const opt = document.createElement('option');
    opt.value = name;
    opt.textContent = name;
    sel.appendChild(opt);
  });
  sel.value = currentVal || 'All';
}

function formatDateTime(isoStr) {
  if (!isoStr) return '—';
  const d = new Date(isoStr);
  if (isNaN(d.getTime())) return isoStr;
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  });
}

function parseUserAgent(ua) {
  if (!ua) return 'Desktop - Browser';
  let os = 'Desktop';
  if (/mobile/i.test(ua)) os = 'Mobile';
  else if (/mac/i.test(ua)) os = 'Mac';
  else if (/windows/i.test(ua)) os = 'Desktop';

  let browser = 'Browser';
  if (/chrome|crios/i.test(ua) && !/edg/i.test(ua)) browser = 'Chrome';
  else if (/edg/i.test(ua)) browser = 'Edge';
  else if (/safari/i.test(ua) && !/chrome/i.test(ua)) browser = 'Safari';
  else if (/firefox|fxios/i.test(ua)) browser = 'Firefox';

  return `${os} - ${browser}`;
}

function calculateLifespan(loginTimeStr, logoutTimeStr, loginStatus) {
  if (loginStatus === 'Failed') return '0m';
  if (!logoutTimeStr) return 'Active';
  const t1 = new Date(loginTimeStr).getTime();
  const t2 = new Date(logoutTimeStr).getTime();
  if (isNaN(t1) || isNaN(t2) || t2 <= t1) return '0m';
  const diffMs = t2 - t1;
  const mins = Math.floor(diffMs / 60000);
  const hrs = Math.floor(mins / 60);
  const remMins = mins % 60;
  if (hrs > 0) return `${hrs}h ${remMins}m`;
  return `${mins}m`;
}

function renderTableRows(logs) {
  const tbody = document.getElementById('loginTableBody');
  if (!tbody) return;

  const noResults = document.getElementById('noResultsRow');
  tbody.innerHTML = '';
  if (noResults) tbody.appendChild(noResults);

  logs.forEach(log => {
    const user = log.users || {};
    const roleObj = user.roles || {};
    const posObj = user.positions || {};
    const deptObj = posObj.departments || {};

    const actor = user.first_name ? `${user.first_name} ${user.last_name}` : (user.email || 'Unknown User');
    const email = user.email || 'N/A';
    const role = roleObj.role_name || 'User';
    const department = deptObj.department_name || 'Central IT';

    const loginTimeFormatted = formatDateTime(log.login_time);
    const logoutTimeFormatted = formatDateTime(log.logout_time);
    const dateVal = log.login_time ? log.login_time.split('T')[0] : '';

    let statusText = 'Successful Login';
    if (log.login_status === 'Failed') {
      if (user.status === 'Locked' || (log.failure_reason && log.failure_reason.toLowerCase().includes('locked'))) {
        statusText = 'Account Locked';
      } else {
        statusText = 'Failed Login';
      }
    } else if (log.logout_time) {
      statusText = 'Logout';
    }

    const lifespan = calculateLifespan(log.login_time, log.logout_time, log.login_status);
    const ip = log.ip_address || '127.0.0.1';
    const device = parseUserAgent(log.browser);
    const details = log.failure_reason || (log.login_status === 'Success' ? 'Auth session established successfully.' : 'Authentication attempt recorded.');

    const payloadObj = {
      login_id: log.login_id,
      user_id: log.user_id,
      session_id: log.session_id,
      login_time: log.login_time,
      logout_time: log.logout_time,
      ip_address: log.ip_address,
      browser: log.browser,
      login_status: log.login_status,
      failure_reason: log.failure_reason
    };

    const tr = document.createElement('tr');
    tr.onclick = function() { openSessionModal(this); };
    tr.className = 'hover:bg-slate-50/70 transition cursor-pointer';
    tr.setAttribute('data-id', `#LOG-${log.login_id}`);
    tr.setAttribute('data-date', dateVal);
    tr.setAttribute('data-actor', actor);
    tr.setAttribute('data-email', email);
    tr.setAttribute('data-role', role);
    tr.setAttribute('data-department', department);
    tr.setAttribute('data-login-time', loginTimeFormatted);
    tr.setAttribute('data-logout-time', logoutTimeFormatted);
    tr.setAttribute('data-lifespan', lifespan);
    tr.setAttribute('data-status', statusText);
    tr.setAttribute('data-ip', ip);
    tr.setAttribute('data-device', device);
    tr.setAttribute('data-location', 'Caloocan City, PH');
    tr.setAttribute('data-details', details);
    tr.setAttribute('data-payload', JSON.stringify(payloadObj));

    let statusBadgeHtml = '';
    if (statusText === 'Successful Login') {
      statusBadgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">🟢 Success</span>`;
    } else if (statusText === 'Logout') {
      statusBadgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 ring-1 ring-slate-600/10">⚪ Logged Out</span>`;
    } else if (statusText === 'Account Locked') {
      statusBadgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-red-100 text-red-900 ring-1 ring-red-800/30"><i class="fa-solid fa-user-lock text-[9px]"></i> Account Locked</span>`;
    } else if (statusText === 'Failed Login') {
      statusBadgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20">🔴 Failed ${log.failure_reason ? '- ' + log.failure_reason : ''}</span>`;
    } else {
      statusBadgeHtml = `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20">🔑 ${statusText}</span>`;
    }

    let loginTimeSub = '';
    if (log.logout_time) {
      loginTimeSub = `<div class="text-[10px] text-slate-400 font-semibold mt-0.5">Logout: ${logoutTimeFormatted}</div>`;
    } else if (log.login_status === 'Success') {
      loginTimeSub = `<div class="text-[10px] text-emerald-600 font-bold mt-0.5 flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active Session</div>`;
    } else {
      loginTimeSub = `<div class="text-[10px] text-slate-400 font-semibold mt-0.5">—</div>`;
    }

    tr.innerHTML = `
      <td class="py-4 px-5 font-mono text-[11px] font-bold text-slate-500">#LOG-${log.login_id}</td>
      <td class="py-4 px-5">
        <div class="font-bold text-slate-800">${actor}</div>
        <div class="text-[10px] text-slate-400 font-semibold mt-0.5">${role} | ${department}</div>
      </td>
      <td class="py-4 px-5">
        <div class="font-semibold text-slate-800">Login: ${loginTimeFormatted}</div>
        ${loginTimeSub}
      </td>
      <td class="py-4 px-5 text-center font-semibold text-slate-700">${lifespan}</td>
      <td class="py-4 px-5">${statusBadgeHtml}</td>
      <td class="py-4 px-5">
        <div class="font-mono font-bold text-slate-700 text-[11px]">${ip}</div>
        <div class="text-[10px] text-slate-400 font-semibold mt-0.5">${device} / Caloocan City, PH</div>
      </td>
    `;

    tbody.appendChild(tr);
  });

  applyFilters();
}

document.addEventListener('DOMContentLoaded', fetchLoginHistory);
</script>

<?php include '../../includes/footer.php'; ?>
