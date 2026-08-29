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
        <span class="text-brand-dark">Subdivision &amp; Building Review</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-building-shield text-brand-dark"></i>
        Subdivision &amp; Building Review
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Intake subdivision plat and building permit applications, route plan sets through Architectural, Structural, Sanitary, Electrical, and Fire Safety review, and issue permits with conditions of approval.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreatePermitModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>New Application</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Applications</span>
        <h3 id="statTotalPa" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-file-lines text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Pending Review</span>
        <h3 id="statPendingPa" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-amber-50 group-hover:text-amber-700 transition duration-350">
        <i class="fa-solid fa-hourglass-half text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Permits Issued</span>
        <h3 id="statIssuedPa" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-circle-check text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-rose-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Denied</span>
        <h3 id="statDeniedPa" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-rose-50 group-hover:text-rose-700 transition duration-350">
        <i class="fa-solid fa-circle-xmark text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Search and Filters Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="paSearchInput" placeholder="Search by reference number, project, or applicant..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <select id="paTypeFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Types</option>
        <option value="Subdivision Plan">Subdivision Plan</option>
        <option value="Building Permit">Building Permit</option>
      </select>
      <select id="paStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Stages</option>
        <option value="Submitted">Submitted</option>
        <option value="Under Review">Under Review</option>
        <option value="Returned for Revision">Returned for Revision</option>
        <option value="Approved">Approved</option>
        <option value="Permit Issued">Permit Issued</option>
        <option value="Denied">Denied</option>
        <option value="Cancelled">Cancelled</option>
      </select>
      <select id="paRecordStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
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
            <th class="px-6 py-4 w-1/5">Reference / Applicant</th>
            <th class="px-6 py-4">Type / Project</th>
            <th class="px-6 py-4">Consolidated Result</th>
            <th class="px-6 py-4">Fee / Payment</th>
            <th class="px-6 py-4">Stage</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="paTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="paPaginationText">Showing 0 to 0 of 0 applications</div>
      <div id="paPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT APPLICATION MODAL -->
