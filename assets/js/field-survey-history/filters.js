let historyResidentPickerDebounce = null;
function triggerHistoryResidentPicker(term) {
  clearTimeout(historyResidentPickerDebounce);
  const box = document.getElementById('historyResidentResults');
  if (term.trim().length < 2) {
    box.classList.add('hidden');
    return;
  }
  historyResidentPickerDebounce = setTimeout(async () => {
    const residents = await searchResidentsForHistoryPicker(term.trim());
    if (!residents.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching residents.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = residents.map(r => `
      <button type="button" onclick='selectHistoryResident(${JSON.stringify(r).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${residentFullName(r)}</span>
        ${r.barangay ? `<span class="text-slate-400"> — ${r.barangay}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}

let historyHouseholdPickerDebounce = null;
function triggerHistoryHouseholdPicker(term) {
  clearTimeout(historyHouseholdPickerDebounce);
  const box = document.getElementById('historyHouseholdResults');
  if (term.trim().length < 1) {
    box.classList.add('hidden');
    return;
  }
  historyHouseholdPickerDebounce = setTimeout(async () => {
    const households = await searchHouseholdsForHistoryPicker(term.trim());
    if (!households.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching households.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = households.map(h => `
      <button type="button" onclick='selectHistoryHousehold(${JSON.stringify(h).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700 font-mono">${h.household_number || 'HH'}</span>
        ${h.barangay ? `<span class="text-slate-400"> — ${h.barangay}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
