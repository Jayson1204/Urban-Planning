let relFilterDebounce = null;
function triggerRelFilter() {
  clearTimeout(relFilterDebounce);
  relFilterDebounce = setTimeout(() => fetchRelocations(1), 350);
}

let relResidentPickerDebounce = null;
function triggerRelResidentPicker(term) {
  clearTimeout(relResidentPickerDebounce);
  const box = document.getElementById('relResidentResults');
  if (term.trim().length < 2) {
    box.classList.add('hidden');
    return;
  }
  relResidentPickerDebounce = setTimeout(async () => {
    const residents = await searchResidentsForRelPicker(term.trim());
    if (!residents.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching residents.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = residents.map(r => `
      <button type="button" onclick='selectRelResident(${JSON.stringify(r).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${escapeHtml(residentFullName(r))}</span>
        ${r.barangay ? `<span class="text-slate-400"> — ${escapeHtml(r.barangay)}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}

let relToUnitPickerDebounce = null;
function triggerRelToUnitPicker(term) {
  clearTimeout(relToUnitPickerDebounce);
  const box = document.getElementById('relToUnitResults');
  if (term.trim().length < 1) {
    box.classList.add('hidden');
    return;
  }
  relToUnitPickerDebounce = setTimeout(async () => {
    const units = await searchUnitsForRelPicker(term.trim());
    if (!units.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching units.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = units.map(u => `
      <button type="button" onclick='selectRelToUnit(${JSON.stringify(u).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700 font-mono">${escapeHtml(u.unit_code)}</span>
        ${u.project_name ? `<span class="text-slate-400"> — ${escapeHtml(u.project_name)}</span>` : ''}
        <span class="text-slate-400"> [${escapeHtml(u.occupancy_status || '')}]</span>
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
