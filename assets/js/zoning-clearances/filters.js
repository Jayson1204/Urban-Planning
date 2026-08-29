let zcFilterDebounce = null;
function triggerZcFilter() {
  clearTimeout(zcFilterDebounce);
  zcFilterDebounce = setTimeout(() => fetchClearances(1), 350);
}

let zcResidentPickerDebounce = null;
function triggerZcResidentPicker(term) {
  clearTimeout(zcResidentPickerDebounce);
  const box = document.getElementById('zcResidentResults');
  if (term.trim().length < 2) {
    box.classList.add('hidden');
    return;
  }
  zcResidentPickerDebounce = setTimeout(async () => {
    const residents = await searchResidentsForPicker(term.trim());
    if (!residents.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching residents. Register them in Resident Management first.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = residents.map(r => `
      <button type="button" onclick='selectZcResident(${JSON.stringify(r).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${escapeHtml(zcResidentFullName(r))}</span>
        ${r.barangay ? `<span class="text-slate-400"> — ${escapeHtml(r.barangay)}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
