let benFilterDebounce = null;
function triggerBenFilter() {
  clearTimeout(benFilterDebounce);
  benFilterDebounce = setTimeout(() => fetchBeneficiaries(1), 350);
}

let residentPickerDebounce = null;
function triggerResidentPicker(term) {
  clearTimeout(residentPickerDebounce);
  const box = document.getElementById('benResidentResults');
  if (term.trim().length < 2) {
    box.classList.add('hidden');
    return;
  }
  residentPickerDebounce = setTimeout(async () => {
    const residents = await searchResidentsForPicker(term.trim());
    if (!residents.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching residents. Register them in Resident Management first.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = residents.map(r => `
      <button type="button" onclick='selectResident(${JSON.stringify(r).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${residentFullName(r)}</span>
        ${r.barangay ? `<span class="text-slate-400"> — ${r.barangay}</span>` : ''}
        ${r.household_number ? `<span class="text-slate-400 font-mono"> (HH ${r.household_number})</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}

let unitPickerDebounce = null;
function triggerUnitPicker(term) {
  clearTimeout(unitPickerDebounce);
  const box = document.getElementById('benUnitResults');
  if (term.trim().length < 1) {
    box.classList.add('hidden');
    return;
  }
  unitPickerDebounce = setTimeout(async () => {
    const units = await searchUnitsForPicker(term.trim());
    if (!units.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching units.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = units.map(u => `
      <button type="button" onclick='selectBenUnit(${JSON.stringify(u).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700 font-mono">${u.unit_code}</span>
        ${u.project_name ? `<span class="text-slate-400"> — ${u.project_name}</span>` : ''}
        <span class="text-slate-400"> [${u.occupancy_status || ''}]</span>
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
