let projectFilterDebounce = null;
function triggerProjectFilter() {
  clearTimeout(projectFilterDebounce);
  projectFilterDebounce = setTimeout(() => fetchProjects(1), 350);
}

let projectPlanPickerDebounce = null;
function triggerProjectPlanPicker(term) {
  clearTimeout(projectPlanPickerDebounce);
  const box = document.getElementById('projectPlanResults');
  if (term.trim().length < 1) {
    box.classList.add('hidden');
    return;
  }
  projectPlanPickerDebounce = setTimeout(async () => {
    const plans = await searchPlansForPicker(term.trim());
    if (!plans.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching development plans.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = plans.map(pl => `
      <button type="button" onclick='selectProjectPlan(${JSON.stringify(pl).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700 font-mono">${escapeHtml(pl.plan_code)}</span>
        <span class="text-slate-400"> — ${escapeHtml(pl.plan_title)}</span>
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
