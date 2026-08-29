function historyEventIcon(type) {
  if (type === 'Result Recorded') return 'fa-file-circle-check';
  return 'fa-clipboard-list';
}

function renderHistoryTimeline(events) {
  const container = document.getElementById('historyTimeline');
  if (!container) return;

  if (!events.length) {
    container.innerHTML = '<div class="px-6 py-8 text-center text-slate-400">No recorded survey history for this subject yet.</div>';
    return;
  }

  container.innerHTML = events.map(ev => `
    <div class="px-6 py-3.5 flex items-start gap-3">
      <div class="h-7 w-7 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 shrink-0 mt-0.5">
        <i class="fa-solid ${historyEventIcon(ev.type)} text-[10px]"></i>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
          <span class="font-bold text-slate-700">${escapeHtml(ev.type)}</span>
          <span class="text-slate-400 font-mono text-[10px] shrink-0">${escapeHtml(ev.date || '')}</span>
        </div>
        <p class="text-slate-500 mt-0.5">${escapeHtml(ev.description)}</p>
      </div>
    </div>
  `).join('');
}
