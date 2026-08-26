let paFilterDebounce = null;
function triggerPaFilter() {
  clearTimeout(paFilterDebounce);
  paFilterDebounce = setTimeout(() => fetchApplications(1), 350);
}

let paResidentPickerDebounce = null;
function triggerPaResidentPicker(term) {
  clearTimeout(paResidentPickerDebounce);
  const box = document.getElementById('paResidentResults');
  if (term.trim().length < 2) {
    box.classList.add('hidden');
    return;
  }
  paResidentPickerDebounce = setTimeout(async () => {
    const residents = await searchResidentsForPaPicker(term.trim());
    if (!residents.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching residents. Register them in Resident Management first.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = residents.map(r => `
      <button type="button" onclick='selectPaResident(${JSON.stringify(r).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${paResidentFullName(r)}</span>
        ${r.barangay ? `<span class="text-slate-400"> — ${r.barangay}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
