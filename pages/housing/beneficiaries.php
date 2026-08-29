<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$benGrantedActions = $headerUser['granted_actions'] ?? [];
$benIsPrivileged = !empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']);
$canReviewBenDocuments = $benIsPrivileged || in_array('APPROVE', $benGrantedActions) || in_array('REJECT', $benGrantedActions);
?>
<script>
  window.benCanReviewDocuments = <?php echo json_encode($canReviewBenDocuments); ?>;
</script>

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto">

  <!-- Breadcrumb & Page Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Housing Management</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Beneficiaries</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-hand-holding-heart text-brand-dark"></i>
        Housing Beneficiaries
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Manage applicants and awardees for socialized-housing units, linked to registered residents. Awarding a beneficiary updates the unit's occupancy.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreateBeneficiaryModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Add Beneficiary</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Beneficiaries</span>
        <h3 id="statTotalBen" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-users text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Applicants</span>
        <h3 id="statApplicantsBen" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-indigo-50 group-hover:text-indigo-700 transition duration-350">
        <i class="fa-solid fa-file-signature text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Awarded</span>
        <h3 id="statAwardedBen" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-award text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-rose-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Disqualified</span>
        <h3 id="statDisqualifiedBen" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-rose-50 group-hover:text-rose-700 transition duration-350">
        <i class="fa-solid fa-user-xmark text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Search and Filters Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="benSearchInput" placeholder="Search by resident name or unit code..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <select id="benStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Stages</option>
        <option value="Applicant">Applicant</option>
        <option value="Qualified">Qualified</option>
        <option value="Awarded">Awarded</option>
        <option value="Disqualified">Disqualified</option>
        <option value="Cancelled">Cancelled</option>
      </select>
      <select id="benCategoryFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Categories</option>
        <option value="Informal Settler">Informal Settler</option>
        <option value="Calamity Victim">Calamity Victim</option>
        <option value="Senior Citizen">Senior Citizen</option>
        <option value="PWD">PWD</option>
        <option value="Government Employee">Government Employee</option>
        <option value="Other">Other</option>
      </select>
      <select id="benRecordStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Records</option>
        <option value="Active">Active</option>
        <option value="Archived">Archived</option>
      </select>
      <select id="benSortFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">Newest First</option>
        <option value="priority">Priority (Waitlist)</option>
      </select>
    </div>
  </div>

  <!-- Datatable -->
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-wider">
            <th class="px-6 py-4 w-1/4">Beneficiary</th>
            <th class="px-6 py-4">Unit</th>
            <th class="px-6 py-4">Category</th>
            <th class="px-6 py-4">Eligibility Score</th>
            <th class="px-6 py-4">Stage</th>
            <th class="px-6 py-4">Award Date</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="beneficiariesTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="benPaginationText">Showing 0 to 0 of 0 beneficiaries</div>
      <div id="benPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT BENEFICIARY MODAL -->
<div id="beneficiaryModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="beneficiaryModalIcon" class="fa-solid fa-hand-holding-heart text-brand-medium"></i>
        <h3 id="beneficiaryModalTitle" class="font-extrabold text-sm tracking-tight uppercase">Add Beneficiary</h3>
      </div>
      <button onclick="closeModal('beneficiaryModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="beneficiaryForm" onsubmit="handleSaveBeneficiary(event)">
      <input type="hidden" id="beneficiaryIdRef">
      <input type="hidden" id="benHouseholdId">
      <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Resident</h5>
          <div class="space-y-1.5 relative">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Select Resident</label>
            <input type="hidden" id="benResidentId">
            <input type="text" id="benResidentSearch" placeholder="Search resident by name, contact, or email..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            <div id="benResidentResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
            <div id="benSelectedResidentBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
              <span id="benSelectedResidentLabel" class="text-[11px] font-bold text-brand-dark"></span>
              <button type="button" onclick="clearSelectedResident()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Application</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Stage</label>
              <select id="benStatus" onchange="toggleAwardFields()" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Applicant">Applicant</option>
                <option value="Qualified">Qualified</option>
                <option value="Awarded">Awarded</option>
                <option value="Disqualified">Disqualified</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Category</label>
              <select id="benCategory" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Informal Settler">Informal Settler</option>
                <option value="Calamity Victim">Calamity Victim</option>
                <option value="Senior Citizen">Senior Citizen</option>
                <option value="PWD">PWD</option>
                <option value="Government Employee">Government Employee</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Application Date</label>
              <input type="date" id="benApplicationDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Monthly Income (₱)</label>
              <input type="number" step="0.01" min="0" id="benMonthlyIncome" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Family Size</label>
              <input type="number" min="0" step="1" id="benFamilySize" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div id="awardFieldsWrapper">
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Unit Award <span class="text-slate-300 normal-case font-medium">(required when awarding)</span></h5>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5 relative">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Housing Unit</label>
              <input type="hidden" id="benUnitId">
              <input type="text" id="benUnitSearch" placeholder="Search unit by code or project..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
              <div id="benUnitResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
              <div id="benSelectedUnitBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
                <span id="benSelectedUnitLabel" class="text-[11px] font-bold text-brand-dark"></span>
                <button type="button" onclick="clearSelectedUnit()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
              </div>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Award Date</label>
              <input type="date" id="benAwardDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Amortization Status</label>
              <select id="benAmortizationStatus" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Not Started">Not Started</option>
                <option value="Current">Current</option>
                <option value="Delinquent">Delinquent</option>
                <option value="Completed">Completed</option>
              </select>
            </div>
          </div>
        </div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Remarks</h5>
          <textarea id="benRemarks" rows="2" placeholder="Optional notes..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('beneficiaryModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" id="beneficiarySaveBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">Save Beneficiary</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. VIEW BENEFICIARY MODAL -->
