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
        <span class="text-brand-dark">Survey Results</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-file-circle-check text-brand-dark"></i>
        Survey Results
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Record the findings once a field survey assignment is completed. Recording a result automatically marks the assignment Completed.
      </p>
    </div>

    <?php if (!empty($headerUser['is_superadmin']) || !empty($headerUser['is_global_access']) || (in_array('CREATE', $headerUser['granted_actions'] ?? []))): ?>
    <div class="shrink-0">
      <button onclick="openCreateResultModal()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-plus text-[10px]"></i>
        <span>Record Result</span>
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Total Results</span>
        <h3 id="statTotalResults" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-file-circle-check text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Active</span>
        <h3 id="statActiveResults" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-circle-check text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-slate-400"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Archived</span>
        <h3 id="statArchivedResults" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
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
      <input type="text" id="resultSearchInput" placeholder="Search by form or site..." class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
    </div>
    <div class="flex items-center gap-2">
      <select id="resultConditionFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
        <option value="">All Conditions</option>
        <option value="Excellent">Excellent</option>
        <option value="Good">Good</option>
        <option value="Fair">Fair</option>
        <option value="Poor">Poor</option>
        <option value="Critical">Critical</option>
      </select>
      <select id="resultStatusFilter" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer font-semibold text-slate-700">
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
            <th class="px-6 py-4 w-1/4">Survey Form</th>
            <th class="px-6 py-4">Subject</th>
            <th class="px-6 py-4">Survey Date</th>
            <th class="px-6 py-4">Condition</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="resultsTableBody" class="divide-y divide-slate-100/80 text-xs">
          <!-- Dynamically populated by JS -->
        </tbody>
      </table>
    </div>

    <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex items-center justify-between text-[11px] font-bold text-slate-400">
      <div id="resultPaginationText">Showing 0 to 0 of 0 results</div>
      <div id="resultPaginationControls" class="flex items-center space-x-1"></div>
    </div>
  </div>

</main>

<!-- 1. CREATE / EDIT RESULT MODAL -->
<div id="resultModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i id="resultModalIcon" class="fa-solid fa-file-circle-check text-brand-medium"></i>
        <h3 id="resultModalTitle" class="font-extrabold text-sm tracking-tight uppercase">Record Result</h3>
      </div>
      <button onclick="closeModal('resultModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form id="resultForm" onsubmit="handleSaveResult(event)">
      <input type="hidden" id="resultIdRef">
      <div class="p-6 space-y-5 max-h-[65vh] overflow-y-auto">

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Survey Assignment</h5>
          <select id="resultAssignmentId" required class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
            <option value="">Select an assignment...</option>
          </select>
        </div>

        <div class="border-t border-slate-100"></div>

        <div>
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-2.5">Survey Details</h5>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Survey Date</label>
              <input type="date" id="resultSurveyDate" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Condition Rating</label>
              <select id="resultConditionRating" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
                <option value="">Not rated</option>
                <option value="Excellent">Excellent</option>
                <option value="Good">Good</option>
                <option value="Fair">Fair</option>
                <option value="Poor">Poor</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Population Count</label>
              <input type="number" min="0" step="1" id="resultPopulationCount" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
            </div>
          </div>
          <div class="mt-3 space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Income Bracket</label>
            <input type="text" id="resultIncomeBracket" placeholder="e.g. Below Poverty Line" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          </div>
        </div>

        <div class="border-t border-slate-100"></div>

        <div class="space-y-3">
          <div class="space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Findings</label>
            <textarea id="resultFindings" rows="2" placeholder="What was observed during the survey..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
          </div>
          <div class="space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Recommendations</label>
            <textarea id="resultRecommendations" rows="2" placeholder="Recommended follow-up actions..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
          </div>
          <div class="space-y-1.5">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Additional Notes</label>
            <textarea id="resultAdditionalNotes" rows="2" placeholder="Optional additional notes..." class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition leading-relaxed"></textarea>
          </div>
        </div>
      </div>
      <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end space-x-2">
        <button type="button" onclick="closeModal('resultModal')" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2 rounded-xl text-xs cursor-pointer transition">Cancel</button>
        <button type="submit" id="resultSaveBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">Save Result</button>
      </div>
    </form>
  </div>
</div>

<!-- 2. VIEW RESULT MODAL (details + photo evidence) -->
<div id="viewResultModal" class="fixed inset-0 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-all duration-300 bg-slate-900/60 backdrop-blur-xs">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden transform scale-95 transition-all duration-300">
    <div class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-circle-info text-brand-medium"></i>
        <h3 class="font-extrabold text-sm tracking-tight uppercase">Survey Result Details</h3>
      </div>
      <button onclick="closeModal('viewResultModal')" class="text-slate-400 hover:text-white transition cursor-pointer text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">

      <div class="border-b border-slate-100 pb-3 space-y-1">
        <h4 id="viewResultForm" class="font-extrabold text-sm text-slate-900">&mdash;</h4>
        <p id="viewResultMeta" class="text-xs text-slate-500 leading-relaxed">&mdash;</p>
      </div>

      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Findings</h5>
        <div id="viewResultFindings" class="border border-slate-200/60 rounded-xl px-4 py-3 text-xs text-slate-600 leading-relaxed">No findings on file.</div>
      </div>

      <div class="space-y-2">
        <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Recommendations</h5>
        <div id="viewResultRecommendations" class="border border-slate-200/60 rounded-xl px-4 py-3 text-xs text-slate-600 leading-relaxed">No recommendations on file.</div>
      </div>

      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Photo Evidence</h5>
          <button type="button" onclick="openPhotoUpload()" class="text-[10px] font-bold text-brand-dark hover:underline cursor-pointer">
            <i class="fa-solid fa-upload mr-1"></i>Upload
          </button>
        </div>
        <div id="viewResultPhotos" class="grid grid-cols-3 gap-2">
          <div class="col-span-3 px-4 py-3 text-slate-400 text-xs border border-slate-200/60 rounded-xl">No photos uploaded yet.</div>
        </div>
      </div>

      <form id="photoUploadForm" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2.5" onsubmit="handleUploadPhoto(event)">
        <input type="hidden" id="uploadResultId">
        <input type="text" id="photoCaption" placeholder="Caption (optional)" class="border border-slate-200 rounded-lg px-2.5 py-2 text-[11px] w-full bg-white focus:outline-none focus:border-brand-medium">
        <input type="file" id="photoFile" required accept=".jpg,.jpeg,.png,.webp" class="text-[11px] w-full">
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2 rounded-lg text-[11px] cursor-pointer transition">Save Photo</button>
      </form>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end">
      <button onclick="closeModal('viewResultModal')" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-xs font-bold cursor-pointer transition">Close</button>
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
