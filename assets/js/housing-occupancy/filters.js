let occFilterDebounce = null;
function triggerOccFilter() {
  clearTimeout(occFilterDebounce);
  occFilterDebounce = setTimeout(() => fetchOccupancy(1), 350);
}

let occResidentPickerDebounce = null;
function triggerOccResidentPicker(term) {
  clearTimeout(occResidentPickerDebounce);
  const box = document.getElementById('occResidentResults');
  if (term.trim().length < 2) {
    box.classList.add('hidden');
    return;
  }
  occResidentPickerDebounce = setTimeout(async () => {
    const residents = await searchResidentsForOccPicker(term.trim());
    if (!residents.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching residents.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = residents.map(r => `
      <button type="button" onclick='selectOccResident(${JSON.stringify(r).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${escapeHtml(residentFullName(r))}</span>
        ${r.barangay ? `<span class="text-slate-400"> — ${escapeHtml(r.barangay)}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}

let occUnitPickerDebounce = null;
function triggerOccUnitPicker(term) {
  clearTimeout(occUnitPickerDebounce);
  const box = document.getElementById('occUnitResults');
  if (term.trim().length < 1) {
    box.classList.add('hidden');
    return;
  }
  occUnitPickerDebounce = setTimeout(async () => {
    const units = await searchUnitsForOccPicker(term.trim());
    if (!units.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching units.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = units.map(u => `
      <button type="button" onclick='selectOccUnit(${JSON.stringify(u).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700 font-mono">${escapeHtml(u.unit_code)}</span>
        ${u.project_name ? `<span class="text-slate-400"> — ${escapeHtml(u.project_name)}</span>` : ''}
        <span class="text-slate-400"> [${escapeHtml(u.occupancy_status || '')}]</span>
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
