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
        <span class="text-brand-dark">Survey History</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-timeline text-brand-dark"></i>
        Survey History
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Look up every survey assignment and recorded result for a resident, household, or site over time.
      </p>
    </div>
  </div>

  <!-- Subject Picker -->
  <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs space-y-3">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div class="space-y-1.5">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Subject Type</label>
        <select id="historySubjectType" onchange="toggleHistorySubjectFields()" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full bg-white focus:outline-none focus:border-brand-medium transition cursor-pointer">
          <option value="Resident">Resident</option>
          <option value="Household">Household</option>
          <option value="Site">Site</option>
        </select>
      </div>

      <div id="historyResidentFields" class="sm:col-span-2 space-y-1.5 relative">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Select Resident</label>
        <input type="hidden" id="historySubjectId">
        <input type="text" id="historyResidentSearch" placeholder="Search resident by name, contact, or email..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
        <div id="historyResidentResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
        <div id="historySelectedResidentBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
          <span id="historySelectedResidentLabel" class="text-[11px] font-bold text-brand-dark"></span>
          <button type="button" onclick="clearSelectedHistoryResident()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </div>

      <div id="historyHouseholdFields" class="hidden sm:col-span-2 space-y-1.5 relative">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Select Household</label>
        <input type="text" id="historyHouseholdSearch" placeholder="Search household by number or address..." autocomplete="off" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
        <div id="historyHouseholdResults" class="hidden absolute z-10 top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto"></div>
        <div id="historySelectedHouseholdBadge" class="hidden items-center justify-between bg-brand-light border border-brand-border/60 rounded-lg px-3 py-2 mt-1.5">
          <span id="historySelectedHouseholdLabel" class="text-[11px] font-bold text-brand-dark"></span>
          <button type="button" onclick="clearSelectedHistoryHousehold()" class="text-brand-dark hover:text-red-500 text-xs cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </div>

      <div id="historySiteFields" class="hidden sm:col-span-2 space-y-1.5">
        <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">Site Label</label>
        <div class="flex gap-2">
          <input type="text" id="historySiteLabel" placeholder="Exact site label, e.g. Purok 3 Drainage Line" class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs w-full focus:outline-none focus:border-brand-medium transition">
          <button type="button" onclick="loadHistoryForSite()" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs shrink-0 cursor-pointer transition">Look Up</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Timeline -->
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
      <h5 class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Timeline</h5>
    </div>
    <div id="historyTimeline" class="text-xs divide-y divide-slate-100">
      <div class="px-6 py-8 text-center text-slate-400">Select a resident, household, or site above to view its survey history.</div>
    </div>
  </div>

</main>

<!-- TOAST -->
<div id="toast" class="fixed bottom-4 right-4 z-50 bg-slate-900 text-white text-xs font-bold px-4 py-3.5 rounded-xl shadow-lg flex items-center gap-3 transform translate-y-4 opacity-0 pointer-events-none transition-all duration-300">
  <div class="h-5 w-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px]">
    <i class="fa-solid fa-check"></i>
  </div>
  <span id="toastMsg" class="tracking-wide">Action executed successfully.</span>
</div>

<?php include '../../includes/footer.php'; ?>