<div id="viewBeneficiaryModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-circle-info text-brand-medium"></i>
        <h3 class="font-extrabold text-sm tracking-tight uppercase">Beneficiary Details</h3>
      </div>
      <button onclick="closeModal('viewBeneficiaryModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
      <div class="border-b border-slate-100 pb-3 space-y-1">
        <h4 id="viewBenName" class="font-extrabold text-sm text-slate-900">&mdash;</h4>
        <p id="viewBenMeta" class="text-xs text-slate-500 leading-relaxed">&mdash;</p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Awarded Unit</span>
          <p id="viewBenUnit" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Award Date</span>
          <p id="viewBenAwardDate" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Monthly Income</span>
          <p id="viewBenIncome" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Family Size</span>
          <p id="viewBenFamilySize" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Eligibility Score</span>
          <p id="viewBenScore" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Amortization Status</span>
          <p id="viewBenAmortization" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
      </div>
      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Remarks</h5>
        <div id="viewBenRemarks" class="border border-slate-200/60 rounded-xl px-4 py-3 text-xs text-slate-600 leading-relaxed">No remarks on file.</div>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Application Documents</h5>
          <button type="button" onclick="openBenDocumentUpload()" class="text-[10px] font-bold text-brand-dark hover:underline cursor-pointer">
            <i class="fa-solid fa-upload mr-1"></i>Upload
          </button>
        </div>
        <p class="text-[9px] text-slate-400 leading-relaxed -mt-1">Citizens will submit these directly once the mobile app is available. Until then, staff can upload on the applicant's behalf.</p>
        <div id="viewBenDocuments" class="border border-slate-200/60 rounded-xl overflow-hidden text-xs divide-y divide-slate-100">
          <div class="px-4 py-3 text-slate-400">No documents submitted yet.</div>
        </div>
      </div>

      <form id="benDocumentUploadForm" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2.5" onsubmit="handleUploadBenDocument(event)">
        <input type="hidden" id="uploadBenBeneficiaryId">
        <div class="grid grid-cols-2 gap-2.5">
          <select id="benDocumentType" required class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full bg-white focus:outline-none focus:border-brand-medium">
            <option value="">Document Type</option>
            <option value="Valid ID">Valid ID</option>
            <option value="Proof of Income">Proof of Income</option>
            <option value="Barangay Certificate">Barangay Certificate</option>
            <option value="Certificate of Indigency">Certificate of Indigency</option>
            <option value="Proof of Residency">Proof of Residency</option>
            <option value="Other">Other</option>
          </select>
          <input type="file" id="benDocumentFile" required accept=".pdf,.jpg,.jpeg,.png" class="text-[11px] w-full">
        </div>
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Save Document</button>
      </form>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end">
      <button onclick="closeModal('viewBeneficiaryModal')" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold cursor-pointer transition">Close</button>
    </div>
  </div>
</div>

<!-- 3. REJECT DOCUMENT MODAL -->
<div id="rejectBenDocumentModal" class="fixed inset-0 z-[60] flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-file-circle-xmark text-rose-400"></i>
        <h3 class="font-extrabold text-sm tracking-tight uppercase">Reject Document</h3>
      </div>
      <button onclick="closeModal('rejectBenDocumentModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="rejectBenDocumentForm" onsubmit="handleConfirmRejectBenDocument(event)">
      <input type="hidden" id="rejectBenDocumentId">
      <div class="p-6 space-y-2">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Reason for rejection</label>
        <textarea id="rejectBenDocumentReason" required rows="3" placeholder="Explain what's wrong with this document so the applicant can resubmit..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition resize-none"></textarea>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('rejectBenDocumentModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs">Confirm Rejection</button>
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
