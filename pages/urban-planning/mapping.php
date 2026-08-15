<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<!-- Leaflet is only needed on this page, so it is loaded here rather than in header.php. -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto">

  <!-- Breadcrumb & Page Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Urban Planning</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Barangay Map</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-map-location-dot text-brand-dark"></i>
        Caloocan Barangay Map
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Interactive boundary map of all 188 Caloocan barangays with per-barangay housing statistics and housing project markers.
      </p>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-brand-dark"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Barangays</span>
        <h3 id="statBarangayCount" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-brand-light group-hover:text-brand-dark transition duration-350">
        <i class="fa-solid fa-draw-polygon text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Mapped Housing Units</span>
        <h3 id="statMappedUnits" class="text-2xl font-black text-slate-900 tracking-tight">0</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-emerald-50 group-hover:text-emerald-700 transition duration-350">
        <i class="fa-solid fa-house text-sm"></i>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs flex items-center justify-between group relative overflow-hidden">
      <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500"></div>
      <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Selected Barangay</span>
        <h3 id="statSelectedBarangay" class="text-2xl font-black text-slate-900 tracking-tight">—</h3>
      </div>
      <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-450 group-hover:bg-amber-50 group-hover:text-amber-700 transition duration-350">
        <i class="fa-solid fa-location-dot text-sm"></i>
      </div>
    </div>
  </div>

  <!-- Map + Info Panel -->
  <div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-5 items-start">

    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs overflow-hidden">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 border-b border-slate-100">
        <div class="relative flex-1 max-w-sm">
          <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
          <input type="text" id="mapBarangaySearch" placeholder="Search barangay (e.g. Barangay 176)..." autocomplete="off" class="pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-xs w-full bg-slate-50/50 focus:bg-white focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
          <div id="mapSearchResults" class="hidden absolute z-[1000] top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-64 overflow-y-auto"></div>
        </div>
        <div class="flex items-center gap-4 text-[11px] font-semibold text-slate-600">
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" id="layerBoundaries" checked class="rounded border-slate-300 text-brand-medium focus:ring-brand-medium/30">
            Barangay Boundaries
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input type="checkbox" id="layerHousingProjects" checked class="rounded border-slate-300 text-brand-medium focus:ring-brand-medium/30">
            Housing Projects
          </label>
        </div>
      </div>
      <div id="barangayMap" class="w-full" style="height: 600px;">
        <div class="skeleton-loader w-full h-full flex items-center justify-center text-slate-400 text-xs">Loading map...</div>
      </div>
    </div>

    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs p-5 space-y-4 xl:sticky xl:top-24">
      <div id="barangayInfoEmpty" class="text-center py-8 text-slate-400 text-xs">
        <i class="fa-solid fa-hand-pointer text-2xl mb-2 block"></i>
        Click a barangay on the map to view its details.
      </div>

      <div id="barangayInfoPanel" class="hidden space-y-4">
        <div>
          <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Barangay</span>
          <h4 id="infoBarangayName" class="text-lg font-black text-slate-900">—</h4>
          <span id="infoBarangayPsgc" class="text-[10px] font-mono text-slate-400"></span>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="bg-slate-50 rounded-xl p-3">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Housing Units</span>
            <span id="infoHousingUnits" class="text-lg font-black text-slate-900">0</span>
          </div>
          <div class="bg-slate-50 rounded-xl p-3">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Occupied</span>
            <span id="infoOccupiedUnits" class="text-lg font-black text-slate-900">0</span>
          </div>
          <div class="bg-slate-50 rounded-xl p-3">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Applications</span>
            <span id="infoTotalApplications" class="text-lg font-black text-slate-900">0</span>
          </div>
          <div class="bg-slate-50 rounded-xl p-3">
            <span class="text-[9px] font-black uppercase tracking-wider text-slate-400 block">Approved</span>
            <span id="infoApprovedApplications" class="text-lg font-black text-slate-900">0</span>
          </div>
        </div>

        <div class="bg-amber-50 rounded-xl p-3">
          <span class="text-[9px] font-black uppercase tracking-wider text-amber-700 block">Pending Applications</span>
          <span id="infoPendingApplications" class="text-lg font-black text-amber-800">0</span>
        </div>

        <a id="infoViewHousingLink" href="../housing/housing-units.php" class="w-full flex items-center justify-center gap-2 bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition">
          <i class="fa-solid fa-house text-[10px]"></i>
          View Housing Units
        </a>
      </div>
    </div>
  </div>
</main>

<?php include '../../includes/footer.php'; ?>