<div id="paModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="paModalIcon" class="fa-solid fa-building-shield text-brand-medium"></i>
        <h3 id="paModalTitle" class="font-extrabold text-sm tracking-tight uppercase">New Permit Application</h3>
      </div>
      <button onclick="closeModal('paModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="paForm" onsubmit="handleSaveApplication(event)">
      <input type="hidden" id="paIdRef">
      <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Applicant</h5>
          <div class="space-y-1.5 relative">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Select Resident</label>
            <input type="hidden" id="paResidentId">
            <input type="text" id="paResidentSearch" placeholder="Search resident by name, contact, or email..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            <div id="paResidentResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
            <div id="paSelectedResidentBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
              <span id="paSelectedResidentLabel" class="text-[11px] font-bold text-brand-dark"></span>
              <button type="button" onclick="clearSelectedPaResident()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Application Type &amp; Project</h5>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Application Type</label>
              <select id="paApplicationType" onchange="togglePaTypeFields()" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Building Permit">Building Permit</option>
                <option value="Subdivision Plan">Subdivision Plan</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Project Name</label>
              <input type="text" id="paProjectName" required class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Barangay</label>
              <input type="text" id="paBarangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Street Address</label>
              <input type="text" id="paStreetAddress" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="mt-3 space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Project Description</label>
            <textarea id="paProjectDescription" rows="2" placeholder="Brief description of the proposed project..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Project Figures</h5>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5" id="paLotAreaField">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Lot Area (sqm)</label>
              <input type="number" step="0.01" min="0" id="paLotArea" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5" id="paFloorAreaField">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Floor Area (sqm)</label>
              <input type="number" step="0.01" min="0" id="paFloorArea" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5" id="paStoreysField">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">No. of Storeys</label>
              <input type="number" step="1" min="0" id="paStoreys" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5 hidden" id="paLotsField">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">No. of Lots</label>
              <input type="number" step="1" min="0" id="paLots" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Est. Project Cost</label>
              <input type="number" step="0.01" min="0" id="paProjectCost" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('paModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" id="paSaveBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">Save Application</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. VIEW APPLICATION MODAL -->
<div id="viewPaModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-circle-info text-brand-medium"></i>
        <h3 class="font-extrabold text-sm tracking-tight uppercase">Application Details</h3>
      </div>
      <button onclick="closeModal('viewPaModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
      <div class="border-b border-slate-100 pb-3 space-y-1">
        <h4 id="viewPaReference" class="font-extrabold text-sm text-slate-900">&mdash;</h4>
        <p id="viewPaMeta" class="text-xs text-slate-500 leading-relaxed">&mdash;</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Consolidated Result</span>
          <p id="viewPaConsolidated" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Fee / Payment</span>
          <p id="viewPaFee" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
      </div>

      <div id="viewPaCertificateLink" class="hidden">
        <a id="viewPaCertificateHref" href="#" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-brand-dark hover:underline">
          <i class="fa-solid fa-file-shield"></i> View Printable Permit
        </a>
      </div>

      <!-- Discipline Review Grid -->
      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Multi-Discipline Review</h5>
        <div id="viewPaDisciplines" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5"></div>
      </div>

      <!-- Plan Documents -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Plan Documents</h5>
          <button type="button" onclick="openPaDocumentUpload()" class="text-[10px] font-bold text-brand-dark hover:underline cursor-pointer"><i class="fa-solid fa-upload"></i> Upload</button>
        </div>
        <div id="viewPaDocuments" class="border border-slate-200/60 rounded-xl overflow-hidden text-xs divide-y divide-slate-100">
          <div class="px-4 py-3 text-slate-400">No plan documents uploaded yet.</div>
        </div>
        <form id="paDocumentUploadForm" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2.5" onsubmit="handleUploadPaDocument(event)">
          <input type="hidden" id="uploadPaApplicationId">
          <div class="grid grid-cols-2 gap-2.5">
            <select id="paDocumentType" class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full bg-white focus:outline-none focus:border-brand-medium">
              <option value="Site Development Plan">Site Development Plan</option>
              <option value="Building Plan">Building Plan</option>
              <option value="Structural Plan">Structural Plan</option>
              <option value="Sanitary/Plumbing Plan">Sanitary/Plumbing Plan</option>
              <option value="Electrical Plan">Electrical Plan</option>
              <option value="Fire Safety Plan">Fire Safety Plan</option>
              <option value="Subdivision Plat">Subdivision Plat</option>
              <option value="Other">Other</option>
            </select>
            <input type="file" id="paDocumentFile" accept=".pdf,.jpg,.jpeg,.png,.dwg" class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full bg-white focus:outline-none focus:border-brand-medium">
          </div>
          <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Upload Document</button>
        </form>
      </div>

      <!-- Review Timeline -->
      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Review &amp; Resubmission Timeline</h5>
        <div id="viewPaTimeline" class="border border-slate-200/60 rounded-xl overflow-hidden text-xs divide-y divide-slate-100">
          <div class="px-4 py-3 text-slate-400">No review activity yet.</div>
        </div>
      </div>

      <!-- Resubmit -->
      <form id="paResubmitForm" class="hidden bg-amber-50 border border-amber-150 rounded-xl p-3.5 space-y-2.5" onsubmit="handleResubmitApplication(event)">
        <input type="hidden" id="paResubmitId">
        <p class="text-[11px] text-amber-700 font-semibold">This application was returned for revision. Describe what changed, then resubmit.</p>
        <textarea id="paResubmitRemarks" required rows="2" placeholder="What changed in this resubmission..." class="border border-amber-200 rounded-lg px-2.5 py-2 text-[11px] w-full focus:outline-none focus:border-amber-400 resize-none"></textarea>
        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Resubmit Application</button>
      </form>

      <!-- Issue Permit -->
      <form id="paIssuePermitForm" class="hidden bg-emerald-50 border border-emerald-150 rounded-xl p-3.5 space-y-2.5" onsubmit="handleIssuePermit(event)">
        <input type="hidden" id="paIssueId">
        <p class="text-[11px] text-emerald-700 font-semibold">All discipline reviews are Approved. Record conditions of approval and issue the permit.</p>
        <textarea id="paConditionsOfApproval" rows="2" placeholder="Conditions of approval (optional)..." class="border border-emerald-200 rounded-lg px-2.5 py-2 text-[11px] w-full focus:outline-none focus:border-emerald-400 resize-none"></textarea>
        <input type="date" id="paExpiryDate" class="border border-emerald-200 rounded-lg px-2.5 py-2 text-[11px] w-full focus:outline-none focus:border-emerald-400">
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Issue Permit</button>
      </form>

      <!-- Top-level Status Transition -->
      <form id="paTransitionForm" class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2.5" onsubmit="handleTransitionApplication(event)">
        <input type="hidden" id="paTransitionId">
        <div class="grid grid-cols-2 gap-2.5">
          <select id="paTransitionStatus" required class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full bg-white focus:outline-none focus:border-brand-medium">
            <option value="">Move To Stage...</option>
            <option value="Under Review">Under Review</option>
            <option value="Returned for Revision">Returned for Revision</option>
            <option value="Approved">Approved</option>
            <option value="Denied">Denied</option>
            <option value="Cancelled">Cancelled</option>
          </select>
          <input type="text" id="paTransitionRole" placeholder="Your role (e.g. Building Official)" class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full focus:outline-none focus:border-brand-medium">
        </div>
        <textarea id="paTransitionRemarks" required rows="2" placeholder="Remarks for this decision (required)..." class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full focus:outline-none focus:border-brand-medium resize-none"></textarea>
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Record Decision</button>
      </form>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end">
      <button onclick="closeModal('viewPaModal')" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold cursor-pointer transition">Close</button>
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
