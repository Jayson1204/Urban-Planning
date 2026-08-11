let infraFilterDebounce = null;
function triggerInfraFilter() {
  clearTimeout(infraFilterDebounce);
  infraFilterDebounce = setTimeout(() => fetchInfraRecords(1), 350);
}

let infraProjectPickerDebounce = null;
function triggerInfraProjectPicker(term) {
  clearTimeout(infraProjectPickerDebounce);
  const box = document.getElementById('infraProjectResults');
  if (term.trim().length < 1) {
    box.classList.add('hidden');
    return;
  }
  infraProjectPickerDebounce = setTimeout(async () => {
    const projects = await searchProjectsForPicker(term.trim());
    if (!projects.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching urban projects.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = projects.map(p => `
      <button type="button" onclick='selectInfraProject(${JSON.stringify(p).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700 font-mono">${p.project_code}</span>
        <span class="text-slate-400"> — ${p.project_title}</span>
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
