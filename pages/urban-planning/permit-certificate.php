<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
$applicationId = (int)($_GET['id'] ?? 0);
?>

<link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/reports-print.css">

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto">

  <div class="no-print flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>Urban Planning</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Permit</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-file-shield text-brand-dark"></i>
        Permit Document
      </h1>
    </div>
    <div class="shrink-0 flex items-center gap-2">
      <button onclick="window.print()" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-650 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition">
        <i class="fa-solid fa-print text-[10px]"></i>
        <span>Print / Save PDF</span>
      </button>
    </div>
  </div>

  <div id="paCertLoading" class="text-center text-xs text-slate-400 py-16">Loading...</div>

  <div id="paCertNotReady" class="hidden bg-white border border-slate-200/80 rounded-2xl p-10 text-center text-sm text-slate-500">
    This application has not had a permit issued or a final decision recorded yet. A permit document is only produced once issuance or denial is recorded.
  </div>

  <!-- Permit -->
  <div id="paCertificate" class="hidden bg-white border-2 border-slate-800 rounded-2xl p-10 max-w-2xl mx-auto space-y-6 text-center">
    <div class="space-y-1">
      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Republic of the Philippines &mdash; Local Government Unit</p>
      <h2 id="certTitle" class="text-xl font-black text-slate-900 uppercase tracking-tight">Building Permit</h2>
    </div>
    <p class="text-xs text-slate-600 leading-relaxed">This is to certify that the project described below has completed multi-discipline review and is hereby granted a permit, subject to the conditions of approval stated.</p>
    <div class="text-left grid grid-cols-2 gap-4 text-xs bg-slate-50 border border-slate-200 rounded-xl p-5">
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Permit Number</span><span id="certPermitNumber" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Reference Number</span><span id="certReference" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Applicant</span><span id="certApplicant" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Project</span><span id="certProject" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Location</span><span id="certLocation" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Date Issued</span><span id="certIssuedDate" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Expiry Date</span><span id="certExpiryDate" class="font-bold text-slate-800">&mdash;</span></div>
    </div>
    <div class="text-left space-y-1.5">
      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Conditions of Approval</p>
      <p id="certConditions" class="text-xs text-slate-700 leading-relaxed bg-slate-50 border border-slate-200 rounded-xl p-4">&mdash;</p>
    </div>
  </div>

  <!-- Notice of Denial -->
  <div id="paNotice" class="hidden bg-white border-2 border-rose-700 rounded-2xl p-10 max-w-2xl mx-auto space-y-6 text-center">
    <div class="space-y-1">
      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Republic of the Philippines &mdash; Local Government Unit</p>
      <h2 class="text-xl font-black text-rose-700 uppercase tracking-tight">Notice of Denial</h2>
    </div>
    <p class="text-xs text-slate-600 leading-relaxed">This is to notify the applicant that the application described below has been reviewed and denied.</p>
    <div class="text-left grid grid-cols-2 gap-4 text-xs bg-slate-50 border border-slate-200 rounded-xl p-5">
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Reference Number</span><span id="noticeReference" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Applicant</span><span id="noticeApplicant" class="font-bold text-slate-800">&mdash;</span></div>
      <div><span class="block text-[9px] font-black uppercase text-slate-400">Project</span><span id="noticeProject" class="font-bold text-slate-800">&mdash;</span></div>
    </div>
    <div class="text-left space-y-1.5">
      <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cited Findings</p>
      <p id="noticeFindings" class="text-xs text-slate-700 leading-relaxed bg-rose-50 border border-rose-150 rounded-xl p-4">&mdash;</p>
    </div>
  </div>

</main>

<script>
  window.civentralPermitApplicationId = <?php echo json_encode($applicationId); ?>;
</script>
<?php $paCertJsVer = @filemtime(__DIR__ . '/../../assets/js/urban-planning/permit-certificate.js') ?: time(); ?>
<script src="<?php echo $basePath; ?>assets/js/urban-planning/permit-certificate.js?v=<?php echo $paCertJsVer; ?>"></script>

<?php include '../../includes/footer.php'; ?>
