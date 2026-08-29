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
        <span>Resident Management</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Resident Directory</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-people-roof text-brand-dark"></i>
        Resident Directory
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Manage resident profiles, household groupings, and supporting document attachments for Caloocan City.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreateResidentModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Add Resident</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Residents</span>
        <h3 id="statTotalResidents" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-users text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Active</span>
        <h3 id="statActiveResidents" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-circle-check text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-400"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Archived</span>
        <h3 id="statArchivedResidents" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-slate-100 group-hover:text-slate-700 transition duration-350">
        <i class="fa-solid fa-box-archive text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-cyan-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Households Covered</span>
        <h3 id="statHouseholdsCovered" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-cyan-50 group-hover:text-cyan-700 transition duration-350">
        <i class="fa-solid fa-house-chimney text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Search and Filters Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="residentSearchInput" placeholder="Search by name, contact number, or email..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2">
      <input type="text" id="residentBarangayFilter" placeholder="Filter by barangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium transition w-40">
      <select id="residentStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
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
            <th class="px-6 py-4 w-1/3">Resident</th>
            <th class="px-6 py-4">Household / Barangay</th>
            <th class="px-6 py-4">Contact</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="residentsTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="residentsPaginationText">Showing 0 to 0 of 0 residents</div>
      <div id="residentsPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT RESIDENT MODAL -->
<div id="residentModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="residentModalIcon" class="fa-solid fa-user-plus text-brand-medium"></i>
        <h3 id="residentModalTitle" class="font-extrabold text-sm tracking-tight uppercase">Add Resident</h3>
      </div>
      <button onclick="closeModal('residentModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="residentForm" onsubmit="handleSaveResident(event)">
      <input type="hidden" id="residentIdRef">
      <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Personal Information</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-1 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">First Name</label>
              <input type="text" id="residentFirstName" required class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="sm:col-span-1 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Middle Name</label>
              <input type="text" id="residentMiddleName" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="sm:col-span-1 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Last Name</label>
              <input type="text" id="residentLastName" required class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Suffix</label>
              <input type="text" id="residentSuffix" placeholder="Jr., Sr., III" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Birth Date</label>
              <input type="date" id="residentBirthDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Gender</label>
              <select id="residentGender" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="">Select</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Civil Status</label>
              <select id="residentCivilStatus" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="">Select</option>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Widowed">Widowed</option>
                <option value="Separated">Separated</option>
                <option value="Divorced">Divorced</option>
              </select>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Contact Information</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Contact Number</label>
              <input type="tel" id="residentContactNumber" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Email</label>
              <input type="email" id="residentEmail" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Occupation</label>
              <input type="text" id="residentOccupation" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Address & Household</h5>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Barangay</label>
              <input type="text" id="residentBarangay" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Street Address</label>
              <input type="text" id="residentStreetAddress" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>

          <div class="mt-3 space-y-1.5 relative">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Household (optional — for family grouping)</label>
            <input type="hidden" id="residentHouseholdId">
            <input type="text" id="residentHouseholdSearch" placeholder="Search existing household by barangay, address, or HH number..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            <div id="householdSearchResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
            <div id="selectedHouseholdBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
              <span id="selectedHouseholdLabel" class="text-[11px] font-bold text-brand-dark"></span>
              <button type="button" onclick="clearSelectedHousehold()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
            </div>
          </div>

          <div id="relationshipToHeadWrapper" class="mt-3 space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Relationship to Household Head</label>
            <select id="residentRelationship" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
              <option value="">Select</option>
              <option value="Head">Head</option>
              <option value="Spouse">Spouse</option>
              <option value="Son">Son</option>
              <option value="Daughter">Daughter</option>
              <option value="Other">Other Relative</option>
            </select>
          </div>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('residentModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" id="residentSaveBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">Save Resident</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. VIEW RESIDENT PROFILE MODAL -->
<div id="viewResidentModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-circle-info text-brand-medium"></i>
        <h3 class="font-extrabold text-sm tracking-tight uppercase">Resident Profile</h3>
      </div>
      <button onclick="closeModal('viewResidentModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">

      <div class="border-b border-slate-100 pb-3 space-y-1">
        <h4 id="viewResidentName" class="font-extrabold text-sm text-slate-900">&mdash;</h4>
        <p id="viewResidentMeta" class="text-xs text-slate-500 leading-relaxed">&mdash;</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Contact</span>
          <p id="viewResidentContact" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
        <div class="bg-slate-50 border border-slate-200/40 rounded-xl p-3.5 space-y-1">
          <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block">Household / Barangay</span>
          <p id="viewResidentHousehold" class="text-sm font-extrabold text-slate-800">&mdash;</p>
        </div>
      </div>

      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Household Members</h5>
        <div id="viewHouseholdMembers" class="border border-slate-200/60 rounded-xl overflow-hidden text-xs divide-y divide-slate-100">
          <div class="px-4 py-3 text-slate-400">Not part of a household.</div>
        </div>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Document Attachments</h5>
          <button type="button" onclick="openDocumentUpload()" class="text-[10px] font-bold text-brand-dark hover:underline cursor-pointer">
            <i class="fa-solid fa-upload mr-1"></i>Upload
          </button>
        </div>
        <div id="viewResidentDocuments" class="border border-slate-200/60 rounded-xl overflow-hidden text-xs divide-y divide-slate-100">
          <div class="px-4 py-3 text-slate-400">No documents uploaded yet.</div>
        </div>
      </div>

      <form id="documentUploadForm" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2.5" onsubmit="handleUploadDocument(event)">
        <input type="hidden" id="uploadResidentId">
        <div class="grid grid-cols-2 gap-2.5">
          <select id="documentType" required class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full bg-white focus:outline-none focus:border-brand-medium">
            <option value="">Document Type</option>
            <option value="Birth Certificate">Birth Certificate</option>
            <option value="Valid ID">Valid ID</option>
            <option value="Proof of Residency">Proof of Residency</option>
            <option value="Other">Other</option>
          </select>
          <input type="file" id="documentFile" required accept=".pdf,.jpg,.jpeg,.png" class="text-[11px] w-full">
        </div>
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Save Document</button>
      </form>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end">
      <button onclick="closeModal('viewResidentModal')" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold cursor-pointer transition">Close</button>
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
