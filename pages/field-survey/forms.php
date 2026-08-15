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
        <span>Field Survey</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Survey Forms</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-clipboard-list text-brand-dark"></i>
        Survey Forms
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Define the survey types field staff can be assigned, such as household needs assessments or infrastructure condition checks.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreateSurveyFormModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Add Survey Form</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Forms</span>
        <h3 id="statTotalForms" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-clipboard-list text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Active</span>
        <h3 id="statActiveForms" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-circle-check text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-400"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Archived</span>
        <h3 id="statArchivedForms" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-slate-100 group-hover:text-slate-700 transition duration-350">
        <i class="fa-solid fa-box-archive text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Search and Filters Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
    <div class="relative flex-1 max-w-md">
      <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
      <input type="text" id="surveyFormSearchInput" placeholder="Search by form code or title..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2">
      <select id="surveyFormCategoryFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Categories</option>
        <option value="Household Assessment">Household Assessment</option>
        <option value="Infrastructure Condition">Infrastructure Condition</option>
        <option value="Land Use Assessment">Land Use Assessment</option>
        <option value="Socioeconomic Survey">Socioeconomic Survey</option>
        <option value="Other">Other</option>
      </select>
      <select id="surveyFormSubjectFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Subjects</option>
        <option value="Resident">Resident</option>
        <option value="Household">Household</option>
        <option value="Site">Site</option>
      </select>
      <select id="surveyFormStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
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
            <th class="px-6 py-4 w-1/4">Form</th>
            <th class="px-6 py-4">Category</th>
            <th class="px-6 py-4">Subject</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="surveyFormsTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="surveyFormPaginationText">Showing 0 to 0 of 0 forms</div>
      <div id="surveyFormPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT SURVEY FORM MODAL -->
<div id="surveyFormModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="surveyFormModalIcon" class="fa-solid fa-clipboard-list text-brand-medium"></i>
        <h3 id="surveyFormModalTitle" class="font-extrabold text-sm tracking-tight uppercase">Add Survey Form</h3>
      </div>
      <button onclick="closeModal('surveyFormModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="surveyFormForm" onsubmit="handleSaveSurveyForm(event)">
      <input type="hidden" id="surveyFormIdRef">
      <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Form Identification</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-1 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Form Code</label>
              <input type="text" id="surveyFormCode" required placeholder="FS-0001" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="sm:col-span-2 space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Form Title</label>
              <input type="text" id="surveyFormTitle" required placeholder="e.g. 2026 Household Needs Assessment" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Category</label>
              <select id="surveyFormCategory" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Household Assessment">Household Assessment</option>
                <option value="Infrastructure Condition">Infrastructure Condition</option>
                <option value="Land Use Assessment">Land Use Assessment</option>
                <option value="Socioeconomic Survey">Socioeconomic Survey</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Subject Type</label>
              <select id="surveyFormSubjectType" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="Resident">Resident</option>
                <option value="Household">Household</option>
                <option value="Site">Site</option>
              </select>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Description</h5>
          <textarea id="surveyFormDescription" rows="3" placeholder="What this survey form is used for..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('surveyFormModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs">Save Form</button>
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
