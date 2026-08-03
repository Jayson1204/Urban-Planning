<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto">

  <!-- BREADCRUMB -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Urban Planning</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Overview</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-map-location-dot text-brand-dark"></i>
        Urban Planning
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Development plans, urban projects, infrastructure records, and planning documents for Caloocan City.
      </p>
    </div>
    <div class="shrink-0 flex items-center gap-2 bg-amber-50 border border-amber-200/80 text-amber-700 text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-xl">
      <i class="fa-solid fa-screwdriver-wrench text-amber-500"></i>
      <span>Module In Development</span>
    </div>
  </div>

  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
    <div class="bg-slate-50 border-b border-slate-200/60 px-6 py-4 flex items-center gap-2.5">
      <div class="h-7 w-7 rounded-lg bg-brand-light border border-brand-border flex items-center justify-center">
        <i class="fa-solid fa-clipboard-list text-brand-dark text-xs"></i>
      </div>
      <div>
        <span class="text-xs font-black text-slate-700 block leading-none tracking-tight">Coming Up in This Module</span>
        <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider">Built incrementally, one feature at a time</span>
      </div>
    </div>

    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="border border-slate-200/80 rounded-xl p-4 flex items-start gap-3">
        <div class="h-9 w-9 rounded-lg bg-cyan-50 border border-cyan-100 flex items-center justify-center text-cyan-700 shrink-0">
          <i class="fa-solid fa-file-signature text-sm"></i>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-800">Development Plans</p>
          <p class="text-[10px] text-slate-500 leading-relaxed mt-0.5">Zoning and land-use plans on file for the city.</p>
        </div>
      </div>
      <div class="border border-slate-200/80 rounded-xl p-4 flex items-start gap-3">
        <div class="h-9 w-9 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 shrink-0">
          <i class="fa-solid fa-diagram-project text-sm"></i>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-800">Urban Projects</p>
          <p class="text-[10px] text-slate-500 leading-relaxed mt-0.5">Active and proposed infrastructure/development projects.</p>
        </div>
      </div>
      <div class="border border-slate-200/80 rounded-xl p-4 flex items-start gap-3">
        <div class="h-9 w-9 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-700 shrink-0">
          <i class="fa-solid fa-timeline text-sm"></i>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-800">Project Status Tracking</p>
          <p class="text-[10px] text-slate-500 leading-relaxed mt-0.5">Milestones and progress for ongoing projects.</p>
        </div>
      </div>
      <div class="border border-slate-200/80 rounded-xl p-4 flex items-start gap-3">
        <div class="h-9 w-9 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-700 shrink-0">
          <i class="fa-solid fa-road text-sm"></i>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-800">Infrastructure Records</p>
          <p class="text-[10px] text-slate-500 leading-relaxed mt-0.5">Roads, utilities, and public facility records.</p>
        </div>
      </div>
      <div class="border border-slate-200/80 rounded-xl p-4 flex items-start gap-3 sm:col-span-2">
        <div class="h-9 w-9 rounded-lg bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-700 shrink-0">
          <i class="fa-solid fa-folder-open text-sm"></i>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-800">Planning Documents</p>
          <p class="text-[10px] text-slate-500 leading-relaxed mt-0.5">Ordinances, permits, and supporting documentation for planning cases.</p>
        </div>
      </div>
    </div>
  </div>

</main>

<?php include '../../includes/footer.php'; ?>
