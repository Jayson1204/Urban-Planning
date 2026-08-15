<?php
$basePath = '../../';
include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="flex-1 p-6 md:p-8 w-full space-y-6 overflow-y-auto flex flex-col">

  <!-- Breadcrumb & Page Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/60 pb-5 shrink-0">
    <div class="space-y-1">
      <div class="flex items-center space-x-2 text-xs font-bold uppercase tracking-wider text-slate-400">
        <span>AI Assistant</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-60"></i>
        <span class="text-brand-dark">Chat</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2 mt-4">
        <i class="fa-solid fa-robot text-brand-dark"></i>
        AI Planning Assistant
      </h1>
      <p class="text-xs text-slate-500 max-w-2xl leading-relaxed">
        Ask questions about resident, housing, urban project, and field survey data. Answers are grounded in the current program analytics snapshot.
      </p>
    </div>
    <div class="shrink-0">
      <button onclick="clearAiChat()" class="border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition">
        <i class="fa-solid fa-broom text-[10px]"></i>
        <span>Clear Chat</span>
      </button>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="flex flex-wrap gap-2 shrink-0">
    <button onclick="sendQuickPrompt('Summarize the current program status across residents, housing, urban projects, and field surveys in a short report.')" class="bg-white border border-slate-200 hover:border-brand-medium hover:bg-brand-light text-xs font-bold text-slate-600 hover:text-brand-dark px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
      <i class="fa-solid fa-file-lines text-[10px]"></i> Summarize Program Status
    </button>
    <button onclick="sendQuickPrompt('Based on the current data, generate 3-5 concrete urban planning recommendations, prioritized by urgency.')" class="bg-white border border-slate-200 hover:border-brand-medium hover:bg-brand-light text-xs font-bold text-slate-600 hover:text-brand-dark px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
      <i class="fa-solid fa-lightbulb text-[10px]"></i> Generate Planning Recommendations
    </button>
    <button onclick="sendQuickPrompt('What does the housing occupancy breakdown tell us, and what should the team watch out for?')" class="bg-white border border-slate-200 hover:border-brand-medium hover:bg-brand-light text-xs font-bold text-slate-600 hover:text-brand-dark px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2">
      <i class="fa-solid fa-house-chimney text-[10px]"></i> Explain Housing Occupancy
    </button>
  </div>

  <!-- Chat Window -->
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs flex-1 flex flex-col overflow-hidden min-h-[420px]">
    <div id="aiChatMessages" class="flex-1 overflow-y-auto p-6 space-y-4">
      <div class="flex items-start gap-3">
        <div class="h-8 w-8 rounded-lg bg-brand-dark text-white flex items-center justify-center shrink-0">
          <i class="fa-solid fa-robot text-xs"></i>
        </div>
        <div class="bg-slate-50 border border-slate-100 rounded-2xl rounded-tl-sm px-4 py-3 max-w-2xl text-xs text-slate-600 leading-relaxed">
          Hi! I'm your AI Planning Assistant. Ask me about residents, housing, urban projects, or field surveys, or use one of the quick actions above.
        </div>
      </div>
    </div>

    <div id="aiTypingIndicator" class="hidden px-6 pb-2 text-[10px] text-slate-400 font-semibold items-center gap-2">
      <i class="fa-solid fa-robot text-[10px]"></i> Assistant is thinking...
    </div>

    <form id="aiChatForm" onsubmit="handleSendAiMessage(event)" class="border-t border-slate-100 p-4 flex items-center gap-3">
      <input type="text" id="aiChatInput" autocomplete="off" placeholder="Ask about program data, e.g. 'How many housing units are vacant?'" class="flex-1 border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/10 transition">
      <button type="submit" id="aiChatSendBtn" class="bg-[#0f172a] hover:bg-slate-800 text-white font-bold px-5 py-2.5 rounded-xl text-xs flex items-center gap-2 cursor-pointer transition shadow-xs">
        <i class="fa-solid fa-paper-plane text-[10px]"></i>
        <span>Send</span>
      </button>
    </form>
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
